<?php
// src/Form/ChangePasswordType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attr = ['class' => 'form-control'];
        $labelAttr = ['class' => 'form-label'];

        $builder
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'attr' => $attr + ['autocomplete' => 'current-password'],
                'label_attr' => $labelAttr,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre mot de passe actuel.']),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => $attr + ['autocomplete' => 'new-password'],
                    'label_attr' => $labelAttr,
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => $attr + ['autocomplete' => 'new-password'],
                    'label_attr' => $labelAttr,
                ],
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir un nouveau mot de passe.']),
                    new Assert\Length(['min' => 8, 'minMessage' => 'Au moins {{ limit }} caractères.']),
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Mot de passe trop faible. Utilisez lettres, chiffres et caractères spéciaux.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
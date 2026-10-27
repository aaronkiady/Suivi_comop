<?php
// src/Form/AdminUserType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attr = ['class' => 'form-control'];
        $labelAttr = ['class' => 'form-label'];
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, ['label' => 'Nom', 'attr' => $attr, 'label_attr' => $labelAttr])
            ->add('prenoms', TextType::class, ['label' => 'Prénoms', 'attr' => $attr, 'label_attr' => $labelAttr])
            ->add('matricule', TextType::class, ['label' => 'Matricule', 'attr' => $attr, 'label_attr' => $labelAttr])
            ->add('email', EmailType::class, ['label' => 'Email', 'attr' => $attr, 'label_attr' => $labelAttr])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe (laisser vide pour conserver)' : 'Mot de passe initial',
                'mapped' => false,
                'required' => false, // Optionnel
                'attr' => $attr,
                'label_attr' => $labelAttr,
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Assert\Length(['min' => 6, 'minMessage' => 'Au moins {{ limit }} caractères.']),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle attribué',
                'mapped' => false,
                'choices' => [
                    'Utilisateur Standard' => 'ROLE_USER',
                    'Comptabilité' => 'ROLE_COMPTA',
                    'Directeur Technique (DT)' => 'ROLE_DT',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'attr' => ['class' => 'form-select'],
                'label_attr' => $labelAttr,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
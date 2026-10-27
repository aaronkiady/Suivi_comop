<?php
// src/Form/RegistrationFormType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- NOUVEAUX CHAMPS ---
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre nom.']),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('prenoms', TextType::class, [
                'label' => 'Prénoms',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Vos prénoms',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir vos prénoms.']),
                    new Assert\Length(['max' => 150]),
                ],
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: OTI-2023-001',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre matricule.']),
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Le matricule ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            // --- CHAMPS EXISTANTS ---
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'nom@exemple.com',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre adresse email.']),
                    new Assert\Email(['message' => 'L\'adresse email "{{ value }}" n\'est pas valide.']),
                    new Assert\Length(['max' => 180]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => '••••••••',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => '••••••••',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir un mot de passe.']),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 4096,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Votre mot de passe est trop faible. Utilisez des lettres, chiffres et caractères spéciaux.',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation et la politique de confidentialité',
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'Vous devez accepter les conditions pour vous inscrire.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
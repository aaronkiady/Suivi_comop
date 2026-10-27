<?php
// src/Form/ProfileType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attr = ['class' => 'form-control'];
        $labelAttr = ['class' => 'form-label'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => $attr,
                'label_attr' => $labelAttr,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('prenoms', TextType::class, [
                'label' => 'Prénoms',
                'attr' => $attr,
                'label_attr' => $labelAttr,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Les prénoms sont obligatoires.']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'attr' => $attr,
                'label_attr' => $labelAttr,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le matricule est obligatoire.']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => $attr,
                'label_attr' => $labelAttr,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Assert\Email(['message' => 'L\'adresse email n\'est pas valide.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
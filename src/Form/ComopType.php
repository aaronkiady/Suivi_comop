<?php
// src/Form/ComopType.php

namespace App\Form;

use App\Entity\Comop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attr = ['class' => 'form-control'];
        $labelAttr = ['class' => 'form-label'];

        $builder
            ->add('projet', TextType::class, [
                'label' => 'Projet',
                'attr' => $attr + ['placeholder' => 'Nom du projet'],
                'label_attr' => $labelAttr,
            ])
            ->add('refBc', TextType::class, [
                'label' => 'Référence BC',
                'attr' => $attr + ['placeholder' => 'Ex: BC-2024-001'],
                'label_attr' => $labelAttr,
            ])
            ->add('fournisseur', TextType::class, [
                'label' => 'Fournisseur',
                'attr' => $attr + ['placeholder' => 'Nom du fournisseur'],
                'label_attr' => $labelAttr,
            ])
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
                'attr' => $attr + ['placeholder' => 'Description de la commande'],
                'label_attr' => $labelAttr,
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (MGA)',
                'currency' => 'MGA',
                'divisor' => 1,
                'attr' => $attr,
                'label_attr' => $labelAttr,
            ])
            ->add('montantUsd', MoneyType::class, [
                'label' => 'Montant (USD)',
                'currency' => 'USD',
                'divisor' => 1,
                'attr' => $attr,
                'label_attr' => $labelAttr,
            ])
            ->add('dt', TextType::class, [
                'label' => 'Directeur des Travaux',
                'attr' => $attr,
                'label_attr' => $labelAttr,
            ])
            ->add('datePaiement', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'required' => false,
                'attr' => $attr,
                'label_attr' => $labelAttr,
            ])
            ->add('piecesJointesFile', FileType::class, [
                'label' => 'Pièces jointes (PDF, DOCX, images)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png'
                ],
            ]);
            
        // ⚠️ Condition : n'ajoute le champ 'statut' QUE si l'option est à true
        if ($options['can_edit_statut']) {
            $builder->add('statut', TextType::class, [
                'label' => 'Statut',
                'attr' => $attr + ['placeholder' => 'Ex: En attente, Payé...'],
                'label_attr' => $labelAttr,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comop::class,
            'can_edit_statut' => false, // Valeur par défaut : le champ est masqué
        ]);
    }
}
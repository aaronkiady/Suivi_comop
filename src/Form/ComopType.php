<?php
// src/Form/ComopType.php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Comop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
        $attr      = ['class' => 'form-control'];
        $labelAttr = ['class' => 'form-label'];

        // ---------- Groupe 1 : Commande (ROLE_DT, ROLE_ADMIN) ----------
        if ($options['can_edit_commande']) {
            $builder
                ->add('projet', TextType::class, [
                    'label'      => 'Projet',
                    'attr'       => $attr + ['placeholder' => 'Nom du projet'],
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ])
                ->add('refBc', TextType::class, [
                    'label'      => 'Référence BC',
                    'attr'       => $attr + ['placeholder' => 'Ex: BC-2024-001'],
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ])
                ->add('fournisseur', TextType::class, [
                    'label'      => 'Fournisseur',
                    'attr'       => $attr + ['placeholder' => 'Nom du fournisseur'],
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ])
                ->add('designation', TextType::class, [
                    'label'      => 'Désignation',
                    'attr'       => $attr + ['placeholder' => 'Description de la commande'],
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ])
                ->add('dt', TextType::class, [
                    'label'      => 'Directeur des Travaux',
                    'attr'       => $attr,
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ]);
        }

        // ---------- Montant (ROLE_DT, ROLE_ADMIN) ----------
        if ($options['can_edit_montant']) {
            $builder->add('montant', MoneyType::class, [
                'label'      => 'Montant',
                'currency'   => false,
                'divisor'    => 1,
                'attr'       => $attr,
                'label_attr' => $labelAttr,
                'required'   => false,
            ]);
        }

        // ---------- Devise (ROLE_DT, ROLE_ADMIN) ----------
        if ($options['can_edit_devise']) {
            $builder->add('devise', ChoiceType::class, [
                'label'       => 'Devise',
                'choices'     => [
                    'MGA' => 'MGA',
                    'USD' => 'USD',
                    'EUR' => 'EUR',
                ],
                'placeholder' => '— Choisir —',
                'required'    => false,
                'attr'        => ['class' => 'form-select'],
                'label_attr'  => $labelAttr,
            ]);
        }

        // ---------- Finances (ROLE_FINANCE, ROLE_ADMIN) ----------
        if ($options['can_edit_finances']) {
            $builder
                ->add('montantPaye', MoneyType::class, [
                    'label'      => 'Montant déjà payé',
                    'currency'   => false,
                    'divisor'    => 1,
                    'attr'       => $attr,
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ])
                ->add('statut', TextType::class, [
                    'label'      => 'Statut',
                    'attr'       => $attr + ['placeholder' => 'Ex: En attente, Payé...'],
                    'label_attr' => $labelAttr,
                    'required'   => false,
                ])
                ->add('datePaiement', DateType::class, [
                    'label'      => 'Date de paiement',
                    'widget'     => 'single_text',
                    'required'   => false,
                    'attr'       => $attr,
                    'label_attr' => $labelAttr,
                ]);
        }

        // ---------- Pièces jointes (Tout le monde) ----------
        $builder->add('piecesJointesFile', FileType::class, [
            'label'    => 'Pièces jointes (PDF, DOCX, images)',
            'mapped'   => false,
            'required' => false,
            'multiple' => true,
            'attr'     => ['accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png', 'class' => 'form-control'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Comop::class,
            'can_edit_commande' => false,
            'can_edit_montant'  => false,
            'can_edit_devise'   => false,
            'can_edit_finances' => false,
        ]);
    }
}
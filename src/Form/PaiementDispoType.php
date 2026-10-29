<?php

namespace App\Form;

use App\Entity\PaiementDispo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaiementDispoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attr = ['class' => 'form-control'];
        $labelAttr = ['class' => 'form-label'];

        $builder
            ->add('dateEtablissement', DateType::class, [
                'label' => 'Date d\'établissement',
                'widget' => 'single_text',
                'required' => false,
                'attr' => $attr,
                'label_attr' => $labelAttr,
            ])
            ->add('typePaiement', TextType::class, [
                'label' => 'Type de paiement',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Ex: Virement, Chèque...'],
                'label_attr' => $labelAttr,
            ])
            ->add('banque', TextType::class, [
                'label' => 'Banque',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Nom de la banque'],
                'label_attr' => $labelAttr,
            ])
            ->add('societe', TextType::class, [
                'label' => 'Société',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Nom de la société'],
                'label_attr' => $labelAttr,
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Réf. du paiement'],
                'label_attr' => $labelAttr,
            ])
            ->add('echeanceTraite', TextType::class, [
                'label' => 'Échéance traite',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Ex: 30 jours'],
                'label_attr' => $labelAttr,
            ])
            ->add('fournisseurs', TextType::class, [
                'label' => 'Fournisseur',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Nom du fournisseur'],
                'label_attr' => $labelAttr,
            ])
            ->add('ariary', IntegerType::class, [
                'label' => 'Montant (Ariary)',
                'required' => false,
                'attr' => $attr + ['placeholder' => '0', 'min' => 0],
                'label_attr' => $labelAttr,
            ])
            ->add('euro', IntegerType::class, [
                'label' => 'Montant (Euro)',
                'required' => false,
                'attr' => $attr + ['placeholder' => '0', 'min' => 0],
                'label_attr' => $labelAttr,
            ])
            ->add('dollar', IntegerType::class, [
                'label' => 'Montant (Dollar)',
                'required' => false,
                'attr' => $attr + ['placeholder' => '0', 'min' => 0],
                'label_attr' => $labelAttr,
            ])
            ->add('statutSwift', TextType::class, [
                'label' => 'Statut Swift',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Ex: Envoyé, Reçu...'],
                'label_attr' => $labelAttr,
            ])
            ->add('statPaiement', TextType::class, [
                'label' => 'Statut paiement',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Ex: Payé, En attente...'],
                'label_attr' => $labelAttr,
            ])
            ->add('piecesBc', TextType::class, [
                'label' => 'Ref. BC',
                'required' => false,
                'attr' => $attr + ['placeholder' => 'Réf. pièces justificatives'],
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaiementDispo::class,
        ]);
    }
}
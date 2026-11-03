<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ComopRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComopRepository::class)]
class Comop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $projet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refBc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fournisseur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $designation = null;

    /**
     * Montant principal exprimé dans la devise choisie (MGA, USD ou EUR).
     */
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $montant = null;

    /**
     * Ancien champ historique : conservé pour compatibilité.
     */
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $montantUsd = null;

    /**
     * Devise du montant : MGA, USD ou EUR.
     */
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $devise = 'MGA';

    /**
     * Montant déjà payé, dans la même devise que $montant.
     */
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $montantPaye = 0;

    /**
     * Reste à payer — recalculé automatiquement via updateResteAPayer().
     * Non modifiable directement : pas de setter public.
     */
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $resteAPayer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $datePaiement = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $dateInsertion = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $piecesJointes = [];

    public function __construct()
    {
        $this->dateInsertion = new DateTimeImmutable();
    }

    // ---------------------------------------------------------------------
    //  Getters / Setters
    // ---------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjet(): ?string
    {
        return $this->projet;
    }

    public function setProjet(string $projet): static
    {
        $this->projet = $projet;

        return $this;
    }

    public function getRefBc(): ?string
    {
        return $this->refBc;
    }

    public function setRefBc(string $refBc): static
    {
        $this->refBc = $refBc;

        return $this;
    }

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(?int $montant): static
    {
        $this->montant = $montant;
        $this->updateResteAPayer();

        return $this;
    }

    public function getMontantUsd(): ?int
    {
        return $this->montantUsd;
    }

    public function setMontantUsd(?int $montantUsd): static
    {
        $this->montantUsd = $montantUsd;

        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(?string $devise): static
    {
        $this->devise = $devise;
        $this->updateResteAPayer();

        return $this;
    }

    public function getMontantPaye(): ?int
    {
        return $this->montantPaye;
    }

    public function setMontantPaye(?int $montantPaye): static
    {
        $this->montantPaye = $montantPaye;
        $this->updateResteAPayer();

        return $this;
    }

    public function getResteAPayer(): ?int
    {
        return $this->resteAPayer;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDt(): ?string
    {
        return $this->dt;
    }

    public function setDt(?string $dt): static
    {
        $this->dt = $dt;

        return $this;
    }

    public function getDatePaiement(): ?DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getDateInsertion(): ?DateTimeImmutable
    {
        return $this->dateInsertion;
    }

    public function setDateInsertion(DateTimeImmutable $dateInsertion): static
    {
        $this->dateInsertion = $dateInsertion;

        return $this;
    }

    public function getPiecesJointes(): ?array
    {
        return $this->piecesJointes;
    }

    public function setPiecesJointes(?array $piecesJointes): static
    {
        $this->piecesJointes = $piecesJointes;

        return $this;
    }

    // ---------------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------------

    public function addPieceJointe(string $filename): static
    {
        $this->piecesJointes[] = $filename;

        return $this;
    }

    public function removePieceJointe(string $filename): static
    {
        $this->piecesJointes = array_filter(
            $this->piecesJointes ?? [],
            static fn (string $f): bool => $f !== $filename
        );

        return $this;
    }

    /**
     * Recalcule automatiquement le reste à payer.
     *
     * Formule : montant - montantPaye, borné à 0.
     * Appelé automatiquement dans setMontant(), setMontantPaye() et setDevise().
     */
    private function updateResteAPayer(): void
    {
        if ($this->montant === null) {
            $this->resteAPayer = null;

            return;
        }

        $paye = $this->montantPaye ?? 0;

        $this->resteAPayer = max(0, $this->montant - $paye);
    }
}
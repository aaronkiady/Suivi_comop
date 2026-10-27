<?php

namespace App\Entity;

use App\Repository\ComopRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: ComopRepository::class)]
class Comop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $projet = null;

    #[ORM\Column(length: 255)]
    private ?string $refBc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fournisseur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $designation = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $montant = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $montantUsd = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $datePaiement = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $piecesJointes = [];

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

    public function getDatePaiement(): ?\DateTime
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTime $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

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

    public function addPieceJointe(string $filename): static
    {
        $this->piecesJointes[] = $filename;
        return $this;
    }

    public function removePieceJointe(string $filename): static
    {
        $this->piecesJointes = array_filter($this->piecesJointes ?? [], fn($f) => $f !== $filename);
        return $this;
    }
}

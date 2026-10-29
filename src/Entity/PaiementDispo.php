<?php

namespace App\Entity;

use App\Repository\PaiementDispoRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: PaiementDispoRepository::class)]
class PaiementDispo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateEtablissement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typePaiement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banque = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $societe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $echeanceTraite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fournisseurs = null;

    #[ORM\Column(nullable: true)]
    private ?int $ariary = null;

    #[ORM\Column(nullable: true)]
    private ?int $euro = null;

    #[ORM\Column(nullable: true)]
    private ?int $dollar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statutSwift = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statPaiement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $piecesBc = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $piecesJointes = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEtablissement(): ?\DateTime
    {
        return $this->dateEtablissement;
    }

    public function setDateEtablissement(?\DateTime $dateEtablissement): static
    {
        $this->dateEtablissement = $dateEtablissement;

        return $this;
    }

    public function getTypePaiement(): ?string
    {
        return $this->typePaiement;
    }

    public function setTypePaiement(?string $typePaiement): static
    {
        $this->typePaiement = $typePaiement;

        return $this;
    }

    public function getBanque(): ?string
    {
        return $this->banque;
    }

    public function setBanque(?string $banque): static
    {
        $this->banque = $banque;

        return $this;
    }

    public function getSociete(): ?string
    {
        return $this->societe;
    }

    public function setSociete(?string $societe): static
    {
        $this->societe = $societe;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getEcheanceTraite(): ?string
    {
        return $this->echeanceTraite;
    }

    public function setEcheanceTraite(?string $echeanceTraite): static
    {
        $this->echeanceTraite = $echeanceTraite;

        return $this;
    }

    public function getFournisseurs(): ?string
    {
        return $this->fournisseurs;
    }

    public function setFournisseurs(?string $fournisseurs): static
    {
        $this->fournisseurs = $fournisseurs;

        return $this;
    }

    public function getAriary(): ?int
    {
        return $this->ariary;
    }

    public function setAriary(?int $ariary): static
    {
        $this->ariary = $ariary;

        return $this;
    }

    public function getEuro(): ?int
    {
        return $this->euro;
    }

    public function setEuro(?int $euro): static
    {
        $this->euro = $euro;

        return $this;
    }

    public function getDollar(): ?int
    {
        return $this->dollar;
    }

    public function setDollar(?int $dollar): static
    {
        $this->dollar = $dollar;

        return $this;
    }

    public function getStatutSwift(): ?string
    {
        return $this->statutSwift;
    }

    public function setStatutSwift(?string $statutSwift): static
    {
        $this->statutSwift = $statutSwift;

        return $this;
    }

    public function getStatPaiement(): ?string
    {
        return $this->statPaiement;
    }

    public function setStatPaiement(?string $statPaiement): static
    {
        $this->statPaiement = $statPaiement;

        return $this;
    }

    public function getPiecesBc(): ?string
    {
        return $this->piecesBc;
    }

    public function setPiecesBc(?string $piecesBc): static
    {
        $this->piecesBc = $piecesBc;

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

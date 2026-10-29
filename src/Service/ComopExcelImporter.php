<?php
// src/Service/ComopExcelImporter.php

namespace App\Service;

use App\Entity\Comop;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ComopExcelImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $successCount = 0;
        $errors = [];

        $firstRow = true;
        foreach ($rows as $rowIndex => $row) {
            if ($firstRow) {
                $firstRow = false;
                continue;
            }

            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $comop = new Comop();

                $projet = trim((string) ($row['A'] ?? ''));
                $refBc = trim((string) ($row['B'] ?? ''));

                if (empty($projet)) {
                    $errors[] = "Ligne $rowIndex : le champ 'Projet' est obligatoire.";
                    continue;
                }
                if (empty($refBc)) {
                    $errors[] = "Ligne $rowIndex : le champ 'Réf. BC' est obligatoire.";
                    continue;
                }

                $comop->setProjet($projet);
                $comop->setRefBc($refBc);
                $comop->setFournisseur($this->cleanString($row['C'] ?? null));
                $comop->setDesignation($this->cleanString($row['D'] ?? null));
                
                // ✅ Conversion propre en entier (supprime espaces, virgules, points)
                $comop->setMontant($this->toInt($row['E'] ?? null));
                $comop->setMontantUsd($this->toInt($row['F'] ?? null));
                
                $comop->setStatut($this->cleanString($row['G'] ?? null));
                
                // ✅ 'dt' est le nom du Directeur des Travaux, pas une date
                $comop->setDt($this->cleanString($row['H'] ?? null));

                // Gestion de la date de paiement
                if (!empty($row['I'])) {
                    $dateValue = $row['I'];
                    if (is_numeric($dateValue)) {
                        $comop->setDatePaiement(ExcelDate::excelToDateTimeObject((float) $dateValue));
                    } else {
                        try {
                            $comop->setDatePaiement(new \DateTime((string) $dateValue));
                        } catch (\Exception $e) {
                            $errors[] = "Ligne $rowIndex : format de date invalide pour 'Date Paiement'.";
                            continue;
                        }
                    }
                }

                $this->entityManager->persist($comop);
                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "Ligne $rowIndex : " . $e->getMessage();
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return [
                'success' => 0,
                'errors' => ['Erreur lors de l\'enregistrement en base de données : ' . $e->getMessage()],
            ];
        }

        return [
            'success' => $successCount,
            'errors' => $errors,
        ];
    }

    /**
     * Nettoie une valeur texte : trim, retourne null si vide.
     */
    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Convertit une valeur (string ou float) en entier propre.
     * Supprime les espaces, virgules, caractères non numériques.
     */
    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Si c'est déjà un nombre (float/int venant d'Excel)
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
        
        // Si c'est une chaîne : "12 500 000", "12,500,000", "12500000.50"
        $cleaned = preg_replace('/[^0-9\-]/', '', (string) $value);
        
        return $cleaned === '' ? null : (int) $cleaned;
    }
}
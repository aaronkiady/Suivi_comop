<?php
// src/Service/PaiementDispoExcelImporter.php

namespace App\Service;

use App\Entity\PaiementDispo;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PaiementDispoExcelImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array{success: int, errors: array<string>}
     */
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
                $paiement = new PaiementDispo();

                // Colonne B : Date Etablissement
                if (!empty($row['B'])) {
                    $dateValue = $row['B'];
                    if (is_numeric($dateValue)) {
                        $paiement->setDateEtablissement(ExcelDate::excelToDateTimeObject((float) $dateValue));
                    } else {
                        try {
                            $paiement->setDateEtablissement(new \DateTime((string) $dateValue));
                        } catch (\Exception $e) {
                            $errors[] = "Ligne $rowIndex : date d'établissement invalide.";
                            continue;
                        }
                    }
                }

                $paiement->setTypePaiement($this->cleanString($row['C'] ?? null));
                $paiement->setBanque($this->cleanString($row['D'] ?? null));
                $paiement->setSociete($this->cleanString($row['E'] ?? null));
                $paiement->setReference($this->cleanString($row['F'] ?? null));
                $paiement->setEcheanceTraite($this->cleanString($row['G'] ?? null));
                $paiement->setFournisseurs($this->cleanString($row['H'] ?? null));
                $paiement->setAriary($this->toInt($row['I'] ?? null));
                $paiement->setEuro($this->toInt($row['J'] ?? null));
                $paiement->setDollar($this->toInt($row['K'] ?? null));
                $paiement->setStatutSwift($this->cleanString($row['L'] ?? null));
                $paiement->setStatPaiement($this->cleanString($row['M'] ?? null));
                $paiement->setPiecesBc($this->cleanString($row['N'] ?? null));

                $this->entityManager->persist($paiement);
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

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
        $cleaned = preg_replace('/[^0-9\-]/', '', (string) $value);
        return $cleaned === '' ? null : (int) $cleaned;
    }
}
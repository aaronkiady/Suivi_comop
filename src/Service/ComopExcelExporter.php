<?php
// src/Service/ComopExcelExporter.php

namespace App\Service;

use App\Entity\Comop;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComopExcelExporter
{
    /**
     * Génère une réponse HTTP contenant le fichier Excel.
     *
     * @param Comop[] $comops
     */
    public function export(array $comops): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comops');

        // --- 1. Configuration des en-têtes ---
        $headers = [
            'A1' => 'Projet',
            'B1' => 'Réf. BC',
            'C1' => 'Fournisseur',
            'D1' => 'Désignation',
            'E1' => 'Montant (MGA)',
            'F1' => 'Montant (USD)',
            'G1' => 'Statut',
            'H1' => 'Date DT',
            'I1' => 'Date Paiement',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style des en-têtes : fond bleu OTI, texte blanc, gras, centré
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B4F82'], // Bleu OTI
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '143C64'],
                ],
            ],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // --- 2. Remplissage des données ---
        $row = 2;
        foreach ($comops as $comop) {
            $sheet->setCellValue('A' . $row, $comop->getProjet());
            $sheet->setCellValue('B' . $row, $comop->getRefBc());
            $sheet->setCellValue('C' . $row, $comop->getFournisseur() ?? '—');
            $sheet->setCellValue('D' . $row, $comop->getDesignation() ?? '—');
            $sheet->setCellValue('E' . $row, (float) $comop->getMontant());
            $sheet->setCellValue('F' . $row, (float) $comop->getMontantUsd());
            $sheet->setCellValue('G' . $row, $comop->getStatut() ?? '—');
            $sheet->setCellValue('H' . $row, $comop->getDt() ?? '—');
            $sheet->setCellValue('I' . $row, $comop->getDatePaiement()?->format('d/m/Y') ?? '—');

            // Alternance de couleurs pour les lignes
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F4F7F6');
            }

            $row++;
        }

        // --- 3. Formatage des colonnes numériques ---
        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            // Format monétaire MGA
            $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0 "Ar"');
            // Format monétaire USD
            $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()
                ->setFormatCode('"$"#,##0.00');

            // Bordures fines sur toutes les cellules de données
            $sheet->getStyle('A2:J' . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('DEE2E6');

            // Alignement centré pour ID, Statut, Dates
            $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H2:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // --- 4. Ajustement automatique de la largeur des colonnes ---
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Figer la première ligne (en-têtes) lors du défilement
        $sheet->freezePane('A2');

        // --- 5. Génération de la réponse HTTP ---
        $filename = 'Comops_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
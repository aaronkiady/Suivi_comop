<?php
// src/Service/PaiementDispoExcelExporter.php

namespace App\Service;

use App\Entity\PaiementDispo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaiementDispoExcelExporter
{
    /**
     * @param PaiementDispo[] $paiements
     */
    public function export(array $paiements): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Paiements');

        // --- En-têtes ---
        $headers = [
            'A1' => 'ID',
            'B1' => 'Date Etablissement',
            'C1' => 'Type Paiement',
            'D1' => 'Banque',
            'E1' => 'Société',
            'F1' => 'Référence',
            'G1' => 'Échéance Traite',
            'H1' => 'Fournisseurs',
            'I1' => 'Ariary',
            'J1' => 'Euro',
            'K1' => 'Dollar',
            'L1' => 'Statut Swift',
            'M1' => 'Statut Paiement',
            'N1' => 'Pièces BC',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style des en-têtes (bleu OTI)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B4F82'],
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
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // --- Données ---
        $row = 2;
        foreach ($paiements as $p) {
            $sheet->setCellValue('A' . $row, $p->getId());
            $sheet->setCellValue('B' . $row, $p->getDateEtablissement()?->format('Y-m-d') ?? '');
            $sheet->setCellValue('C' . $row, $p->getTypePaiement() ?? '');
            $sheet->setCellValue('D' . $row, $p->getBanque() ?? '');
            $sheet->setCellValue('E' . $row, $p->getSociete() ?? '');
            $sheet->setCellValue('F' . $row, $p->getReference() ?? '');
            $sheet->setCellValue('G' . $row, $p->getEcheanceTraite() ?? '');
            $sheet->setCellValue('H' . $row, $p->getFournisseurs() ?? '');
            $sheet->setCellValue('I' . $row, $p->getAriary() ?? 0);
            $sheet->setCellValue('J' . $row, $p->getEuro() ?? 0);
            $sheet->setCellValue('K' . $row, $p->getDollar() ?? 0);
            $sheet->setCellValue('L' . $row, $p->getStatutSwift() ?? '');
            $sheet->setCellValue('M' . $row, $p->getStatPaiement() ?? '');
            $sheet->setCellValue('N' . $row, $p->getPiecesBc() ?? '');

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':N' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F4F7F6');
            }
            $row++;
        }

        // --- Formats numériques et bordures ---
        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('I2:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K2:K' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->getStyle('A2:N' . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('DEE2E6');

            $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Auto-size
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Figer la première ligne
        $sheet->freezePane('A2');

        // --- Génération de la réponse ---
        $filename = 'Paiements_Dispo_' . date('Y-m-d_His') . '.xlsx';
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
<?php
// generate_test_excel.php
// À exécuter UNE SEULE FOIS : php generate_test_excel.php
// Puis supprimer le fichier.

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Comops');

// --- En-têtes ---
$headers = ['ID', 'Projet', 'Réf. BC', 'Fournisseur', 'Désignation', 'Montant (MGA)', 'Montant (USD)', 'Statut', 'Directeur des Travaux', 'Date Paiement'];
$sheet->fromArray($headers, null, 'A1');

// Style des en-têtes
$sheet->getStyle('A1:J1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
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
]);
$sheet->getRowDimension(1)->setRowHeight(25);

// --- Données de test (les montants sont des nombres bruts, pas de strings) ---
$data = [
    ['', 'Aménagement Bureau', 'BC-2024-001', 'BTP Madagascar SARL', 'Travaux de rénovation des locaux', 12500000, 2650, 'Payé', 'Rakoto Jean', '2024-01-15'],
    ['', 'Achat Véhicule', 'BC-2024-002', 'Toyota Madagascar', 'Toyota Hilux 4x4', 85000000, 18000, 'En attente', 'Rabe Pierre', ''],
    ['', 'Infrastructure Réseau', 'BC-2024-003', 'Cisco Partner OI', 'Switchs et routeurs Cisco', 42000000, 8900, 'En attente', 'Razafy Marc', ''],
    ['', 'Fournitures Bureau', 'BC-2024-004', 'Papeterie Centrale', 'Papier A4, stylos, classeurs', 1850000, 390, 'Payé', 'Rakoto Jean', '2024-01-25'],
    ['', 'Maintenance Serveurs', 'BC-2024-005', 'InfoTech Services', 'Contrat maintenance annuel', 36000000, 7600, 'Payé', 'Rabe Pierre', '2024-03-05'],
    ['', 'Formation Sécurité', 'BC-2024-006', 'SafeGuard Formation', 'Formation sécurité IT pour 20 employés', 7200000, 1520, 'En attente', 'Razafy Marc', ''],
    ['', 'Climatisation', 'BC-2024-007', 'Froid Expert', 'Installation 5 unités climatisation', 15000000, 3180, 'Payé', 'Rakoto Jean', '2024-02-28'],
    ['', 'Licences Logicielles', 'BC-2024-008', 'Microsoft Partner', 'Licences Office 365 - 50 utilisateurs', 22500000, 4750, 'Payé', 'Rabe Pierre', '2024-01-10'],
    ['', 'Carburant', 'BC-2024-009', 'Total Energies', 'Carburant véhicules - Q1 2024', 4800000, 1010, 'En attente', 'Razafy Marc', ''],
    ['', 'Audit Comptable', 'BC-2024-010', 'Cabinet Expertise', 'Audit annuel 2023', 18000000, 3800, 'En attente', 'Rakoto Jean', ''],
];

$row = 2;
foreach ($data as $line) {
    $sheet->fromArray($line, null, 'A' . $row);
    
    if ($row % 2 === 0) {
        $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F4F7F6');
    }
    $row++;
}

$lastRow = $row - 1;

// ✅ IMPORTANT : On applique le format d'affichage MAIS les cellules contiennent des int purs.
// On utilise le format '#,##0' uniquement pour l'affichage, pas de "Ar" ni de "$" dans le format
// (sinon PhpSpreadsheet peut renvoyer la chaîne formatée lors de la lecture).
$sheet->getStyle('F2:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
$sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Bordures
$sheet->getStyle('A1:J' . $lastRow)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)
    ->getColor()->setRGB('DEE2E6');

// Largeur des colonnes
$widths = ['A' => 8, 'B' => 25, 'C' => 18, 'D' => 25, 'E' => 35, 'F' => 18, 'G' => 18, 'H' => 15, 'I' => 22, 'J' => 15];
foreach ($widths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->freezePane('A2');

// --- Écriture du fichier ---
$filename = 'Comops_Test_Import.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✅ Fichier généré : " . __DIR__ . DIRECTORY_SEPARATOR . $filename . PHP_EOL;
echo "➡️  Colonnes I = Directeur des Travaux (texte), J = Date Paiement (date)." . PHP_EOL;
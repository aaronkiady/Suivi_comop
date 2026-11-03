<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Comop;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ComopExcelImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Importation du fichier Excel COMOP.
     */
    public function import(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
        } catch (\Throwable $e) {
            return [
                'success' => 0,
                'errors' => [
                    'Impossible de lire le fichier Excel : ' . $e->getMessage()
                ],
            ];
        }

        /*
         * 1. Sélection de la feuille COMOP 271026.
         */
        $sheet = $spreadsheet->getSheetByName('COMOP 271026');

        // Compatibilité avec les anciens fichiers
        if (!$sheet) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        /*
         * 2. Recherche automatique de la ligne des en-têtes.
         */
        $headerRow = $this->findHeaderRow($sheet);

        if ($headerRow === null) {
            return [
                'success' => 0,
                'errors' => [
                    'Impossible de trouver les en-têtes du fichier COMOP.'
                ],
            ];
        }

        /*
         * 3. Identification des colonnes.
         */
        $headers = [];

        foreach ($sheet->getRowIterator($headerRow, $headerRow) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $header = $this->normalizeHeader($cell->getValue());

                if ($header !== '') {
                    $headers[$header] = $cell->getColumn();
                }
            }
        }

        /*
         * 4. Correspondance des anciens et nouveaux intitulés.
         */
        $columns = [
            'projet' => [
                'projet',
            ],

            'refBc' => [
                'refbcqu',
                'refbc',
                'refbondecommande',
            ],

            'site' => [
                'site',
            ],

            'fournisseur' => [
                'fournisseur',
                'fournisseurs',
            ],

            'designation' => [
                'lignesdecommande',
                'designations',
                'designation',
            ],

            'montant' => [
                'montant',
            ],

            'montantUsd' => [
                'montantusd',
            ],

            'devise' => [
                'devise',
            ],

            'montantPaye' => [
                'montantdejaepaye',
                'payementdeja fait',
                'paiementdeja fait',
                'montantpaye',
            ],

            'statut' => [
                'statut',
            ],

            'resteAPayer' => [
                'resteapayer',
            ],

            'dt' => [
                'dt',
            ],

            'datePaiement' => [
                'datepaiement',
            ],
        ];

        $mappedColumns = [];

        foreach ($columns as $field => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizeHeader($alias);

                if (isset($headers[$normalizedAlias])) {
                    $mappedColumns[$field] = $headers[$normalizedAlias];
                    break;
                }
            }
        }

        /*
         * 5. Vérification des colonnes obligatoires.
         */
        $required = [
            'projet',
            'refBc',
        ];

        foreach ($required as $field) {
            if (!isset($mappedColumns[$field])) {
                return [
                    'success' => 0,
                    'errors' => [
                        "Colonne obligatoire introuvable : $field"
                    ],
                ];
            }
        }

        /*
         * 6. Importation des lignes.
         */
        $successCount = 0;
        $errors = [];

        $highestRow = $sheet->getHighestRow();

        for ($rowIndex = $headerRow + 1; $rowIndex <= $highestRow; $rowIndex++) {

            try {

                // Récupération des valeurs par nom de colonne
                $get = function (string $field) use (
                    $sheet,
                    $mappedColumns,
                    $rowIndex
                ) {
                    if (!isset($mappedColumns[$field])) {
                        return null;
                    }

                    return $this->getCellValue(
                        $sheet,
                        $mappedColumns[$field],
                        $rowIndex
                    );
                };

                /*
                 * Ignorer les lignes complètement vides.
                 */
                $rowValues = [];

                foreach ($mappedColumns as $field => $column) {
                    $rowValues[] = $get($field);
                }

                if (empty(array_filter(
                    $rowValues,
                    static fn ($value) => $value !== null && $value !== ''
                ))) {
                    continue;
                }

                /*
                 * Projet et référence BC.
                 */
                $projet = $this->cleanString($get('projet'));
                $refBc = $this->cleanString($get('refBc'));

                /*
                 * Le projet peut être absent dans certaines lignes
                 * du nouveau fichier.
                 */
                if (!$projet) {
                    $projet = 'Non renseigné';
                }

                if (!$refBc) {
                    $refBc = 'Non renseigné';
                }

                /*
                 * Une référence BC reste obligatoire dans l'entité.
                 * On ne fabrique pas de fausses références.
                 */
                if (!$refBc) {
                    $errors[] =
                        "Ligne $rowIndex : Référence BC absente. Ligne ignorée.";

                    continue;
                }

                /*
                 * Création de l'entité.
                 */
                $comop = new Comop();

                $comop->setProjet($projet);
                $comop->setRefBc($refBc);

                /*
                 * Fournisseur.
                 */
                $comop->setFournisseur(
                    $this->cleanString($get('fournisseur'))
                );

                /*
                 * SITE + Désignation.
                 *
                 * L'entité actuelle ne possède pas de champ SITE.
                 * On conserve donc cette information dans la désignation.
                 */
                $site = $this->cleanString($get('site'));

                $designation = $this->cleanString(
                    $get('designation')
                );

                if ($site !== null) {
                    $designation = '[SITE : ' . $site . '] '
                        . ($designation ?? '');
                }

                $comop->setDesignation($designation);

                /*
                 * DEVISE.
                 *
                 * MGA par défaut lorsque la cellule est vide.
                 */
                $deviseExcel = $this->cleanString($get('devise'));

                $devise = $this->detectDevise($deviseExcel);

                $comop->setDevise($devise);

                /*
                 * MONTANT.
                 *
                 * getCalculatedValue() permet notamment de récupérer
                 * les résultats des formules Excel dans la colonne F.
                 */
                $montantExcel = $get('montant');

                /*
                 * Cas particulier :
                 * certaines lignes contiennent une valeur comme
                 * "376,20 $" dans la colonne DEVISE et aucun montant
                 * dans la colonne MONTANT.
                 */
                if (
                    ($montantExcel === null || $montantExcel === '')
                    && $deviseExcel !== null
                    && $this->containsCurrencyAmount($deviseExcel)
                ) {
                    $montantExcel = $deviseExcel;
                }

                $montant = $this->toInt($montantExcel);

                $comop->setMontant($montant);

                /*
                 * MONTANT USD.
                 *
                 * Ancienne colonne conservée pour compatibilité.
                 */
                $montantUsd = $this->toInt($get('montantUsd'));

                $comop->setMontantUsd($montantUsd);

                /*
                 * MONTANT DÉJÀ PAYÉ.
                 */
                $montantPaye = $this->toInt(
                    $get('montantPaye')
                );

                $comop->setMontantPaye($montantPaye ?? 0);

                /*
                 * RESTE À PAYER.
                 *
                 * Il n'est pas nécessaire d'importer la formule
                 * Excel de la colonne J.
                 *
                 * L'entité calcule automatiquement :
                 * montant - montantPaye.
                 *
                 * La valeur est bornée à zéro.
                 */

                /*
                 * STATUT.
                 */
                $comop->setStatut(
                    $this->cleanString($get('statut'))
                );

                /*
                 * DIRECTEUR DES TRAVAUX.
                 */
                $comop->setDt(
                    $this->cleanString($get('dt'))
                );

                /*
                 * DATE DE PAIEMENT.
                 *
                 * Cette colonne existe dans les anciens modèles,
                 * mais pas dans COMOP 271026.
                 */
                $datePaiement = $get('datePaiement');

                if ($datePaiement !== null && $datePaiement !== '') {

                    $date = $this->parseExcelDate($datePaiement);

                    if ($date === null) {
                        $errors[] =
                            "Ligne $rowIndex : Date de paiement invalide.";

                        continue;
                    }

                    $comop->setDatePaiement($date);
                }

                /*
                 * Enregistrement.
                 */
                $this->entityManager->persist($comop);

                $successCount++;

            } catch (\Throwable $e) {

                $errors[] =
                    "Ligne $rowIndex : " . $e->getMessage();
            }
        }

        /*
         * 7. Enregistrement en base de données.
         */
        try {

            $this->entityManager->flush();

        } catch (\Throwable $e) {

            return [
                'success' => 0,
                'errors' => [
                    'Erreur lors de l\'enregistrement en base de données : '
                    . $e->getMessage()
                ],
            ];
        }

        /*
         * 8. Résultat de l'importation.
         */
        return [
            'success' => $successCount,
            'errors' => $errors,
        ];
    }

    /**
     * Recherche de la ligne contenant les en-têtes.
     */
    private function findHeaderRow(Worksheet $sheet): ?int
    {
        $maxRows = min(15, $sheet->getHighestRow());

        for ($row = 1; $row <= $maxRows; $row++) {

            $values = [];

            foreach ($sheet->getRowIterator($row, $row) as $excelRow) {
                foreach ($excelRow->getCellIterator() as $cell) {
                    $values[] = $this->normalizeHeader(
                        $cell->getValue()
                    );
                }
            }

            $hasProjet = in_array('projet', $values, true);

            $hasRef = (
                in_array('refbcqu', $values, true)
                || in_array('refbc', $values, true)
            );

            if ($hasProjet && $hasRef) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Récupération d'une cellule Excel.
     *
     * Les formules sont calculées lorsque possible.
     */
    private function getCellValue(
        Worksheet $sheet,
        string $column,
        int $row
    ): mixed {
        $cell = $sheet->getCell($column . $row);

        try {
            $value = $cell->getCalculatedValue();

            if ($value !== null && !is_string($value)) {
                return $value;
            }

            if (is_string($value) && !str_starts_with($value, '=')) {
                return $value;
            }

        } catch (\Throwable $e) {
            // Utilisation de la valeur brute en cas d'échec.
        }

        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            return null;
        }

        return $value;
    }

    /**
     * Normalisation des intitulés Excel.
     */
    private function normalizeHeader(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        $value = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $value
        );

        return preg_replace('/[^a-z0-9]/', '', $value);
    }

    /**
     * Nettoyage des chaînes.
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
     * Détection de la devise.
     */
    private function detectDevise(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return 'MGA';
        }

        $value = mb_strtoupper(trim($value));

        if (
            str_contains($value, 'USD')
            || str_contains($value, '$')
        ) {
            return 'USD';
        }

        if (
            str_contains($value, 'EUR')
            || str_contains($value, '€')
        ) {
            return 'EUR';
        }

        if (
            str_contains($value, 'MGA')
            || str_contains($value, 'ARIARY')
            || str_contains($value, 'AR')
        ) {
            return 'MGA';
        }

        return 'MGA';
    }

    /**
     * Détermine si la cellule contient un montant avec devise.
     */
    private function containsCurrencyAmount(string $value): bool
    {
        return preg_match(
            '/[0-9].*(\$|€|USD|EUR)/iu',
            $value
        ) === 1;
    }

    /**
     * Conversion des montants en entier.
     *
     * Gère notamment :
     * 12 500 000
     * 12,500,000
     * 12 500 000,50
     * 376,20 $
     * 3196000.27
     */
    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $value = trim((string) $value);

        // Suppression des espaces et symboles monétaires.
        $value = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $value);

        $value = preg_replace('/[^0-9,.\-]/u', '', $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        $comma = strrpos($value, ',');
        $dot = strrpos($value, '.');

        if ($comma !== false && $dot !== false) {

            // Le dernier séparateur est considéré comme décimal.
            if ($comma > $dot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }

        } elseif ($comma !== false) {

            // Une ou deux décimales après la virgule.
            if (preg_match('/,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }

        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }

    /**
     * Conversion d'une date Excel ou d'une date texte.
     */
    private function parseExcelDate(mixed $value): ?\DateTimeInterface
    {
        try {

            if ($value instanceof \DateTimeInterface) {
                return \DateTime::createFromInterface($value);
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject(
                    (float) $value
                );
            }

            return new \DateTime((string) $value);

        } catch (\Throwable $e) {

            return null;
        }
    }
}

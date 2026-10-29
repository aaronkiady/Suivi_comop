<?php

namespace App\Controller;

use App\Entity\PaiementDispo;
use App\Form\PaiementDispoType;
use App\Repository\PaiementDispoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\PaiementDispoImportType;
use App\Service\PaiementDispoExcelExporter;
use App\Service\PaiementDispoExcelImporter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/paiement/dispo')]
final class PaiementDispoController extends AbstractController
{
    #[Route(name: 'app_paiement_dispo_index', methods: ['GET'])]
    public function index(PaiementDispoRepository $paiementDispoRepository): Response
    {
        return $this->render('paiement_dispo/index.html.twig', [
            'paiement_dispos' => $paiementDispoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_paiement_dispo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $paiementDispo = new PaiementDispo();
        $form = $this->createForm(PaiementDispoType::class, $paiementDispo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('piecesJointesFile')->getData();
            if ($files) {
                // On récupère les fichiers déjà existants (utile pour l'edit)
                $filenames = $paiementDispo->getPiecesJointes() ?? [];
                
                foreach ($files as $file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                    try {
                        // On déplace le fichier physique dans le dossier public/uploads
                        $file->move($this->getParameter('uploads_directory'), $newFilename);
                        $filenames[] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors du téléchargement du fichier : ' . $originalFilename);
                    }
                }
                
                // On sauvegarde le tableau mis à jour
                $paiementDispo->setPiecesJointes($filenames);
            }

            $entityManager->persist($paiementDispo);
            $entityManager->flush();

            return $this->redirectToRoute('app_paiement_dispo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement_dispo/new.html.twig', [
            'paiement_dispo' => $paiementDispo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_dispo_show', methods: ['GET'])]
    public function show(PaiementDispo $paiementDispo): Response
    {
        return $this->render('paiement_dispo/show.html.twig', [
            'paiement_dispo' => $paiementDispo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paiement_dispo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PaiementDispo $paiementDispo, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PaiementDispoType::class, $paiementDispo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        // GESTION DES PIÈCES JOINTES (Upload Multiple)
        $files = $form->get('piecesJointesFile')->getData();
        if ($files) {
            // On récupère les fichiers déjà existants (utile pour l'edit)
            $filenames = $paiementDispo->getPiecesJointes() ?? [];
            
            foreach ($files as $file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    // On déplace le fichier physique dans le dossier public/uploads
                    $file->move($this->getParameter('uploads_directory'), $newFilename);
                    $filenames[] = $newFilename;
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement du fichier : ' . $originalFilename);
                }
            }
            
            // On sauvegarde le tableau mis à jour
            $paiementDispo->setPiecesJointes($filenames);
        }

        $entityManager->persist($paiementDispo);
        $entityManager->flush();

            return $this->redirectToRoute('app_paiement_dispo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement_dispo/edit.html.twig', [
            'paiement_dispo' => $paiementDispo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_dispo_delete', methods: ['POST'])]
    public function delete(Request $request, PaiementDispo $paiementDispo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paiementDispo->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paiementDispo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_paiement_dispo_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/paiement-dispo/export/excel', name: 'app_paiement_dispo_export_excel', methods: ['GET'])]
    public function exportExcel(PaiementDispoRepository $repo, PaiementDispoExcelExporter $exporter): StreamedResponse
    {
        return $exporter->export($repo->findAll());
    }

    #[Route('/paiement-dispo/import/excel', name: 'app_paiement_dispo_import_excel', methods: ['GET', 'POST'])]
    public function importExcel(
        Request $request,
        PaiementDispoExcelImporter $importer
    ): Response {
        $form = $this->createForm(PaiementDispoImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $report = $importer->import($file);

            if ($report['success'] > 0) {
                $this->addFlash('success', sprintf('%d paiement(s) importé(s) avec succès.', $report['success']));
            }

            if (!empty($report['errors'])) {
                $msg = 'Erreurs rencontrées :<br><ul class="mb-0 mt-1">';
                foreach ($report['errors'] as $err) {
                    $msg .= '<li>' . htmlspecialchars($err) . '</li>';
                }
                $msg .= '</ul>';
                $this->addFlash('danger', $msg);
            }

            return $this->redirectToRoute('app_paiement_dispo_index');
        }

        return $this->render('paiement_dispo/import.html.twig', ['form' => $form]);
    }

    #[Route('/paiement-dispo/import/template', name: 'app_paiement_dispo_import_template', methods: ['GET'])]
    public function downloadImportTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Import');

        $headers = ['ID', 'Date Etablissement', 'Type Paiement', 'Banque', 'Société', 'Référence', 'Échéance Traite', 'Fournisseurs', 'Ariary', 'Euro', 'Dollar', 'Statut Swift', 'Statut Paiement', 'Pièces BC'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B4F82']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '143C64']]],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Exemple
        $example = ['', '2024-01-15', 'Virement', 'BNI', 'OTI', 'PAY-2024-001', '30 jours', 'BTP Madagascar', 15000000, 3200, 3450, 'Reçu', 'Payé', 'BC-2024-001'];
        $sheet->fromArray($example, null, 'A2');

        $sheet->getStyle('A2:N2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6C757D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7F6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_DASHED, 'color' => ['rgb' => 'DEE2E6']]],
        ]);

        // Formats
        $sheet->getStyle('I3:K1000')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B3:B1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        $widths = ['A' => 8, 'B' => 18, 'C' => 18, 'D' => 15, 'E' => 18, 'F' => 20, 'G' => 15, 'H' => 22, 'I' => 15, 'J' => 15, 'K' => 15, 'L' => 15, 'M' => 15, 'N' => 18];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(fn() => $writer->save('php://output'));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="Modele_Import_Paiements.xlsx"');

        return $response;
    }

    #[Route('/paiement-dispo/{id}/remove-file/{filename}', name: 'app_paiement_dispo_remove_file', methods: ['POST'])]
    public function removeFile(
        PaiementDispo $paiementDispo,
        string $filename,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('delete_file_' . $filename, $request->getPayload()->getString('_token'))) {
            // Supprimer du disque
            $filePath = $this->getParameter('uploads_directory') . '/' . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Supprimer du tableau JSON
            $paiementDispo->removePieceJointe($filename);
            $em->flush();

            $this->addFlash('success', 'Fichier supprimé avec succès.');
        }

        return $this->redirectToRoute('app_paiement_dispo_show', ['id' => $paiementDispo->getId()]);
    }
}

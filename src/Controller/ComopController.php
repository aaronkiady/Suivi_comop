<?php

namespace App\Controller;

use App\Entity\Comop;
use App\Form\ComopType;
use App\Repository\ComopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\ComopExcelExporter;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ComopImportType;
use App\Service\ComopExcelImporter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

//#[Route('/comop')]
final class ComopController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('app_comop_index');
    }

    #[Route('comop/index', name: 'app_comop_index', methods: ['GET'])]
    public function index(ComopRepository $comopRepository): Response
    {
        return $this->render('comop/index.html.twig', [
            'comops' => $comopRepository->findAll(),
        ]);
    }

    #[Route('comop/new', name: 'app_comop_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $comop = new Comop();

        // On vérifie si l'utilisateur a le droit de modifier le statut
        $canEditStatut = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_FINANCE');

        $form = $this->createForm(ComopType::class, $comop, [
            'can_edit_statut' => $canEditStatut,
        ]);

        //$form = $this->createForm(ComopType::class, $comop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $files = $form->get('piecesJointesFile')->getData();
            if ($files) {
                // On récupère les fichiers déjà existants (utile pour l'edit)
                $filenames = $comop->getPiecesJointes() ?? [];
                
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
                $comop->setPiecesJointes($filenames);
            }

            $entityManager->persist($comop);
            $entityManager->flush();

            $entityManager->persist($comop);
            $entityManager->flush();

            return $this->redirectToRoute('app_comop_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comop/new.html.twig', [
            'comop' => $comop,
            'form' => $form,
        ]);
    }

    #[Route('comop/{id}', name: 'app_comop_show', methods: ['GET'])]
    public function show(Comop $comop): Response
    {
        return $this->render('comop/show.html.twig', [
            'comop' => $comop,
        ]);
    }

    #[Route('comop/{id}/edit', name: 'app_comop_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comop $comop, EntityManagerInterface $entityManager): Response
    {

        $canEditStatut = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_FINANCE');

        $form = $this->createForm(ComopType::class, $comop, [
            'can_edit_statut' => $canEditStatut,
        ]);

        //$form = $this->createForm(ComopType::class, $comop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_comop_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comop/edit.html.twig', [
            'comop' => $comop,
            'form' => $form,
        ]);
    }

    #[Route('comop/{id}', name: 'app_comop_delete', methods: ['POST'])]
    public function delete(Request $request, Comop $comop, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comop->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comop);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comop_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('comop/export/excel', name: 'app_comop_export_excel', methods: ['GET'])]
    public function exportExcel(ComopRepository $comopRepository, ComopExcelExporter $exporter): Response
    {
        $comops = $comopRepository->findAll();
        
        return $exporter->export($comops);
    }

    #[Route('comop/import/excel', name: 'app_comop_import_excel', methods: ['GET', 'POST'])]
    public function importExcel(
        Request $request,
        ComopExcelImporter $importer
    ): Response {
        $form = $this->createForm(ComopImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            $report = $importer->import($file);

            // Message flash basé sur le résultat
            if ($report['success'] > 0) {
                $this->addFlash('success', sprintf(
                    '%d ligne(s) importée(s) avec succès.',
                    $report['success']
                ));
            }

            if (!empty($report['errors'])) {
                $errorMessage = 'Erreurs rencontrées :<br><ul class="mb-0 mt-1">';
                foreach ($report['errors'] as $error) {
                    $errorMessage .= '<li>' . htmlspecialchars($error) . '</li>';
                }
                $errorMessage .= '</ul>';
                $this->addFlash('danger', $errorMessage);
            }

            return $this->redirectToRoute('app_comop_index');
        }

        return $this->render('comop/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/comop/{id}/remove-file/{filename}', name: 'app_comop_remove_file', methods: ['POST'])]
    public function removeFile(
        Comop $comop,
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
            $comop->removePieceJointe($filename);
            $em->flush();

            $this->addFlash('success', 'Fichier supprimé avec succès.');
        }

        return $this->redirectToRoute('app_comop_show', ['id' => $comop->getId()]);
    }
}

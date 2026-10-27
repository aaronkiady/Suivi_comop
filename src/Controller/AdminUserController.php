<?php
// src/Controller/AdminUserController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $selectedRole = $form->get('roles')->getData();
            $user->setRoles([$selectedRole]);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
        
        // On pré-remplit le champ de choix du rôle avec le rôle actuel de l'utilisateur
        $currentRole = $user->getRoles()[0] ?? 'ROLE_USER';
        $form->get('roles')->setData($currentRole);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe est saisi, on le met à jour
            $newPassword = $form->get('plainPassword')->getData();
            if ($newPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            // Mise à jour du rôle
            $selectedRole = $form->get('roles')->getData();
            $user->setRoles([$selectedRole]);

            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
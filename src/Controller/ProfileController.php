<?php
// src/Controller/ProfileController.php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        // --- Formulaire infos personnelles ---
        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_profile');
        }

        // --- Formulaire mot de passe ---
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $oldPassword = $passwordForm->get('oldPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $em->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
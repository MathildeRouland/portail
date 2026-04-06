<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use App\Form\PasswordChangeFormType;
use App\Form\UserType;
use Symfony\Component\Form\FormError;

final class UserController extends AbstractController
{
    // #[Route('/user', name: 'app_user')]
    // public function index(): JsonResponse
    // {
    //     return $this->json([
    //         'message' => 'Welcome to your new controller!',
    //         'path' => 'src/Controller/UserController.php',
    //     ]);
    // }
    

    #[Route('/users', name: 'user_browse')]
    public function browse(UserRepository $userRepository): Response
{
     // Récupérer tous les utilisateurs
     $users = $userRepository->findAll();

     // Passer les utilisateurs au template
     return $this->render('user/browse.html.twig', [
         'users' => $users,
    ]);
}

#[Route('/users/create', name: 'user_create')]
public function createUser(
    Request $request,
    UserRepository $userRepository,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager,
    CsrfTokenManagerInterface $csrfTokenManager
): Response {
    // Vérification que seul un ROLE_SUPER_ADMIN peut créer des utilisateurs
    if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
        throw new AccessDeniedHttpException('Seuls les super administrateurs peuvent créer des utilisateurs.');
    }
    
    $user = new User();
    $form = $this->createForm(UserType::class, $user, [
    'current_user' => $this->getUser(),
    'validation_groups' => ['create'],   
]);
    
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        // Vérification explicite du token CSRF
        $submittedToken = $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('create_user', $submittedToken))) {
            throw new \RuntimeException('Jeton CSRF invalide.');
        }
        
        // Hash du mot de passe
        $plainPassword = $form->get('password')->getData();
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        // Récupérer le rôle sélectionné
        $role = $form->get('roles')->getData();
        $user->setRoles([$role]);
        
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur "' . $user->getUsername() . '" créé avec succès avec le rôle ' . $role);
        return $this->redirectToRoute('user_browse');
    }

    return $this->render('user/create.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/users/{id}/edit', name: 'user_edit')]
public function updateUser(Request $request, EntityManagerInterface $em, Security $security,  UserPasswordHasherInterface $passwordHasher, int $id): Response
{
    // Récupérer l'utilisateur à modifier
    $userToUpdate = $em->getRepository(User::class)->find($id);
    if (!$userToUpdate) {
        throw $this->createNotFoundException('Utilisateur non trouvé.');
    }

    // Vérification que seul un ROLE_SUPER_ADMIN peut modifier des utilisateurs
    if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
        throw new AccessDeniedHttpException('Seuls les super administrateurs peuvent modifier des utilisateurs.');
    }

    $form = $this->createForm(\App\Form\UserEditType::class, $userToUpdate, [
        'current_user' => $this->getUser(),
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Hash du mot de passe si un nouveau mot de passe est fourni
        $plainPassword = $form->get('password')->getData();
        if ($plainPassword) {
        $hashedPassword = $passwordHasher->hashPassword($userToUpdate, $plainPassword);
        $userToUpdate->setPassword($hashedPassword);
        }

        // Récupérer le rôle sélectionné
        $role = $form->get('roles')->getData();
        $userToUpdate->setRoles([$role]);

        $em->flush();

        $this->addFlash('success', 'Utilisateur "' . $userToUpdate->getUsername() . '" mis à jour avec succès avec le rôle ' . $role);
        return $this->redirectToRoute('user_browse');
    }

    return $this->render('user/edit.html.twig', [
        'form' => $form->createView(),
        'user' => $userToUpdate,
    ]);
}
    #[Route('/password/change', name: 'user_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(PasswordChangeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Vérification du mot de passe actuel
            if (!$passwordHasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                $form->get('currentPassword')->addError(
                    new FormError('Mot de passe actuel incorrect.')
                );
            } else {

                // Mise à jour du mot de passe
                $newHashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('newPassword')->getData()
                );

                $user->setPassword($newHashedPassword);
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');

                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $userToDelete = $em->getRepository(User::class)->find($id);
        if (!$userToDelete) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedHttpException('Seuls les super administrateurs peuvent supprimer des utilisateurs.');
        }

        if ($this->isCsrfTokenValid('delete-user-' . $userToDelete->getId(), $request->request->get('_token'))) {
            $em->remove($userToDelete);
            $em->flush();

            $this->addFlash('success', 'Utilisateur "' . $userToDelete->getUsername() . '" supprimé avec succès.');
        }

        return $this->redirectToRoute('user_browse');
    }
}
<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Entity\User;
use App\Service\LoggerHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
    CsrfTokenManagerInterface $csrfTokenManager,
    #[Autowire(service: 'monolog.logger.crud')]
    LoggerInterface $logger
): Response {
    $loggerHelper = new LoggerHelper($logger);
    
    // Vérification que seul un ROLE_SUPER_ADMIN peut créer des utilisateurs
    try {
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
            $loggerHelper->logInfo('Utilisateur créé', ['username' => $user->getUsername(), 'role' => $role]);
            return $this->redirectToRoute('user_browse');
        }

        return $this->render('user/create.html.twig', [
            'form' => $form->createView(),
        ]);
    } catch (\Exception $e) {
        $errorMsg = 'Erreur lors de la création utilisateur: ' . $e->getMessage();
        $this->addFlash('error', $errorMsg);
        $loggerHelper->logError('Erreur lors de la création utilisateur', $e);
        return $this->redirectToRoute('user_browse');
    }
}

#[Route('/users/{id}/edit', name: 'user_edit')]
public function updateUser(Request $request, EntityManagerInterface $em, Security $security,  UserPasswordHasherInterface $passwordHasher, #[Autowire(service: 'monolog.logger.crud')] LoggerInterface $logger, int $id): Response
{
    $loggerHelper = new LoggerHelper($logger);
    
    try {
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
            $loggerHelper->logInfo('Utilisateur modifié', ['id' => $id, 'username' => $userToUpdate->getUsername(), 'role' => $role]);
            return $this->redirectToRoute('user_browse');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $userToUpdate,
        ]);
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la modification utilisateur');
        $loggerHelper->logError('Erreur lors de la modification utilisateur', $e, ['userId' => $id]);
        return $this->redirectToRoute('user_browse');
    }
}
    #[Route('/password/change', name: 'user_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'monolog.logger.crud')]
        LoggerInterface $logger,
    ): Response {
        $loggerHelper = new LoggerHelper($logger);
        
        try {
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
                    $loggerHelper->logInfo('Changement de mot de passe', ['userId' => $user->getId(), 'username' => $user->getUsername()]);

                    return $this->redirectToRoute('homepage');
                }
            }

            return $this->render('user/change_password.html.twig', [
                'form' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du changement de mot de passe');
            $loggerHelper->logError('Erreur lors du changement de mot de passe', $e);
            return $this->redirectToRoute('homepage');
        }
    }

    #[Route('/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, EntityManagerInterface $em, #[Autowire(service: 'monolog.logger.crud')] LoggerInterface $logger, int $id): Response
    {
        $loggerHelper = new LoggerHelper($logger);
        
        try {
            $userToDelete = $em->getRepository(User::class)->find($id);
            if (!$userToDelete) {
                throw $this->createNotFoundException('Utilisateur non trouvé.');
            }

            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                throw new AccessDeniedHttpException('Seuls les super administrateurs peuvent supprimer des utilisateurs.');
            }

            if ($this->isCsrfTokenValid('delete-user-' . $userToDelete->getId(), $request->request->get('_token'))) {
                $username = $userToDelete->getUsername();
                $em->remove($userToDelete);
                $em->flush();

                $this->addFlash('success', 'Utilisateur "' . $username . '" supprimé avec succès.');
                $loggerHelper->logInfo('Utilisateur supprimé', ['id' => $id, 'username' => $username]);
            }

            return $this->redirectToRoute('user_browse');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression utilisateur');
            $loggerHelper->logError('Erreur lors de la suppression utilisateur', $e, ['userId' => $id]);
            return $this->redirectToRoute('user_browse');
        }
    }
}
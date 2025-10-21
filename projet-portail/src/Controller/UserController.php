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
    #[Route('/', name: 'homepage')]
    public function home ()
{
     // Passer les utilisateurs au template
     return $this->render('homepage.html.twig');
}

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
    $form = $this->createForm(\App\Form\UserType::class, $user, [
        'current_user' => $this->getUser(),
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
public function updateUser(Request $request, EntityManagerInterface $em, Security $security, int $id): Response
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
        //$plainPassword = $form->get('password')->getData();
        //if ($plainPassword) {
          //  $hashedPassword = $passwordHasher->hashPassword($userToUpdate, $plainPassword);
            //$userToUpdate->setPassword($hashedPassword);
        //}

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
}
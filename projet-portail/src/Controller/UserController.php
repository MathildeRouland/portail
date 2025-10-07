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

    #[Route('/users', name: 'user_browse')]
    public function browse(UserRepository $userRepository): Response
{
     // R�cup�rer tous les utilisateurs
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
    CsrfTokenManagerInterface $csrfTokenManager,
    EntityManagerInterface $entityManager
): Response {
    if ($request->isMethod('POST')) {
        $submittedToken = $request->request->get('_csrf_token');

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('create_user', $submittedToken))) {
            throw new \RuntimeException('Jeton CSRF invalide.');
        }

        $email = $request->request->get('email');
        $plainPassword = $request->request->get('password');
        $username = $request->request->get('username');

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return new Response('Utilisateur cr�� avec succ�s.');
    }

    return $this->render('user/create.html.twig');
}

}

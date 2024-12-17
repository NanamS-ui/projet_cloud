<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\GenderRepository;
use App\Repository\RoleRepository;

class UsersController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UsersRepository $usersRepository;
    private RoleRepository $roleRepository;
    private GenderRepository $genderRepository;

    public function __construct(EntityManagerInterface $entityManager, UsersRepository $usersRepository, RoleRepository $roleRepository, GenderRepository $genderRepository)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->roleRepository = $roleRepository;
        $this->genderRepository = $genderRepository;
    }

    // 1. GET - Liste de tous les utilisateurs
    #[Route('/api/users', name: 'list_users', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->usersRepository->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getUserId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'birthDate' => $user->getBirthDate()->format('Y-m-d'),
                'role' => $user->getRole()->getName(),
                'gender' => $user->getGender()->getName(),
            ];
        }

        return $this->json($data);
    }

    // 2. GET - Détail d'un utilisateur par ID
    #[Route('/api/user/{id}', name: 'show_users', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->usersRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $data = [
            'id' => $user->getUserId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'role' => $user->getRole()->getName(),
            'gender' => $user->getGender()->getName(),
        ];

        return $this->json($data);
    }

    // 3. POST - Créer un nouvel utilisateur
    #[Route('/api/user', name: 'create_users', methods: ['POST'])]
    public function create(Request $request, SessionInterface $session): JsonResponse
    {
        $userData = $session->get('user');

        if (!$userData) {
            return $this->json(['message' => 'No user data in session'], 400);
        }

        $user = new Users();
        $user->setUsername($userData['username']);
        $user->setEmail($userData['email']);

        $hashedPassword = md5($userData['password']);
        $user->setPassword($hashedPassword);

        $user->setBirthDate(new \DateTime($userData['birthDate']));

        $roleId = $userData['role'] ?? 1;
        $role = $this->roleRepository->find($roleId);

        if (!$role) {
            $role = $this->roleRepository->find(1);
        }

        $gender = $this->genderRepository->find($userData['gender']);

        $user->setRole($role);
        $user->setGender($gender);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $session->remove('user');

        return $this->json([
            'message' => 'User created successfully from session',
            'user' => [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'birthDate' => $user->getBirthDate()->format('Y-m-d')
            ]
        ], 201);
    }

    // 4. PUT - Modifier un utilisateur existant
    #[Route('/api/user/{id}', name: 'update_users', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->usersRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }
        if (isset($data['birthDate'])) {
            $user->setBirthDate(new \DateTime($data['birthDate']));
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'User updated successfully']);
    }

    // 5. DELETE - Supprimer un utilisateur
    #[Route('/api/user/{id}', name: 'delete_users', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->usersRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }
}

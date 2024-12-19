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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        summary: "Récupérer les détails d'un utilisateur",
        description: "Permet de récupérer les informations d'un utilisateur spécifique par son ID.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "L'identifiant unique de l'utilisateur"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de l'utilisateur",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "username", type: "string"),
                            new OA\Property(property: "email", type: "string"),
                            new OA\Property(property: "birthDate", type: "string", format: "date"),
                            new OA\Property(property: "role", type: "string"),
                            new OA\Property(property: "gender", type: "string"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Utilisateur non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "User not found")
                        ]
                    )
                )
            )
        ]
    )]
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
    #[OA\Post(
        summary: "Créer un utilisateur",
        description: "Cette route permet de créer un nouvel utilisateur en utilisant les données stockées en session.",
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur créé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "User created successfully from session"),
                            new OA\Property(
                                property: "user",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "username", type: "string"),
                                    new OA\Property(property: "email", type: "string"),
                                    new OA\Property(property: "birthDate", type: "string", format: "date"),
                                ]
                            ),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Données utilisateur absentes dans la session",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "No user data in session")
                        ]
                    )
                )
            )
        ]
    )]
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
    #[OA\Put(
        summary: "Mettre à jour un utilisateur",
        description: "Permet de mettre à jour un utilisateur existant en fonction des données fournies.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "L'identifiant unique de l'utilisateur à modifier"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur mis à jour avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "User updated successfully")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Utilisateur non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "User not found")
                        ]
                    )
                )
            )
        ]
    )]
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
    #[OA\Delete(
        summary: "Supprimer un utilisateur",
        description: "Permet de supprimer un utilisateur spécifique par son ID.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "L'identifiant unique de l'utilisateur à supprimer"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur supprimé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "User deleted successfully")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Utilisateur non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "User not found")
                        ]
                    )
                )
            )
        ]
    )]
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

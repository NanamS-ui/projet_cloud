<?php

namespace App\Controller;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Attributes as OA;

class RoleController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RoleRepository $roleRepository;

    public function __construct(EntityManagerInterface $entityManager, RoleRepository $roleRepository)
    {
        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
    }

    // Méthode privée pour récupérer un rôle ou lever une exception 404
    private function findRoleOr404(int $id): Role
    {
        $role = $this->roleRepository->find($id);

        if (!$role) {
            throw $this->createNotFoundException("Rôle non trouvé");
        }

        return $role;
    }

    #[Route('/api/roles', name: 'index_roles', methods: ['GET'])]
    #[OA\Get(
        summary: "Liste tous les rôles",
        description: "Cette route retourne tous les rôles disponibles dans la base de données."
    )]
    #[OA\Response(response: 200, description: "Liste des rôles retournée avec succès")]
    public function index(): JsonResponse
    {
        $roles = $this->roleRepository->findAll();

        $data = array_map(fn($role) => [
            'roleId' => $role->getRoleId(),
            'name' => $role->getName(),
        ], $roles);

        return $this->json($data);
    }

    #[Route('/api/role', name: 'create_roles', methods: ['POST'])]
    #[OA\Post(
        summary: "Créer un rôle",
        description: "Cette route permet de créer un nouveau rôle dans le système."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object",
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nom du rôle")
                    ]
                )
            )
    )]
    #[OA\Response(response: 201, description: "Rôle créé avec succès")]
    #[OA\Response(response: 400, description: "Paramètre manquant ou invalide")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['message' => 'Le nom est requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $role = new Role();
        $role->setName($data['name']);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $this->json(['message' => 'Rôle créé avec succès'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/role/{id}', name: 'show_roles_id', methods: ['GET'])]
    #[OA\Get(
        summary: "Afficher les détails d'un rôle",
        description: "Cette route retourne les informations d'un rôle spécifique."
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID du rôle",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 200, description: "Détails du rôle retournés avec succès")]
    #[OA\Response(response: 404, description: "Rôle non trouvé")]
    public function show(int $id): JsonResponse
    {
        $role = $this->findRoleOr404($id);

        $data = [
            'roleId' => $role->getRoleId(),
            'name' => $role->getName(),
        ];

        return $this->json($data);
    }

    #[Route('/api/role/{id}', name: 'update_roles', methods: ['PUT'])]
    #[OA\Put(
        summary: "Mettre à jour un rôle",
        description: "Cette route permet de mettre à jour les informations d'un rôle existant."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nom du rôle")
                    ]
                )
            )
    )]
    #[OA\Response(response: 200, description: "Rôle mis à jour avec succès")]
    #[OA\Response(response: 404, description: "Rôle non trouvé")]
    public function update(int $id, Request $request): JsonResponse
    {
        $role = $this->findRoleOr404($id);

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $role->setName($data['name']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Rôle mis à jour avec succès']);
    }

    #[Route('/api/role/{id}', name: 'delete_roles', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Supprimer un rôle",
        description: "Cette route permet de supprimer un rôle de la base de données."
    )]
    #[OA\Response(response: 200, description: "Rôle supprimé avec succès")]
    #[OA\Response(response: 404, description: "Rôle non trouvé")]
    public function delete(int $id): JsonResponse
    {
        $role = $this->findRoleOr404($id);

        $this->entityManager->remove($role);
        $this->entityManager->flush();

        return $this->json(['message' => 'Rôle supprimé avec succès']);
    }
}

<?php

namespace App\Controller;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RoleController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RoleRepository $roleRepository;

    public function __construct(EntityManagerInterface $entityManager, RoleRepository $roleRepository)
    {
        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
    }

    // Méthode privée pour récupérer un Role ou lever une exception 404
    private function findRoleOr404(int $id): Role
    {
        $role = $this->roleRepository->find($id);

        if (!$role) {
            throw $this->createNotFoundException("Role non trouvé");
        }

        return $role;
    }

    #[Route('/api/roles', name: 'index_roles', methods: ['GET'])]
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
    public function delete(int $id): JsonResponse
    {
        $role = $this->findRoleOr404($id);

        $this->entityManager->remove($role);
        $this->entityManager->flush();

        return $this->json(['message' => 'Rôle supprimé avec succès']);
    }
}

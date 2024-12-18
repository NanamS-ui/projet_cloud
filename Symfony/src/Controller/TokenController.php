<?php
namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\Util;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class TokenController extends AbstractController{
    private EntityManagerInterface $entityManager;
    private UsersRepository $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, UsersRepository $usersRepository)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
    }
    #[Route('/api/token', name: 'create_token_users', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['idUser'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $user = $this->usersRepository->find($data['idUser']);
        $user->setToken(Util::generateToken());
        $this->entityManager->flush();

        return $this->json(['message' => 'token created successfully'], 201);
    }
    #[Route('/api/token/{id}', name: 'delete_token_users', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->usersRepository->find($id);
        $user->setToken(null);
        $this->entityManager->flush();

        return $this->json(['message' => 'token deleted successfully'], 201);
    }
}
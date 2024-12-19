<?php

namespace App\Controller;

use App\Entity\LimiteTentativeConnection;
use App\Repository\LimiteTentativeConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LimiteTentativeConnectionController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/limite/tentative/connection', name: 'app_limite_tentative_connection')]
    public function index2(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LimiteTentativeConnectionController.php',
        ]);
    }

    #[Route('/api/limiteConnections', name: 'index_limite', methods: ['GET'])]
    public function index(LimiteTentativeConnectionRepository $limiteTentativeConnectionRepository): JsonResponse
    {
        $limit = $limiteTentativeConnectionRepository->findAll();

        $data = [];
        foreach ($limit as $limite) {
            $data[] = [
                'limit' => $limite->getLimite(),
            ];
        }

        return $this->json($data);
    }
    #[Route('/api/limiteConnection', name: 'create_limite', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['limite'])) {
            return $this->json(['message' => 'La limite est requise'], Response::HTTP_BAD_REQUEST);
        }

        $limite = new LimiteTentativeConnection();
        $limite->setLimite($data['limite']);

        $this->entityManager->persist($limite);
        $this->entityManager->flush();

        return $this->json(['message' => 'limite créé avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/api/limiteConnection', name: 'update_limite', methods: ['PUT'])]
    public function update(Request $request, LimiteTentativeConnectionRepository $limiteTentativeConnectionRepository): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if (!isset($data['limite'])) {
            return $this->json(['message' => 'La nouvelle limite est requise'], Response::HTTP_BAD_REQUEST);
        }
        $limite = $limiteTentativeConnectionRepository->findAll()[0];
        $limite->setLimite($data['limite']);
        $this->entityManager->flush();

        return $this->json(['message' => 'limite mis à jour avec succès']);
    }
}

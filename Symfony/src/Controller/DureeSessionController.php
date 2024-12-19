<?php

namespace App\Controller;

use App\Entity\DureeSession;
use App\Repository\DureeSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DureeSessionController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/duree/session', name: 'app_duree_session')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/DureeSessionController.php',
        ]);
    }

    #[Route('/api/dureeSessions', name: 'indexduree', methods: ['GET'])]
    public function dureeSession(DureeSessionRepository $dureeSessionRepository): JsonResponse
    {
        $duree = $dureeSessionRepository->findAll();

        $data = [];
        foreach ($duree as $dureeSession) {
            $data[] = [
                'duree' => $dureeSession->getDuree(),
            ];
        }

        return $this->json($data);
    }
    #[Route('/api/dureeSession', name: 'create_duree', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['duree'])) {
            return $this->json(['message' => 'La duree est requise'], Response::HTTP_BAD_REQUEST);
        }

        $duree = new DureeSession();
        $duree->setDuree($data['duree']);

        $this->entityManager->persist($duree);
        $this->entityManager->flush();

        return $this->json(['message' => 'duree créé avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/api/dureeSession', name: 'update_dureeSession', methods: ['PUT'])]
    public function update(Request $request, DureeSessionRepository $dureeSessionRepository): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if (!isset($data['duree'])) {
            return $this->json(['message' => 'La nouvelle duree est requise'], Response::HTTP_BAD_REQUEST);
        }
        $duree = $dureeSessionRepository->findAll()[0];
        $duree->setDuree($data['duree']);
        $this->entityManager->flush();

        return $this->json(['message' => 'limite mis à jour avec succès']);
    }
}

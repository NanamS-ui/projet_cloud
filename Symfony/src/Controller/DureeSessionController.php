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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        summary: "Récupérer toutes les durées de session",
        description: "Cette route retourne toutes les durées de session disponibles."
    )]
    #[OA\Response(response: 200, description: "Liste des durées de session retournée avec succès")]
    #[OA\Response(response: 500, description: "Erreur serveur interne")]
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
    #[OA\Post(
        summary: "Créer une durée de session",
        description: "Cette route permet de créer une nouvelle durée de session."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object", 
                    required: ["duree"],
                    properties: [
                        new OA\Property(property: "duree", type: "string", description: "Durée de la session")
                    ]
                )
            )
    )]
    #[OA\Response(response: 201, description: "Durée de session créée avec succès")]
    #[OA\Response(response: 400, description: "Paramètre manquant ou invalide")]
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

        return $this->json(['message' => 'Durée créée avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/api/dureeSession', name: 'update_dureeSession', methods: ['PUT'])]
    #[OA\Put(
        summary: "Mettre à jour une durée de session",
        description: "Cette route permet de mettre à jour une durée de session existante."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object",
                    required: ["duree"],
                    properties: [
                        new OA\Property(property: "duree", type: "string", description: "Nouvelle durée de la session")
                    ]
                )
            )
    )]
    #[OA\Response(response: 200, description: "Durée de session mise à jour avec succès")]
    #[OA\Response(response: 400, description: "Paramètre manquant ou invalide")]
    #[OA\Response(response: 404, description: "Durée de session non trouvée")]
    public function update(Request $request, DureeSessionRepository $dureeSessionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['duree'])) {
            return $this->json(['message' => 'La nouvelle duree est requise'], Response::HTTP_BAD_REQUEST);
        }
        
        // Hypothèse : On met à jour la première durée trouvée (ajuster si nécessaire)
        $duree = $dureeSessionRepository->findAll()[0];
        
        if (!$duree) {
            return $this->json(['message' => 'Durée de session non trouvée'], Response::HTTP_NOT_FOUND);
        }
        
        $duree->setDuree($data['duree']);
        $this->entityManager->flush();

        return $this->json(['message' => 'Durée mise à jour avec succès']);
    }
}

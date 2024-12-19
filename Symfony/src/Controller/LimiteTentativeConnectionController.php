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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        summary: "Récupérer les limites de tentatives de connexion",
        description: "Cette route retourne toutes les limites configurées pour les tentatives de connexion."
    )]
    #[OA\Response(response: 200, description: "Liste des limites de tentatives de connexion retournée avec succès")]
    #[OA\Response(response: 500, description: "Erreur serveur interne")]
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
    #[OA\Post(
        summary: "Créer une limite de tentative de connexion",
        description: "Cette route permet de créer une nouvelle limite pour les tentatives de connexion."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object", 
                    required: ["limite"],
                    properties: [
                        new OA\Property(property: "limite", type: "integer", description: "Nombre maximum de tentatives autorisées")
                    ]
                )
            )
    )]
    #[OA\Response(response: 201, description: "Limite créée avec succès")]
    #[OA\Response(response: 400, description: "Paramètre manquant ou invalide")]
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

        return $this->json(['message' => 'Limite créée avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/api/limiteConnection', name: 'update_limite', methods: ['PUT'])]
    #[OA\Put(
        summary: "Mettre à jour une limite de tentative de connexion",
        description: "Cette route permet de mettre à jour une limite existante pour les tentatives de connexion."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object",
                    required: ["limite"],
                    properties: [
                        new OA\Property(property: "limite", type: "integer", description: "Nouvelle limite de tentatives autorisées")
                    ]
                )
            )
    )]
    #[OA\Response(response: 200, description: "Limite mise à jour avec succès")]
    #[OA\Response(response: 400, description: "Paramètre manquant ou invalide")]
    #[OA\Response(response: 404, description: "Limite non trouvée")]
    public function update(Request $request, LimiteTentativeConnectionRepository $limiteTentativeConnectionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['limite'])) {
            return $this->json(['message' => 'La nouvelle limite est requise'], Response::HTTP_BAD_REQUEST);
        }
        
        // Hypothèse : On met à jour la première limite trouvée (ajuster si nécessaire)
        $limite = $limiteTentativeConnectionRepository->findAll()[0];

        if (!$limite) {
            return $this->json(['message' => 'Limite non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $limite->setLimite($data['limite']);
        $this->entityManager->flush();

        return $this->json(['message' => 'Limite mise à jour avec succès']);
    }
}

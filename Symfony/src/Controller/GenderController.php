<?php
namespace App\Controller;

use App\Entity\Gender;
use App\Repository\GenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class GenderController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/gender', name: 'index_gender', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupérer la liste des genres",
        description: "Cette route retourne tous les genres disponibles."
    )]
    
    #[OA\Response(response: 500, description: "Erreur serveur interne")]
    public function index(GenderRepository $genderRepository): JsonResponse
    {
        $genders = $genderRepository->findAll();

        $data = [];
        foreach ($genders as $gender) {
            $data[] = [
                'genderId' => $gender->getGenderId(),
                'name' => $gender->getName(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/gender', name: 'create_gender', methods: ['POST'])]
    #[OA\Post(
        summary: "Créer un genre",
        description: "Cette route permet de créer un nouveau genre."
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object", 
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nom du genre")
                    ]
                )
            )
    )]
    #[OA\Response(response: 201, description: "Genre créé avec succès")]
    #[OA\Response(response: 400, description: "Paramètre manquant ou invalide")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['message' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        $gender = new Gender();
        $gender->setName($data['name']);

        $this->entityManager->persist($gender);
        $this->entityManager->flush();

        return $this->json(['message' => 'Genre créé avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/api/gender/{id}', name: 'show_gender_by_id', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupérer un genre par son ID",
        description: "Cette route permet de récupérer un genre spécifique à partir de son identifiant."
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant du genre",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 200, description: "Genre trouvé", 
        content: 
            new OA\JsonContent(
                properties: [
                    new OA\Property(property: "genderId", type: "integer", description: "Identifiant du genre"),
                    new OA\Property(property: "name", type: "string", description: "Nom du genre")
                ]
            )
    )]
    #[OA\Response(response: 404, description: "Genre non trouvé")]
    public function show(int $id, GenderRepository $genderRepository): JsonResponse
    {
        $gender = $genderRepository->find($id);

        if (!$gender) {
            return $this->json(['message' => 'Genre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'genderId' => $gender->getGenderId(),
            'name' => $gender->getName(),
        ];

        return $this->json($data);
    }

    #[Route('/api/gender/{id}', name: 'update_gender', methods: ['PUT'])]
    #[OA\Put(
        summary: "Mettre à jour un genre",
        description: "Cette route permet de mettre à jour un genre existant."
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant du genre à mettre à jour",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        content: 
            new OA\MediaType(
                mediaType: "application/json", 
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nom du genre")
                    ]
                )
            )
    )]
    #[OA\Response(response: 200, description: "Genre mis à jour avec succès")]
    #[OA\Response(response: 404, description: "Genre non trouvé")]
    #[OA\Response(response: 400, description: "Paramètre invalide")]
    public function update(int $id, Request $request, GenderRepository $genderRepository): JsonResponse
    {
        $gender = $genderRepository->find($id);

        if (!$gender) {
            return $this->json(['message' => 'Genre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $gender->setName($data['name']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Genre mis à jour avec succès']);
    }

    #[Route('/api/gender/{id}', name: 'delete_gender', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Supprimer un genre",
        description: "Cette route permet de supprimer un genre spécifique."
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant du genre à supprimer",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 200, description: "Genre supprimé avec succès")]
    #[OA\Response(response: 404, description: "Genre non trouvé")]
    public function delete(int $id, GenderRepository $genderRepository): JsonResponse
    {
        $gender = $genderRepository->find($id);

        if (!$gender) {
            return $this->json(['message' => 'Genre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($gender);
        $this->entityManager->flush();

        return $this->json(['message' => 'Genre supprimé avec succès']);
    }
}

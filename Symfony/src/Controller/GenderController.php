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

class GenderController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/gender', name: 'index_gender', methods: ['GET'])]
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

    /**
     * Mettre à jour un genre existant
     */
    #[Route('/api/gender/{id}', name: 'update_gender', methods: ['PUT'])]
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

    /**
     * Supprimer un genre
     */
    #[Route('/api/gender/{id}', name: 'delete_gender', methods: ['DELETE'])]
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

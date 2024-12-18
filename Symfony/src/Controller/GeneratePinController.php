<?php

namespace App\Controller;

use App\Entity\GeneratePin;
use App\Repository\GeneratePinRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\Util;
use DateTime;
class GeneratePinController extends AbstractController{
    private EntityManagerInterface $entityManager;
    private GeneratePinRepository $generatePinRepository;
    private UsersRepository $usersRepository;
    private SessionInterface $session;

public function __construct(
    EntityManagerInterface $entityManager,
    GeneratePinRepository $generatePinRepository,
    UsersRepository $usersRepository,
    SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->generatePinRepository = $generatePinRepository;
        $this->usersRepository = $usersRepository;
        $this->session = $session;
    }
    #[Route('/api/generatePin', name: 'create_generatePin', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'])) {
            return $this->json(['message' => 'Missing required fields idUser'], 400);
        }
        $user = $this->entityManager->getRepository('App\Entity\Users')->find($data['idUser']);
        $user = $this->usersRepository->findByEmail($data['email']);
        if($user == null) return $this->json(['message' => 'User not found'], 400);
        $generatePin= new GeneratePin();
        $generatePin->setUsers($user);
        $pin = Util::generatePin();
        echo($pin);
        $generatePin->setPin(sha1($pin));
        $now = new DateTime();
        $this->generatePinRepository->deleteExpiredPins($now);
        $generatePin->setDateDebut($now);
        $dateFin = clone $now; 
        $dateFin->modify('+90 seconds'); 
        $generatePin->setDateFin($dateFin);
        $this->entityManager->persist($generatePin);
        $this->entityManager->flush();

        return $this->json(['message' => 'Generate pin created successfully'.$now->format('Y-m-d H:i:s')], 200);
    }
    #[Route('/api/verifypin', name: 'create_verifyPin', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'],$data['pin'])) {
            return $this->json(['message' => 'Missing required fields idUser'], 400);
        }
        $attemptsKey = 'pin_attempts_'.$data['email'];
        $attemps = $this->session->get($attemptsKey,0);
        if($attemps>3){
            return $this->json(['message' => 'To much attempts'], 400);
        }
        $user = $this->usersRepository->findByEmail($data['email']);
        if($user == null) return $this->json(['message' => 'User not found'], 400);
        $now = new DateTime();
        $this->generatePinRepository->deleteExpiredPins($now);
        $generatePin = $this->generatePinRepository->findByUserIdAndDateRangeAndPin($user->getUserId(),$now,sha1($data['pin']));
        if($generatePin==null) {
            $this->session->set($attemptsKey,$attemps+1);
            return $this->json(['message' => 'Invalidate pin'], 400);
        }
        return $this->json(['message' => 'Pin validate'], 200);
    }
    #[Route('/api/reinitialisation', name: 'reset_pin', methods: ['POST'])]
    public function reinitialisation(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'],$data['pin'])) {
            return $this->json(['message' => 'Missing required fields idUser'], 400);
        }
        $attemptsKey = 'pin_attempts_'.$data['email'];
        $this->session->set($attemptsKey,0);
        return $this->json(['message' => 'Number of attemps reseted'], 200);
        
        
    }

}
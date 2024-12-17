<?php
namespace App\Controller;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class LoginController extends AbstractController{
    private EntityManagerInterface $entityManager;
    private UsersRepository $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, UsersRepository $usersRepository)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
    }
    #[Route('/api/login', name: 'login_user', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'], $data['password'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }
        $user = $this->usersRepository->login($data['email'],$data['password']);
        if ($user==null) {
            return $this->json(['message' => 'Invalide email or password'], 400);
        }else{
            $data = [
                'id' => $user->getUserId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'birthDate' => $user->getBirthDate()->format('Y-m-d'),
                'role' => $user->getRole()->getName(),
                'gender' => $user->getGender()->getName(),
                'password' => $user->getPassword()
            ];
    
            return $this->json($data);
        }


        return $this->json(['message' => 'User deleted successfully']);
    }
}
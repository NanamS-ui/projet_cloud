<?php
namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
    private UsersRepository $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, UsersRepository $usersRepository)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
    }

    #[Route('/api/login', name: 'login_user', methods: ['POST'])]
    public function login(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $attemptsKey = 'login_attempts_' . $data['email'];
        $lastAttemptKey = 'last_attempt_' . $data['email'];

        $attempts = $session->get($attemptsKey, 0);
        $lastAttempt = $session->get($lastAttemptKey, null);

        if ($attempts >= 3) {
            $delaySeconds = 10 * (2 ** ($attempts - 3));

            if ($lastAttempt !== null) {
                $timeElapsed = time() - $lastAttempt;
                if ($timeElapsed < $delaySeconds) {
                    $remainingTime = $delaySeconds - $timeElapsed;
                    return $this->json([
                        'message' => 'Too many login attempts. Try again later.',
                        'retry_after' => $remainingTime . ' seconds'
                    ], 429); // 429 Too Many Requests
                }
            }
        }

        $user = $this->usersRepository->login($data['email'], $data['password']);

        if ($user === null) {
            $session->set($attemptsKey, $attempts + 1);
            $session->set($lastAttemptKey, time());

            return $this->json([
                'message' => 'Invalid email or password',
                'attempts' => $attempts + 1
            ], 400);
        }

        $session->remove($attemptsKey);
        $session->remove($lastAttemptKey);

        $responseData = [
            'id' => $user->getUserId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'role' => $user->getRole()->getName(),
            'gender' => $user->getGender()->getName()
        ];

        return $this->json($responseData);
    }
}

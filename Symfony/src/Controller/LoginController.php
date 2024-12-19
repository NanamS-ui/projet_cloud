<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\GeneratePinRepository;
use App\Entity\GeneratePin;
use App\Service\Util;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\LimiteTentativeConnectionRepository;
use App\Repository\DureeSessionRepository;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginController extends AbstractController
{
    private UsersRepository $usersRepository;
    private EntityManagerInterface $entityManager;
    private GeneratePinRepository $generatePinRepository;
    private DureeSessionRepository $dureeSessionRepository;

    public function __construct(EntityManagerInterface $entityManager, UsersRepository $usersRepository, GeneratePinRepository $generatePinRepository,)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->generatePinRepository = $generatePinRepository;
    }

    #[Route('/api/login', name: 'login_user', methods: ['POST'])]
    public function login(Request $request, SessionInterface $session, MailerInterface $mailer, LimiteTentativeConnectionRepository $limiteTentativeConnectionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $user = $this->usersRepository->findByEmail($data['email']);
        if ($user === null) {
            return $this->json(['message' => 'Email not found'], 400);
        }

        $email = $data['email'];
        $password = $data['password'];

        $attemptsKey = 'login_attempts_' . $email;
        $attempts = $session->get($attemptsKey, 0);

        $limit = $limiteTentativeConnectionRepository->findAll()[0];
        $limitAttemps = $limit->getLimite();

        if ($attempts >= $limitAttemps) {
            $htmlFilePath = $this->getParameter('kernel.project_dir') . '/public/reinitialiser.html';

            if (!file_exists($htmlFilePath)) {
                return $this->json([
                    'message' => 'Reset email template not found. Please contact support.'
                ], 500);
            }

            $htmlContent = file_get_contents($htmlFilePath);

            $emailMessage = (new Email())
                ->from('projetclouds5p16@gmail.com')
                ->to($email)
                ->subject('Réinitialisation des tentatives de connexion')
                ->html($htmlContent);

            $mailer->send($emailMessage);

            return $this->json([
                'email' => 'Vérifiez vos messages dans votre boîte mail pour la réinitialisation de connexion',
                'message' => 'Too many failed login attempts. Check your email for a reset link.'
            ], 429);
        }

        $user = $this->usersRepository->login($email, $password);

        if ($user === null) {
            $session->set($attemptsKey, $attempts + 1);
            $session->set('email', $email);

            return $this->json([
                'message' => 'Invalid password',
                'attempts' => $attempts + 1
            ], 400);
        }

        $session->remove($attemptsKey);
        $session->remove('email');

        // Générer le PIN
        $generatePin = new GeneratePin();
        $generatePin->setUsers($user);
        $pin = Util::generatePin();
        $generatePin->setPin(md5($pin));
        $now = new DateTime();
        $this->generatePinRepository->deleteExpiredPins($now);
        $generatePin->setDateDebut($now);
        $dateFin = clone $now;
        $dateFin->modify('+90 seconds');
        $generatePin->setDateFin($dateFin);
        $this->entityManager->persist($generatePin);
        $this->entityManager->flush();

        $emailMessage = (new Email())
            ->from('projetclouds5p16@gmail.com')
            ->to($user->getEmail())
            ->subject('Connexion réussie')
            ->html('<html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f4f4f9;
                            padding: 20px;
                        }
                        .container {
                            background-color: #ffffff;
                            border-radius: 8px;
                            padding: 20px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            max-width: 600px;
                            margin: auto;
                        }
                        h2 {
                            color: #333;
                        }
                        h3 {
                            color: #4CAF50;
                        }
                        p {
                            font-size: 16px;
                            color: #555;
                        }
                        .lien p {
                            display: inline-block;
                            padding: 10px 20px;
                            background-color: #007BFF;
                            color: #fff;
                            text-decoration: none;
                            border-radius: 5px;
                            font-weight: bold;
                            text-align: center;
                        }

                        .lien p:hover {
                            background-color: #0056b3;
                        }
                        .footer {
                            font-size: 14px;
                            color: #777;
                            text-align: center;
                            margin-top: 20px;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Bonjour ' . $user->getUsername() . ',</h2>
                        <p>Vous vous êtes connecté avec succès ! Votre code PIN est :</p>
                        <h3>' . $pin . '</h3>
                        <p>Le code PIN expirera dans 90 secondes.</p>
                        <p>Voici url pour valider votre code PIN :</p>
                         <p class="lien">
                            <p>
                                http://127.0.0.1:8000/api/verifyPin
                            </p>
                        </p>
                        <div class="footer">
                            <p>Merci de votre connexion. Si vous n êtes pas l origine de cette demande, veuillez nous contacter immédiatement.</p>
                        </div>
                    </div>
                </body>
                </html>');

        $mailer->send($emailMessage);

        $responseData = [
            'messageSuccess' => 'Connexion réussi, vérifiez vos messages dans votre boîte mail pour la confirmation'
        ];

        return $this->json($responseData);
    }

    #[Route('/api/verifyPin', name: 'verify_Pin', methods: ['POST'])]
    public function verifyPin(Request $request, SessionInterface $session, MailerInterface $mailer, DureeSessionRepository $dureeSessionRepository, LimiteTentativeConnectionRepository $limiteTentativeConnectionRepository): JsonResponse
    {
        $duree = $dureeSessionRepository->findAll()[0];
        ini_set('session.gc_maxlifetime', $duree->getDuree());
        session_set_cookie_params($duree->getDuree());

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['pin'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $user = $this->usersRepository->findByEmail($data['email']);
        if ($user === null) {
            return $this->json(['message' => 'Email not found'], 400);
        }

        $email = $data['email'];

        $attemptsKey = 'pin_attempts_' . $email;
        $attemps = $session->get($attemptsKey, 0);

        $limit = $limiteTentativeConnectionRepository->findAll()[0];
        $limitAttemps = $limit->getLimite();

        if ($attemps >= $limitAttemps) {
            $htmlFilePath = $this->getParameter('kernel.project_dir') . '/public/reinitialiserPIN.html';

            if (!file_exists($htmlFilePath)) {
                return $this->json([
                    'message' => 'Reset email template not found. Please contact support.'
                ], 500);
            }

            $htmlContent = file_get_contents($htmlFilePath);

            $emailMessage = (new Email())
                ->from('projetclouds5p16@gmail.com')
                ->to($data['email'])
                ->subject('Réinitialisation des tentatives de connexion')
                ->html($htmlContent);

            $mailer->send($emailMessage);

            return $this->json([
                'email' => 'Vérifiez vos messages dans votre boîte mail pour la réinitialisation du code PIN'
            ], 429);
        }

        $now = new DateTime();
        $this->generatePinRepository->deleteExpiredPins($now);
        $generatePin = $this->generatePinRepository->findByUserIdAndDateRangeAndPin($user->getUserId(), $now, md5($data['pin']));

        if ($generatePin == null) {
            $session->set($attemptsKey, $attemps + 1);
            $session->set('email', $email);
            return $this->json([
                'message' => 'Invalidate pin',
                'attempts' => $attemps + 1
            ], 400);
        }

        $session->remove($attemptsKey);
        $session->remove('email');

        $user->setToken(Util::generateToken());
        $this->entityManager->flush();

        $session->set('user', $user->getUserId());

        $responseData = [
            'messageSuccess' => 'Connexion successful, token created successfully',
            'id' => $user->getUserId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'role' => $user->getRole()->getName(),
            'gender' => $user->getGender()->getName()
        ];

        return $this->json($responseData);
    }

    #[Route('/api/reinitialiser', name: 'reinitialiser', methods: ['POST'])]
    public function resetAttempts(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? $session->get('email');

        if (!$email) {
            return $this->json([
                'message' => 'Email is required to reset login attempts.'
            ], 400);
        }

        $loginAttemptsKey = 'login_attempts_' . $email;
        $pinAttemptsKey = 'pin_attempts_' . $email;

        if ($session->has($loginAttemptsKey)) {
            $session->remove($loginAttemptsKey);
        }

        if ($session->has($pinAttemptsKey)) {
            $session->remove($pinAttemptsKey);
        }

        $session->remove('email');

        return $this->json([
            'message' => 'Login attempts and PIN attempts have been reset for ' . $email . '.'
        ]);
    }
}

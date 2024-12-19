<?php
namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\GeneratePin;
use App\Repository\GeneratePinRepository;
use App\Service\Util;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\LimiteTentativeConnectionRepository;
use App\Repository\DureeSessionRepository;
use Symfony\Component\Mime\Email;
use OpenApi\Attributes as OA;

class LoginController extends AbstractController
{
    private UsersRepository $usersRepository;
    private EntityManagerInterface $entityManager;
    private GeneratePinRepository $generatePinRepository;
    private DureeSessionRepository $dureeSessionRepository;

    public function __construct(EntityManagerInterface $entityManager, UsersRepository $usersRepository, GeneratePinRepository $generatePinRepository)
    {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->generatePinRepository = $generatePinRepository;
    }

    #[Route('/api/login', name: 'login_user', methods: ['POST'])]
    #[OA\Post(
        summary: "Se connecter à l'application",
        description: "Cette route permet de connecter un utilisateur avec son email et mot de passe."
    )]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "application/json", 
            schema: new OA\Schema(
                type: "object", 
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", description: "Email de l'utilisateur"),
                    new OA\Property(property: "password", type: "string", description: "Mot de passe de l'utilisateur")
                ]
            )
        )
    )]
    #[OA\Response(response: 200, description: "Connexion réussie")]
    #[OA\Response(response: 400, description: "Paramètres manquants ou invalides")]
    #[OA\Response(response: 429, description: "Trop de tentatives de connexion")]
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
            ->html('<html>...</html>');

        $mailer->send($emailMessage);

        return $this->json([
            'messageSuccess' => 'Connexion réussi, vérifiez vos messages dans votre boîte mail pour la confirmation'
        ]);
    }

    #[Route('/api/verifyPin', name: 'verify_Pin', methods: ['POST'])]
    #[OA\Post(
        summary: "Vérifier le code PIN",
        description: "Cette route permet de valider le code PIN envoyé à l'utilisateur."
    )]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "application/json", 
            schema: new OA\Schema(
                type: "object", 
                required: ["email", "pin"],
                properties: [
                    new OA\Property(property: "email", type: "string", description: "Email de l'utilisateur"),
                    new OA\Property(property: "pin", type: "string", description: "Code PIN fourni par l'utilisateur")
                ]
            )
        )
    )]
    #[OA\Response(response: 200, description: "PIN validé et token créé")]
    #[OA\Response(response: 400, description: "PIN invalide ou expiré")]
    #[OA\Response(response: 429, description: "Trop de tentatives de validation")]
    public function verifyPin(Request $request, SessionInterface $session, MailerInterface $mailer, DureeSessionRepository $dureeSessionRepository, LimiteTentativeConnectionRepository $limiteTentativeConnectionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['pin'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $user = $this->usersRepository->findByEmail($data['email']);
        if ($user === null) {
            return $this->json(['message' => 'Email not found'], 400);
        }

        $attemptsKey = 'pin_attempts_' . $data['email'];
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
                ->subject('Réinitialisation des tentatives de validation du PIN')
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
            return $this->json([
                'message' => 'Invalid PIN',
                'attempts' => $attemps + 1
            ], 400);
        }

        $session->remove($attemptsKey);

        // Générer un token pour l'utilisateur
        $user->setToken(Util::generateToken());
        $this->entityManager->flush();

        $session->set('user', $user->getUserId());

        return $this->json([
            'messageSuccess' => 'Connexion réussie, token créé avec succès',
            'id' => $user->getUserId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'role' => $user->getRole()->getName(),
            'gender' => $user->getGender()->getName()
        ]);
    }
}

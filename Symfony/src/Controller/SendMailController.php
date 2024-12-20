<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Users;
use App\Repository\GenderRepository;
use App\Repository\RoleRepository;
use OpenApi\Attributes as OA;

class SendMailController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RoleRepository $roleRepository;
    private GenderRepository $genderRepository;

    public function __construct(EntityManagerInterface $entityManager, RoleRepository $roleRepository, GenderRepository $genderRepository)
    {
        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
        $this->genderRepository = $genderRepository;
    }

    #[Route('/inscription_mail', name: 'send_email', methods: ['POST'])]
    #[OA\Post(
        summary: "Envoyer un email d'inscription",
        description: "Cette route permet de créer un utilisateur et d'envoyer un email de bienvenue.",
    )]
    #[OA\RequestBody(
        description: "Données de l'utilisateur à inscrire",
        required: true,
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                type: "object",
                required: ["username", "email", "password", "birthDate", "genderId"],
                properties: [
                    new OA\Property(property: "username", type: "string", description: "Nom d'utilisateur"),
                    new OA\Property(property: "email", type: "string", description: "Adresse email valide"),
                    new OA\Property(property: "password", type: "string", description: "Mot de passe de l'utilisateur"),
                    new OA\Property(property: "birthDate", type: "string", format: "date", description: "Date de naissance (YYYY-MM-DD)"),
                    new OA\Property(property: "genderId", type: "integer", description: "Identifiant du genre de l'utilisateur"),
                    new OA\Property(property: "roleId", type: "integer", description: "Identifiant du rôle (optionnel)", nullable: true),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Email envoyé avec succès",
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(property: "message", type: "string", example: "Email envoyé avec succès !"),
                    new OA\Property(
                        property: "user",
                        type: "object",
                        properties: [
                            new OA\Property(property: "username", type: "string"),
                            new OA\Property(property: "email", type: "string"),
                            new OA\Property(property: "password", type: "string"),
                            new OA\Property(property: "birthDate", type: "string", format: "date"),
                            new OA\Property(property: "role", type: "integer"),
                            new OA\Property(property: "gender", type: "integer"),
                        ]
                    ),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Erreur dans les données fournies",
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(property: "message", type: "string", example: "Invalid or missing email address"),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 500,
        description: "Erreur lors de l'envoi de l'email",
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(property: "message", type: "string", example: "Erreur lors de l'envoi de l'email."),
                    new OA\Property(property: "error", type: "string", description: "Description de l'erreur"),
                ]
            )
        )
    )]
    public function sendEmail(
        MailerInterface $mailer,
        Request $request,
        SessionInterface $session
    ): Response {
        $data = json_decode($request->getContent(), true);
    
        // Validation des champs requis
        if (!isset($data['username'], $data['email'], $data['password'], $data['birthDate'], $data['genderId'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }
    
        // Validation de l'email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Invalid or missing email address'], 400);
        }
    
        // Vérification si l'email est un compte Gmail
        if (!str_ends_with($data['email'], '@gmail.com')) {
            return $this->json(['message' => 'L\'adresse email doit être un compte Google Mail (@gmail.com).'], 400);
        }
    
        $user = new Users();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $user->setBirthDate(new \DateTime($data['birthDate']));
    
        $roleId = isset($data['roleId']) ? $data['roleId'] : null;
        $role = $roleId ? $this->roleRepository->find($roleId) : $this->roleRepository->find(1);
    
        if (!$role) {
            $role = $this->roleRepository->find(1);
        }
    
        $gender = $this->genderRepository->find($data['genderId']);
    
        $user->setRole($role);
        $user->setGender($gender);
    
        $session->set('user', [
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'role' => $user->getRole()->getRoleId(),
            'gender' => $user->getGender()->getGenderId(),
        ]);
    
        $htmlContent = file_get_contents($this->getParameter('kernel.project_dir') . '/public/inscription.html');
    
        $email = (new Email())
            ->from('projetclouds5p16@gmail.com')
            ->to($data['email'])
            ->subject('Bienvenue sur notre plateforme')
            ->html($htmlContent);
    
        try {
            $mailer->send($email);
    
            return $this->json([
                'message' => "Email envoyé avec succès !",
                'user' => $session->get('user')
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => "Erreur lors de l'envoi de l'email.",
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

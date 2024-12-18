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
    public function sendEmail(
        MailerInterface $mailer,
        Request $request,
        SessionInterface $session
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username'], $data['email'], $data['password'], $data['birthDate'], $data['genderId'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Invalid or missing email address'], 400);
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

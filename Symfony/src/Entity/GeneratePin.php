<?php

namespace App\Entity;

use App\Repository\GeneratePinRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeneratePinRepository::class)]
#[ORM\Table(name: 'generate_pin')]
class GeneratePin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'generate_pin_id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'generate_pins')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private Users $users;

    #[ORM\Column(name: 'date_debut_id', type: 'datetime')]
    private DateTime $dateDebut;

    #[ORM\Column(name: 'date_fin_id', type: 'datetime')]
    private DateTime $dateFin;

    #[ORM\Column(name: 'pin', type: 'string', length: 100)]
    private string $pin;

    // Getter et Setter pour $id
    public function getId(): int
    {
        return $this->id;
    }

    // Getter et Setter pour $users
    public function getUsers(): Users
    {
        return $this->users;
    }

    public function setUsers(Users $users): self
    {
        $this->users = $users;
        return $this;
    }

    // Getter et Setter pour $dateDebut
    public function getDateDebut(): DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(DateTime $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    // Getter et Setter pour $dateFin
    public function getDateFin(): DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(DateTime $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    // Getter et Setter pour $pin
    public function getPin(): string
    {
        return $this->pin;
    }

    public function setPin(string $pin): self
    {
        $this->pin = $pin;
        return $this;
    }
}

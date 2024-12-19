<?php

namespace  App\Entity;

use App\Repository\DureeSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DureeSessionRepository::class)]
#[ORM\Table(name: 'duree_session')]
class DureeSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'gender_id')]
    private int $id;
    #[ORM\Column(type: 'integer', name: 'duree')]
    private int $duree;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getDuree(): ?int
    {
        return $this->duree;
    }
    public function setDuree(int $duree): self
    {
        $this->duree = $duree;
        return $this;
    }
}

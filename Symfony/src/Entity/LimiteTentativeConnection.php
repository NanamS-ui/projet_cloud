<?php

namespace  App\Entity;

use App\Repository\LimiteTentativeConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LimiteTentativeConnectionRepository::class)]
#[ORM\Table(name: 'limite_tentative_session')]
class LimiteTentativeConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'gender_id')]
    private int $id;
    #[ORM\Column(type: 'integer', name: 'limite')]
    private int $limite;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getLimite(): ?int
    {
        return $this->limite;
    }
    public function setLimite(int $limite): self
    {
        $this->limite = $limite;
        return $this;
    }
}

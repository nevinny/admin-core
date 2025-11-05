<?php

namespace AdminCore\Entity\Trait;

use AdminCore\Enum\Statuses;
use Doctrine\ORM\Mapping as ORM;

trait Status
{

    #[ORM\Column(type: 'string', enumType: Statuses::class, options: ['default' => Statuses::Active])]
    private ?Statuses $status = Statuses::Active;

    public function getStatus(): Statuses|string|null
    {
        return $this->status;
    }

    public function setStatus(Statuses $status): static
    {
        $this->status = $status;

        return $this;
    }
    public function isPublished(): bool
    {
//        dd($this->getStatus(),Statuses::Active->value,($this->getStatus() === Statuses::Active));
        return $this->getStatus() === Statuses::Active;
    }
}

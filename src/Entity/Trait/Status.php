<?php

namespace Nevinny\AdminCoreBundle\Entity\Trait;

use Nevinny\AdminCoreBundle\Enum\Statuses;
use Doctrine\ORM\Mapping as ORM;

trait Status
{

    #[ORM\Column(type: 'string', length: 20, enumType: Statuses::class, options: ['default' => Statuses::Active])]
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
        return $this->getStatus() === Statuses::Active;
    }
}

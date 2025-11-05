<?php

namespace AdminCore\Entity\Trait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
trait Created
{
    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $created_at = null;

    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updated_at = null;

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }


    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = $this->updated_at = new \DateTime();
    }
}
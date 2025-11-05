<?php

namespace AdminCore\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use AdminCore\Entity\User as BaseUser;
trait Owner
{
    #[ORM\ManyToOne(targetEntity: BaseUser::class)]
    #[ORM\JoinColumn(name: "created_by", nullable: true)]
    private ?BaseUser $created_by = null;

    #[ORM\ManyToOne(targetEntity: BaseUser::class)]
    #[ORM\JoinColumn(name: "updated_by", nullable: true)]
    private ?BaseUser $updated_by = null;

    public function getCreatedBy(): ?BaseUser
    {
        return $this->created_by;
    }
    public function getOwner(): ?BaseUser
    {
        return $this->created_by;
    }

    public function setCreatedBy(?BaseUser $created_by): static
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getUpdatedBy(): ?BaseUser
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?BaseUser $updated_by): static
    {
        $this->updated_by = $updated_by;
        return $this;
    }


}
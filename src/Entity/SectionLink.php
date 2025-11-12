<?php

namespace Nevinny\AdminCoreBundle\Entity;

use Nevinny\AdminCoreBundle\Interface\SystemEntityInterface;
use Nevinny\AdminCoreBundle\Repository\SectionLinkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SectionLinkRepository::class)]
class SectionLink implements SystemEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SectionType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SectionType $parentType = null;

    #[ORM\ManyToOne(targetEntity: SectionType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SectionType $childType = null;

    public function __toString(): string
    {
        return sprintf('%s → %s', $this->parentType?->getName() ?? '?', $this->childType?->getName() ?? '?');
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentType(): ?SectionType
    {
        return $this->parentType;
    }

    public function setParentType(?SectionType $parentType): SectionLink
    {
        $this->parentType = $parentType;
        return $this;
    }

    public function getChildType(): ?SectionType
    {
        return $this->childType;
    }

    public function setChildType(?SectionType $childType): SectionLink
    {
        $this->childType = $childType;
        return $this;
    }

}

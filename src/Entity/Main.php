<?php

namespace Nevinny\AdminCoreBundle\Entity;

use Nevinny\AdminCoreBundle\Entity\SectionType;
use Nevinny\AdminCoreBundle\Entity\Trait\Created;
use Nevinny\AdminCoreBundle\Entity\Trait\Status;
use Nevinny\AdminCoreBundle\Enum\Statuses;
use Nevinny\AdminCoreBundle\Interface\SystemEntityInterface;
use Nevinny\AdminCoreBundle\Repository\MainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MainRepository::class)]
#[ORM\UniqueConstraint(
    name: "path_unique_idx",
    columns: ["full_path"]
)]
class Main implements SystemEntityInterface
{
    use Created, Status;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $fullPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $template = null;

    #[ORM\Column(nullable: true)]
    private ?int $ord = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isNode = null;

    #[ORM\Column]
    private ?int $entityId = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $parent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SectionType $entityType = null;

    public function __construct()
    {
        $this->mains = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getFullPath(): ?string
    {
        return $this->fullPath;
    }

    public function setFullPath(string $fullPath): static
    {
        $this->fullPath = $fullPath;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getOrd(): ?int
    {
        return $this->ord;
    }

    public function setOrd(?int $ord): static
    {
        $this->ord = $ord;

        return $this;
    }

    public function isNode(): ?bool
    {
        return $this->isNode;
    }

    public function setIsNode(bool $isNode): static
    {
        $this->isNode = $isNode;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getEntityType(): ?SectionType
    {
        return $this->entityType;
    }

    public function setEntityType(?SectionType $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}

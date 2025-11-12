<?php

namespace Nevinny\AdminCoreBundle\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait DefaultFields
{
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?int $parent = null;

    #[ORM\Column(options: ['default' => '0'])]
    private ?int $ord = 0;

    public function __toString(): string
    {
        return $this->title ?? 'Без названия';
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

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(int $parent): static
    {
        $this->parent = $parent;

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

    public function getOrd(): ?int
    {
        return $this->ord;
    }

    public function setOrd(int $ord): static
    {
        $this->ord = $ord;

        return $this;
    }
}

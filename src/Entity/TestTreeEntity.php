<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\EasyAdminTreeSelectFieldBundle\Repository\TestTreeEntityRepository;

/**
 * 用于测试的树形实体
 */
#[ORM\Entity(repositoryClass: TestTreeEntityRepository::class)]
#[ORM\Table(name: 'test_tree_entity', options: ['comment' => '用于测试的树形实体表'])]
class TestTreeEntity implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: '名称不能为空')]
    #[Assert\Length(max: 255, maxMessage: '名称不能超过255个字符')]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '节点名称'])]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, options: ['comment' => '父节点ID'])]
    private ?self $parent = null;

    #[Assert\Length(max: 255, maxMessage: '描述不能超过255个字符')]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '节点描述'])]
    private ?string $description = null;

    #[Assert\NotNull(message: '激活状态不能为空')]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否激活状态'])]
    private bool $active = true;

    #[Assert\GreaterThanOrEqual(value: 0, message: '排序顺序必须大于等于0')]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '排序顺序'])]
    private int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

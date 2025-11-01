<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\TreeDataProviderInterface;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\Type\TreeSelectType;

/**
 * TreeSelect 字段公共基类（封装共享配置与便捷方法）。
 */
abstract class AbstractTreeSelectField implements FieldInterface
{
    use FieldTrait;

    /**
     * 供子类在 new() 时调用的通用配置。
     */
    protected static function base(string $propertyName, ?string $label = null): static
    {
        $self = new static();

        // 这些方法来自FieldTrait，仍然支持链式调用
        $self->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(TreeSelectType::class)
            ->setTemplatePath('@EasyAdminTreeSelectField/bundles/EasyAdminBundle/crud/field/tree_select.html.twig')
            ->addFormTheme('@EasyAdminTreeSelectField/form/tree_select_theme.html.twig')
            ->addJsFiles('bundles/easyadmintreeselectfield/js/tree-select.js')
            ->addCssFiles('bundles/easyadmintreeselectfield/css/tree-select.css')
            ->addCssClass('field-tree-select')
            ->setDefaultColumns('col-md-8')
        ;

        return $self;
    }

    /**
     * 设置数据提供者。
     */
    public function setDataProvider(TreeDataProviderInterface $provider): static
    {
        $this->setFormTypeOption('data_provider', $provider);

        return $this;
    }

    /**
     * 设置实体类（用于多选时的 Collection 转换器等）。
     */
    public function setEntityClass(string $entityClass): static
    {
        $this->setFormTypeOption('entity_class', $entityClass);

        return $this;
    }

    /**
     * 从数组设置数据。
     *
     * @param array<array<string,mixed>> $data
     */
    public function setData(array $data): static
    {
        $provider = new ArrayTreeDataProvider($data);
        $this->setFormTypeOption('data_provider', $provider);

        return $this;
    }

    public function setMultiple(bool $multiple = true): static
    {
        $this->setFormTypeOption('multiple', $multiple);

        return $this;
    }

    public function setExpandAll(bool $expandAll = false): static
    {
        $this->setFormTypeOption('expand_all', $expandAll);

        return $this;
    }

    public function setExpandedLevel(?int $level = 1): static
    {
        $this->setFormTypeOption('expanded_level', $level);

        return $this;
    }

    public function setSearchable(bool $searchable = true): static
    {
        $this->setFormTypeOption('searchable', $searchable);

        return $this;
    }

    public function setMaxDepth(?int $maxDepth = null): static
    {
        $this->setFormTypeOption('max_depth', $maxDepth);

        return $this;
    }

    public function setPlaceholder(?string $placeholder = null): static
    {
        $this->setFormTypeOption('placeholder', $placeholder);

        return $this;
    }

    public function setRequired(bool $required = false): static
    {
        $this->setFormTypeOption('required', $required);

        return $this;
    }

    public function setSortable(bool $sortable = false): static
    {
        $this->setFormTypeOption('sortable', $sortable);

        return $this;
    }

    public function setShowCheckbox(bool $showCheckbox = true): static
    {
        $this->setFormTypeOption('show_checkbox', $showCheckbox);

        return $this;
    }

    public function setLazyLoad(bool $lazyLoad = false): static
    {
        $this->setFormTypeOption('lazy_load', $lazyLoad);

        return $this;
    }

    public function setNodeIcon(?string $icon = null): static
    {
        $this->setFormTypeOption('node_icon', $icon);

        return $this;
    }

    public function setLeafIcon(?string $icon = null): static
    {
        $this->setFormTypeOption('leaf_icon', $icon);

        return $this;
    }

    // -------- 便捷读取（测试与调试友好） --------
    public function getProperty(): string
    {
        return $this->getAsDto()->getProperty();
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->getAsDto()->getLabel();
    }

    public function getFormType(): ?string
    {
        return $this->getAsDto()->getFormType();
    }

    public function getCssClass(): string
    {
        return $this->getAsDto()->getCssClass();
    }

    public function getDefaultColumns(): string
    {
        return $this->getAsDto()->getDefaultColumns();
    }

    /**
     * @return array<string,mixed>
     */
    public function getFormTypeOptions(): array
    {
        /** @var array<string,mixed> */
        return $this->getAsDto()->getFormTypeOptions();
    }
}

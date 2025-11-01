<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Field;

/**
 * TreeSelect 多选字段（语义化别名）。
 */
final class TreeSelectMultiField extends AbstractTreeSelectField
{
    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = self::base($propertyName, $label);
        $field->setMultiple(true);

        return $field;
    }

    // 固化多选语义，避免被外部改为单选
    public function setMultiple(bool $multiple = true): static
    {
        parent::setMultiple(true);

        return $this;
    }
}

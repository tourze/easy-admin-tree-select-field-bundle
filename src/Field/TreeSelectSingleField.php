<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Field;

/**
 * TreeSelect 单选字段。
 */
final class TreeSelectSingleField extends AbstractTreeSelectField
{
    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = self::base($propertyName, $label);
        $field->setMultiple(false);

        return $field;
    }

    // 固化单选语义，屏蔽外部改为多选
    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setMultiple(bool $multiple = true): static
    {
        parent::setMultiple(false);

        return $this;
    }
}

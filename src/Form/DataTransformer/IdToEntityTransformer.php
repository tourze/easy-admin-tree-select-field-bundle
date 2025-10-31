<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * 将单个 Entity <-> ID 进行转换
 *
 * @implements DataTransformerInterface<mixed, mixed>
 */
class IdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $entityClass,
    ) {
    }

    /**
     * Model -> View
     *
     * @param mixed $value 实体对象或标量
     * @return mixed 返回 ID 或 null
     */
    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                return $value->getId();
            }

            // 尝试常见属性名
            if (property_exists($value, 'id')) {
                return $value->id;
            }
        }

        // 已经是标量ID
        return $value;
    }

    /**
     * View -> Model
     *
     * @param mixed $value 标量ID
     * @return mixed 返回实体对象或 null
     */
    public function reverseTransform($value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return $this->entityManager->getRepository($this->entityClass)->find($value);
    }
}

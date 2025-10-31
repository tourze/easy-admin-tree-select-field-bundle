<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * TestTreeEntity 测试类
 * @internal
 */
#[CoversClass(TestTreeEntity::class)]
class TestTreeEntityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TestTreeEntity();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '测试节点'];
        yield 'parent' => ['parent', new TestTreeEntity()];
        yield 'description' => ['description', '这是一个测试节点的描述'];
        yield 'active' => ['active', false];
        yield 'sortOrder' => ['sortOrder', 10];
    }

    public function testInitialValues(): void
    {
        $entity = new TestTreeEntity();
        self::assertNull($entity->getId());
        self::assertSame('', $entity->getName());
        self::assertNull($entity->getParent());
        self::assertNull($entity->getDescription());
        self::assertTrue($entity->isActive());
        self::assertSame(0, $entity->getSortOrder());
    }

    public function testSetParentToNull(): void
    {
        $entity = new TestTreeEntity();
        $entity->setParent(new TestTreeEntity());
        $entity->setParent(null);

        self::assertNull($entity->getParent());
    }

    public function testSetDescriptionToNull(): void
    {
        $entity = new TestTreeEntity();
        $entity->setDescription('描述');
        $entity->setDescription(null);

        self::assertNull($entity->getDescription());
    }

    public function testToString(): void
    {
        $entity = new TestTreeEntity();
        $name = '测试节点名称';
        $entity->setName($name);

        self::assertSame($name, (string) $entity);
    }

    public function testToStringWithEmptyName(): void
    {
        $entity = new TestTreeEntity();
        self::assertSame('', (string) $entity);
    }

    public function testStringableInterface(): void
    {
        $entity = new TestTreeEntity();
        // TestTreeEntity 实现了 Stringable 接口，通过 __toString() 方法来验证
        self::assertIsString((string) $entity);
    }

    /**
     * 测试实体类是否正确实现了贫血模型
     */
    public function testIsAnemic(): void
    {
        $reflection = new \ReflectionClass(TestTreeEntity::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $businessLogicMethods = [];
        foreach ($methods as $method) {
            $methodName = $method->getName();
            // 排除标准的 getter/setter 和 __toString 方法
            if (false === preg_match('/^(get|set|is|__toString)/', $methodName)) {
                $businessLogicMethods[] = $methodName;
            }
        }

        self::assertEmpty(
            $businessLogicMethods,
            sprintf(
                '实体类应该是贫血模型，不应包含业务逻辑方法: %s',
                implode(', ', $businessLogicMethods)
            )
        );
    }

    /**
     * 测试所有私有属性都有对应的访问器方法
     */
    public function testAllPrivatePropertiesHaveAccessors(): void
    {
        $reflection = new \ReflectionClass(TestTreeEntity::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $capitalizedName = ucfirst($propertyName);

            // 检查 getter 方法
            $propertyType = $property->getType();
            if ($propertyType instanceof \ReflectionNamedType && 'bool' === $propertyType->getName()) {
                $getterName = 'is' . $capitalizedName;
            } else {
                $getterName = 'get' . $capitalizedName;
            }

            self::assertTrue(
                $reflection->hasMethod($getterName),
                sprintf('属性 %s 缺少 getter 方法 %s', $propertyName, $getterName)
            );

            // 对于非 ID 字段，检查 setter 方法
            if ('id' !== $propertyName) {
                $setterName = 'set' . $capitalizedName;
                self::assertTrue(
                    $reflection->hasMethod($setterName),
                    sprintf('属性 %s 缺少 setter 方法 %s', $propertyName, $setterName)
                );
            }
        }
    }
}

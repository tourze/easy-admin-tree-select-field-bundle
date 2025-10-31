<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\DataTransformer\CollectionToArrayTransformer;

/**
 * @internal
 */
#[CoversClass(CollectionToArrayTransformer::class)]
final class CollectionToArrayTransformerTest extends TestCase
{
    private CollectionToArrayTransformer $transformer;

    private EntityManagerInterface&MockObject $entityManager;

    /** @var EntityRepository<object>&MockObject */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->willReturn($this->repository)
        ;

        $this->transformer = new CollectionToArrayTransformer(
            $this->entityManager,
            \stdClass::class
        );
    }

    public function testConstructorInitializesCorrectly(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $transformer = new CollectionToArrayTransformer(
            $entityManager,
            \stdClass::class
        );

        $this->assertInstanceOf(CollectionToArrayTransformer::class, $transformer);
    }

    public function testTransformWithNullValueReturnsEmptyArray(): void
    {
        $result = $this->transformer->transform(null);

        $this->assertEmpty($result);
    }

    public function testTransformWithNonCollectionValueReturnsEmptyArray(): void
    {
        $result = $this->transformer->transform('not a collection');

        $this->assertEmpty($result);
    }

    public function testTransformWithEmptyCollectionReturnsEmptyArray(): void
    {
        $collection = new ArrayCollection();
        $result = $this->transformer->transform($collection);

        $this->assertEmpty($result);
    }

    public function testTransformWithCollectionOfEntitiesReturnsIdArray(): void
    {
        // 创建模拟实体
        $entity1 = $this->createEntityWithId(1);
        $entity2 = $this->createEntityWithId(2);

        $collection = new ArrayCollection([$entity1, $entity2]);
        $result = $this->transformer->transform($collection);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]);
        $this->assertEquals(2, $result[1]);
    }

    public function testTransformWithCollectionOfScalarValuesReturnsOriginalValues(): void
    {
        $collection = new ArrayCollection([1, 2, 3, 'test']);
        $result = $this->transformer->transform($collection);

        $this->assertCount(4, $result);
        $this->assertEquals([1, 2, 3, 'test'], $result);
    }

    public function testTransformWithMixedCollectionHandlesBothEntityAndScalar(): void
    {
        $entity = $this->createEntityWithId(42);

        $collection = new ArrayCollection([$entity, 100, 'test']);
        $result = $this->transformer->transform($collection);

        $this->assertCount(3, $result);
        $this->assertEquals(42, $result[0]);
        $this->assertEquals(100, $result[1]);
        $this->assertEquals('test', $result[2]);
    }

    public function testTransformWithObjectWithoutGetIdMethodReturnsObject(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';

        $collection = new ArrayCollection([$obj]);
        $result = $this->transformer->transform($collection);

        $this->assertCount(1, $result);
        $this->assertSame($obj, $result[0]);
    }

    public function testReverseTransformWithNullReturnsEmptyCollection(): void
    {
        $result = $this->transformer->reverseTransform(null);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testReverseTransformWithNonArrayReturnsEmptyCollection(): void
    {
        $result = $this->transformer->reverseTransform('not an array');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testReverseTransformWithEmptyArrayReturnsEmptyCollection(): void
    {
        $result = $this->transformer->reverseTransform([]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testReverseTransformWithValidIdsReturnsCollectionOfEntities(): void
    {
        // 创建模拟实体
        $entity1 = $this->createEntityWithId(1);
        $entity2 = $this->createEntityWithId(2);

        // 配置 repository mock - 使用 find() 方法，不是 findBy()
        $this->repository->method('find')
            ->willReturnCallback(function ($id) use ($entity1, $entity2) {
                return match ($id) {
                    1 => $entity1,
                    2 => $entity2,
                    default => null,
                };
            })
        ;

        $ids = [1, 2];
        $result = $this->transformer->reverseTransform($ids);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);

        $entities = $result->toArray();
        $this->assertCount(2, $entities);
        $entity0 = $entities[0];
        $entity1 = $entities[1];
        $this->assertIsObject($entity0);
        $this->assertIsObject($entity1);
        $this->assertTrue(method_exists($entity0, 'getId'));
        $this->assertTrue(method_exists($entity1, 'getId'));
        $this->assertEquals(1, $entity0->getId());
        $this->assertEquals(2, $entity1->getId());
    }

    public function testReverseTransformIgnoresNullAndEmptyValues(): void
    {
        $entity = $this->createEntityWithId(1);

        $this->repository->method('find')
            ->willReturnCallback(function ($id) use ($entity) {
                return match ($id) {
                    1 => $entity,
                    default => null,
                };
            })
        ;

        $ids = [1, null, '', 0];
        $result = $this->transformer->reverseTransform($ids);

        $this->assertInstanceOf(Collection::class, $result);
        // 只包含 ID 为 1 的实体，null 和 '' 被忽略，0 会尝试查找但返回 null
        $this->assertCount(1, $result);

        $entities = $result->toArray();
        $this->assertCount(1, $entities);
        $entity = $entities[0];
        $this->assertIsObject($entity);
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertEquals(1, $entity->getId());
    }

    public function testReverseTransformIgnoresNonExistentIds(): void
    {
        $entity = $this->createEntityWithId(1);

        $this->repository->method('find')
            ->willReturnCallback(function ($id) use ($entity) {
                return match ($id) {
                    1 => $entity,
                    default => null,  // 99999 和 88888 返回 null
                };
            })
        ;

        $ids = [1, 99999, 88888];
        $result = $this->transformer->reverseTransform($ids);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);

        $entities = $result->toArray();
        $this->assertCount(1, $entities);
        $entity = $entities[0];
        $this->assertIsObject($entity);
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertEquals(1, $entity->getId());
    }

    public function testReverseTransformWithAllInvalidIdsReturnsEmptyCollection(): void
    {
        $this->repository->method('find')
            ->willReturn(null) // 所有 ID 都找不到对应实体
        ;

        $ids = [99999, 88888, null, ''];
        $result = $this->transformer->reverseTransform($ids);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testTransformAndReverseTransformRoundTrip(): void
    {
        // 创建模拟实体
        $entity1 = $this->createEntityWithId(1);
        $entity2 = $this->createEntityWithId(2);

        // 配置 repository mock
        $this->repository->method('find')
            ->willReturnCallback(function ($id) use ($entity1, $entity2) {
                return match ($id) {
                    1 => $entity1,
                    2 => $entity2,
                    default => null,
                };
            })
        ;

        // 原始集合
        $originalCollection = new ArrayCollection([$entity1, $entity2]);

        // 正向转换
        $transformedArray = $this->transformer->transform($originalCollection);

        // 反向转换
        $reverseTransformedCollection = $this->transformer->reverseTransform($transformedArray);

        // 验证结果
        $this->assertInstanceOf(Collection::class, $reverseTransformedCollection);
        $this->assertCount(2, $reverseTransformedCollection);

        $resultEntities = $reverseTransformedCollection->toArray();
        $this->assertCount(2, $resultEntities);
        $entity0 = $resultEntities[0];
        $entity1 = $resultEntities[1];
        $this->assertIsObject($entity0);
        $this->assertIsObject($entity1);
        $this->assertTrue(method_exists($entity0, 'getId'));
        $this->assertTrue(method_exists($entity1, 'getId'));
        $this->assertEquals(1, $entity0->getId());
        $this->assertEquals(2, $entity1->getId());
    }

    public function testTransformPreservesCollectionOrder(): void
    {
        $entities = [];
        for ($i = 1; $i <= 5; ++$i) {
            $entities[] = $this->createEntityWithId($i);
        }

        $collection = new ArrayCollection($entities);
        $result = $this->transformer->transform($collection);

        $this->assertCount(5, $result);

        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($i + 1, $result[$i]);
        }
    }

    public function testReverseTransformPreservesArrayOrder(): void
    {
        $entity1 = $this->createEntityWithId(1);
        $entity2 = $this->createEntityWithId(2);
        $entity3 = $this->createEntityWithId(3);

        // 配置 repository 按照 ID 返回对应实体
        $this->repository->method('find')
            ->willReturnCallback(function ($id) use ($entity1, $entity2, $entity3) {
                return match ($id) {
                    1 => $entity1,
                    2 => $entity2,
                    3 => $entity3,
                    default => null,
                };
            })
        ;

        // 以不同的顺序传递ID
        $ids = [3, 1, 2];
        $result = $this->transformer->reverseTransform($ids);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);

        $resultEntities = $result->toArray();
        $this->assertCount(3, $resultEntities);
        $entity0 = $resultEntities[0];
        $entity1 = $resultEntities[1];
        $entity2 = $resultEntities[2];
        $this->assertIsObject($entity0);
        $this->assertIsObject($entity1);
        $this->assertIsObject($entity2);
        $this->assertTrue(method_exists($entity0, 'getId'));
        $this->assertTrue(method_exists($entity1, 'getId'));
        $this->assertTrue(method_exists($entity2, 'getId'));
        $this->assertEquals(3, $entity0->getId());
        $this->assertEquals(1, $entity1->getId());
        $this->assertEquals(2, $entity2->getId());
    }

    /**
     * 创建一个带有 getId() 方法的模拟实体
     */
    private function createEntityWithId(int $id): object
    {
        $entity = new class {
            private ?int $id = null;

            public function getId(): ?int
            {
                return $this->id;
            }

            public function setId(?int $id): void
            {
                $this->id = $id;
            }
        };

        $entity->setId($id);

        return $entity;
    }
}

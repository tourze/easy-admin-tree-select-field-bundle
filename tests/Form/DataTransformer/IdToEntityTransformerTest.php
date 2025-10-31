<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\DataTransformer\IdToEntityTransformer;

/**
 * @internal
 */
#[CoversClass(IdToEntityTransformer::class)]
final class IdToEntityTransformerTest extends TestCase
{
    public function testTransformEntityToId(): void
    {
        $entity = new class {
            public function getId(): int
            {
                return 42;
            }
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $transformer = new IdToEntityTransformer($em, get_class($entity));

        $this->assertSame(42, $transformer->transform($entity));
    }

    public function testTransformNull(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        $this->assertNull($transformer->transform(null));
    }

    public function testReverseTransformIdToEntity(): void
    {
        $entity = new class {
            public function getId(): int
            {
                return 7;
            }
        };

        /** @var EntityRepository<object>&MockObject $repo */
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($entity)
        ;

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $transformer = new IdToEntityTransformer($em, get_class($entity));

        $this->assertSame($entity, $transformer->reverseTransform(7));
    }

    public function testReverseTransformEmptyReturnsNull(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $transformer = new IdToEntityTransformer($em, \stdClass::class);

        $this->assertNull($transformer->reverseTransform(null));
        $this->assertNull($transformer->reverseTransform(''));
    }
}

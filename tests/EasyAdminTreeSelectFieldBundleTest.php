<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\EasyAdminTreeSelectFieldBundle\DependencyInjection\Compiler\TwigPathCompilerPass;
use Tourze\EasyAdminTreeSelectFieldBundle\EasyAdminTreeSelectFieldBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(EasyAdminTreeSelectFieldBundle::class)]
#[RunTestsInSeparateProcesses]
final class EasyAdminTreeSelectFieldBundleTest extends AbstractBundleTestCase
{
    protected function onSetUp(): void
    {
        // Bundle 测试不需要特殊的初始化
    }

    public function testBundleNameIsCorrect(): void
    {
        $className = self::getBundleClass();
        $bundle = new $className();
        $this->assertInstanceOf(Bundle::class, $bundle);

        $expected = 'EasyAdminTreeSelectFieldBundle';
        $actual = $bundle->getName();

        $this->assertEquals($expected, $actual);
    }

    public function testBundleNamespaceIsCorrect(): void
    {
        $className = self::getBundleClass();
        $bundle = new $className();
        $this->assertInstanceOf(Bundle::class, $bundle);

        $expected = 'Tourze\EasyAdminTreeSelectFieldBundle';
        $actual = $bundle->getNamespace();

        $this->assertEquals($expected, $actual);
    }

    public function testBundlePathIsCorrect(): void
    {
        $className = self::getBundleClass();
        $bundle = new $className();
        $this->assertInstanceOf(Bundle::class, $bundle);

        $path = $bundle->getPath();
        $this->assertStringEndsWith('easy-admin-tree-select-field-bundle/src', $path);
        $this->assertDirectoryExists($path, "Bundle path should be a valid directory: {$path}");
    }

    public function testBuildAddsCompilerPass(): void
    {
        $className = self::getBundleClass();
        $bundle = new $className();
        $this->assertInstanceOf(Bundle::class, $bundle);
        $container = new ContainerBuilder();

        // 记录初始的编译器传递数量
        $initialPassCount = count($container->getCompiler()->getPassConfig()->getPasses());

        $bundle->build($container);

        // 验证已添加编译器传递
        $passes = $container->getCompiler()->getPassConfig()->getPasses();
        $this->assertGreaterThan($initialPassCount, count($passes));

        // 验证是否添加了 TwigPathCompilerPass
        $twigPathCompilerPassFound = false;
        foreach ($passes as $pass) {
            if ($pass instanceof TwigPathCompilerPass) {
                $twigPathCompilerPassFound = true;
                break;
            }
        }

        $this->assertTrue($twigPathCompilerPassFound, 'TwigPathCompilerPass should be added to the container');
    }
}

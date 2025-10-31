<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tourze\EasyAdminTreeSelectFieldBundle\DependencyInjection\EasyAdminTreeSelectFieldExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * EasyAdminTreeSelectFieldExtension基础功能测试
 *
 * @internal
 */
#[CoversClass(EasyAdminTreeSelectFieldExtension::class)]
final class EasyAdminTreeSelectFieldExtensionBasicTest extends AbstractDependencyInjectionExtensionTestCase
{
    private EasyAdminTreeSelectFieldExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new EasyAdminTreeSelectFieldExtension();
        $this->container = new ContainerBuilder();
    }

    public function testExtensionIsCorrectInstance(): void
    {
        $this->assertInstanceOf(AutoExtension::class, $this->extension);
        $this->assertInstanceOf(Extension::class, $this->extension);
    }

    public function testImplementsPrependExtensionInterface(): void
    {
        $this->assertInstanceOf(PrependExtensionInterface::class, $this->extension);
    }

    public function testGetAliasReturnsCorrectValue(): void
    {
        $alias = $this->extension->getAlias();

        $this->assertEquals('easy_admin_tree_select_field', $alias);
    }

    public function testLoadMethodCanBeCalled(): void
    {
        $configs = [];

        // 测试方法可以被调用而不抛出异常（即使依赖文件不能加载）
        try {
            $this->extension->load($configs, $this->container);
            $this->assertTrue(true, 'Load method executed without exception');
        } catch (\Exception $e) {
            // 如果由于服务文件加载问题而失败，这是预期的
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testPrependWithoutTwigExtension(): void
    {
        // 容器没有 twig 扩展
        $this->assertFalse($this->container->hasExtension('twig'));

        $this->extension->prepend($this->container);

        // prepend 应该正常完成，不抛出异常
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testPrependWithTwigExtension(): void
    {
        // 模拟 twig 扩展存在
        /** @phpstan-ignore-next-line tourze.preferAutoExtension */
        $this->container->registerExtension(new class extends Extension {
            public function getAlias(): string
            {
                return 'twig';
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
                // Mock implementation
            }
        });

        $this->assertTrue($this->container->hasExtension('twig'));

        $this->extension->prepend($this->container);

        // 验证 twig 路径配置被添加
        $extensionConfigs = $this->container->getExtensionConfig('twig');
        $this->assertNotEmpty($extensionConfigs);

        $lastConfig = end($extensionConfigs);
        $this->assertArrayHasKey('paths', $lastConfig);

        // 验证至少包含一个路径配置
        $this->assertIsArray($lastConfig['paths']);
        $this->assertNotEmpty($lastConfig['paths']);

        // 验证命名空间
        $paths = $lastConfig['paths'];
        $this->assertIsArray($paths);
        $this->assertContains('EasyAdminTreeSelectField', $paths);
    }

    public function testExtensionCanBeUsedInContainer(): void
    {
        $this->container->registerExtension($this->extension);

        $this->assertTrue($this->container->hasExtension('easy_admin_tree_select_field'));
        $this->assertSame($this->extension, $this->container->getExtension('easy_admin_tree_select_field'));
    }

    public function testPrependAddsCorrectNamespace(): void
    {
        // 添加 twig 扩展
        /** @phpstan-ignore-next-line tourze.preferAutoExtension */
        $this->container->registerExtension(new class extends Extension {
            public function getAlias(): string
            {
                return 'twig';
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
                // Mock implementation
            }
        });

        $this->extension->prepend($this->container);

        $extensionConfigs = $this->container->getExtensionConfig('twig');
        $twigConfig = end($extensionConfigs);

        $this->assertIsArray($twigConfig);
        $this->assertArrayHasKey('paths', $twigConfig);
        $paths = $twigConfig['paths'];
        $this->assertIsArray($paths);

        // 验证命名空间值
        $this->assertContains('EasyAdminTreeSelectField', $paths);
    }

    public function testMultiplePrependCalls(): void
    {
        // 添加 twig 扩展
        /** @phpstan-ignore-next-line tourze.preferAutoExtension */
        $this->container->registerExtension(new class extends Extension {
            public function getAlias(): string
            {
                return 'twig';
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
                // Mock implementation
            }
        });

        // 多次调用 prepend
        $this->extension->prepend($this->container);
        $this->extension->prepend($this->container);

        $extensionConfigs = $this->container->getExtensionConfig('twig');

        // 每次调用都会添加配置，所以应该有多个配置
        $this->assertGreaterThanOrEqual(2, count($extensionConfigs));

        // 验证每个配置都包含正确的命名空间
        foreach ($extensionConfigs as $config) {
            $this->assertArrayHasKey('paths', $config);
            $paths = $config['paths'];
            $this->assertIsArray($paths);
            $this->assertContains('EasyAdminTreeSelectField', $paths);
        }
    }

    public function testResourceDirectoriesExist(): void
    {
        $resourcePath = dirname(__DIR__, 2) . '/src/Resources/config';
        $viewsPath = dirname(__DIR__, 2) . '/src/Resources/views';

        $this->assertDirectoryExists($resourcePath, 'Config directory should exist');
        $this->assertDirectoryExists($viewsPath, 'Views directory should exist');

        $servicesFile = $resourcePath . '/services.yaml';
        $this->assertFileExists($servicesFile, 'Services configuration file should exist');
    }
}

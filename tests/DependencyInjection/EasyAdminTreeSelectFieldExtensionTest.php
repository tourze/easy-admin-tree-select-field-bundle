<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Tourze\EasyAdminTreeSelectFieldBundle\DependencyInjection\EasyAdminTreeSelectFieldExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * @internal
 */
#[CoversClass(EasyAdminTreeSelectFieldExtension::class)]
final class EasyAdminTreeSelectFieldExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private EasyAdminTreeSelectFieldExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new EasyAdminTreeSelectFieldExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
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

    public function testLoadMethodLoadsServices(): void
    {
        // 模拟 services.yaml 文件存在
        $resourcePath = dirname(__DIR__, 2) . '/src/Resources/config';
        $this->assertDirectoryExists($resourcePath, "Resource directory should exist: {$resourcePath}");

        $servicesFile = $resourcePath . '/services.yaml';
        $this->assertFileExists($servicesFile, "Services file should exist: {$servicesFile}");

        // 测试 load 方法不抛出异常
        $configs = [];
        $this->extension->load($configs, $this->container);

        // 验证容器状态（基本验证，因为实际服务注册需要真实的服务文件）
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testLoadWithEmptyConfigs(): void
    {
        $configs = [];

        // 应该不抛出异常
        $this->extension->load($configs, $this->container);

        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testLoadWithMultipleConfigs(): void
    {
        $configs = [
            [],  // 空配置
            [],  // 另一个空配置
        ];

        // 应该不抛出异常
        $this->extension->load($configs, $this->container);

        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
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

        // 验证路径配置的结构，而不是硬编码路径匹配
        $paths = $lastConfig['paths'];
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);

        // 验证有一个路径指向 'EasyAdminTreeSelectField' 命名空间
        $this->assertContains('EasyAdminTreeSelectField', $paths);

        // 验证路径包含正确的结尾部分
        $templatePath = array_keys($paths)[0];
        $this->assertIsString($templatePath);
        $this->assertStringEndsWith('Resources/views', $templatePath);
        $this->assertEquals('EasyAdminTreeSelectField', $paths[$templatePath]);
    }

    public function testPrependAddsCorrectTwigPath(): void
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

        // 验证路径格式和命名空间
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
        $pathKeys = array_keys($paths);
        $pathValues = array_values($paths);
        $templatePath = $pathKeys[0];
        $namespace = $pathValues[0];

        $this->assertIsString($templatePath);
        $this->assertStringEndsWith('Resources/views', $templatePath);
        $this->assertEquals('EasyAdminTreeSelectField', $namespace);

        // 验证路径实际存在 - 使用实际返回的路径而不是期望的路径
        $this->assertDirectoryExists($templatePath, "Template directory should exist: {$templatePath}");
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

        // 验证每个配置都包含正确的路径
        foreach ($extensionConfigs as $config) {
            $this->assertArrayHasKey('paths', $config);
            $paths = $config['paths'];
            $this->assertIsArray($paths);
            $this->assertCount(1, $paths);

            // 验证路径结构
            $pathKeys = array_keys($paths);
            $pathValues = array_values($paths);
            $templatePath = $pathKeys[0];
            $namespace = $pathValues[0];

            $this->assertIsString($templatePath);
            $this->assertStringEndsWith('Resources/views', $templatePath);
            $this->assertEquals('EasyAdminTreeSelectField', $namespace);
        }
    }

    public function testExtensionCanBeUsedInContainer(): void
    {
        $this->container->registerExtension($this->extension);

        $this->assertTrue($this->container->hasExtension('easy_admin_tree_select_field'));
        $this->assertSame($this->extension, $this->container->getExtension('easy_admin_tree_select_field'));
    }

    public function testLoadWithFileLocatorCreation(): void
    {
        // 验证 FileLocator 可以被正确创建
        $resourcePath = dirname(__DIR__, 2) . '/src/Resources/config';
        $fileLocator = new FileLocator($resourcePath);

        $this->assertInstanceOf(FileLocator::class, $fileLocator);

        // 验证 YamlFileLoader 可以被创建
        $loader = new YamlFileLoader($this->container, $fileLocator);
        $this->assertInstanceOf(YamlFileLoader::class, $loader);
    }

    public function testResourcePathExists(): void
    {
        $resourcePath = dirname(__DIR__, 2) . '/src/Resources/config';
        $viewsPath = dirname(__DIR__, 2) . '/src/Resources/views';

        $this->assertDirectoryExists($resourcePath, 'Config directory should exist');
        $this->assertDirectoryExists($viewsPath, 'Views directory should exist');

        $servicesFile = $resourcePath . '/services.yaml';
        $this->assertFileExists($servicesFile, 'Services configuration file should exist');
    }

    public function testTwigPathIsAbsolute(): void
    {
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
        $twigPaths = $twigConfig['paths'];
        $this->assertIsArray($twigPaths);
        $pathKeys = array_keys($twigPaths);
        $this->assertNotEmpty($pathKeys);
        $templatePath = $pathKeys[0];

        $this->assertIsString($templatePath);
        $this->assertTrue(str_starts_with($templatePath, '/') || (bool) preg_match('/^[A-Z]:/', $templatePath),
            "Path should be absolute: {$templatePath}");
    }
}

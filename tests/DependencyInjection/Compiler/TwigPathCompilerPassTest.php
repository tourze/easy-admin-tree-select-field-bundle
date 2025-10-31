<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\EasyAdminTreeSelectFieldBundle\DependencyInjection\Compiler\TwigPathCompilerPass;

/**
 * @internal
 */
#[CoversClass(TwigPathCompilerPass::class)]
final class TwigPathCompilerPassTest extends TestCase
{
    private TwigPathCompilerPass $compilerPass;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new TwigPathCompilerPass();
        $this->container = new ContainerBuilder();
    }

    public function testImplementsCompilerPassInterface(): void
    {
        $this->assertInstanceOf(CompilerPassInterface::class, $this->compilerPass);
    }

    public function testProcessWithoutTwigLoaderDefinition(): void
    {
        // 容器中没有 twig.loader.native_filesystem 定义
        $this->assertFalse($this->container->hasDefinition('twig.loader.native_filesystem'));

        // 调用 process 方法不应抛出异常
        $this->compilerPass->process($this->container);

        // 容器应该保持不变
        $this->assertFalse($this->container->hasDefinition('twig.loader.native_filesystem'));
    }

    public function testProcessWithTwigLoaderDefinition(): void
    {
        // 创建模拟的 Twig 加载器定义
        $twigLoaderDefinition = new Definition();
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        $this->assertTrue($this->container->hasDefinition('twig.loader.native_filesystem'));

        // 处理编译器传递
        $this->compilerPass->process($this->container);

        // 验证方法调用被添加
        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        $this->assertNotEmpty($methodCalls);

        // 查找 addPath 方法调用
        $addPathCallFound = false;
        // 实际路径是 TwigPathCompilerPass 类中定义的路径
        $expectedPath = dirname(__DIR__, 3) . '/src/DependencyInjection/Compiler/../../Resources/views';
        $expectedNamespace = 'EasyAdminTreeSelectField';

        foreach ($methodCalls as $methodCall) {
            [$methodName, $arguments] = $methodCall;

            if ('addPath' === $methodName) {
                $addPathCallFound = true;
                $this->assertCount(2, $arguments);
                $this->assertEquals($expectedPath, $arguments[0]);
                $this->assertEquals($expectedNamespace, $arguments[1]);
                break;
            }
        }

        $this->assertTrue($addPathCallFound, 'addPath method call should be added to twig loader definition');
    }

    public function testProcessAddsCorrectPath(): void
    {
        $twigLoaderDefinition = new Definition();
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        $this->compilerPass->process($this->container);

        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        $this->assertNotEmpty($methodCalls);

        $methodCall = $methodCalls[0];
        $methodName = $methodCall[0];
        $arguments = $methodCall[1];

        $this->assertEquals('addPath', $methodName);
        $this->assertCount(2, $arguments);

        $path = $arguments[0];
        $namespace = $arguments[1];

        $this->assertIsString($path);
        $this->assertEquals('EasyAdminTreeSelectField', $namespace);

        // 验证路径是绝对路径
        $this->assertTrue(str_starts_with($path, '/') || (bool) preg_match('/^[A-Z]:/', $path),
            "Path should be absolute: {$path}");

        // 验证路径结构正确
        $this->assertStringEndsWith('Resources/views', $path);

        // 验证路径实际存在
        $this->assertDirectoryExists($path, "Template directory should exist: {$path}");
    }

    public function testProcessWithExistingMethodCalls(): void
    {
        // 创建已有方法调用的定义
        $twigLoaderDefinition = new Definition();
        $twigLoaderDefinition->addMethodCall('someExistingMethod', ['arg1', 'arg2']);
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        $initialCallsCount = count($twigLoaderDefinition->getMethodCalls());

        $this->compilerPass->process($this->container);

        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        // 应该有原来的调用 + 新的 addPath 调用
        $this->assertCount($initialCallsCount + 1, $methodCalls);

        // 验证原有的方法调用仍然存在
        $existingCall = $methodCalls[0];
        $this->assertEquals('someExistingMethod', $existingCall[0]);
        $this->assertEquals(['arg1', 'arg2'], $existingCall[1]);

        // 验证新增的 addPath 调用
        $addPathCall = $methodCalls[1];
        $this->assertEquals('addPath', $addPathCall[0]);
    }

    public function testMultipleProcessCalls(): void
    {
        $twigLoaderDefinition = new Definition();
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        // 多次调用 process
        $this->compilerPass->process($this->container);
        $this->compilerPass->process($this->container);

        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        // 每次调用都会添加一个 addPath 调用
        $this->assertCount(2, $methodCalls);

        // 验证两个调用都是 addPath
        foreach ($methodCalls as $methodCall) {
            $this->assertEquals('addPath', $methodCall[0]);
            $this->assertCount(2, $methodCall[1]);
        }
    }

    public function testProcessWithDifferentDefinitionTypes(): void
    {
        // 测试不同类型的定义
        $twigLoaderDefinition = new Definition('Symfony\Component\Serializer\Loader\YamlFileLoader');
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        $this->compilerPass->process($this->container);

        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        $this->assertNotEmpty($methodCalls);
        $this->assertEquals('addPath', $methodCalls[0][0]);
    }

    public function testPathIsCorrectlyCalculated(): void
    {
        $twigLoaderDefinition = new Definition();
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        $this->compilerPass->process($this->container);

        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        $path = $methodCalls[0][1][0];

        // 验证路径计算是否正确
        // path 应该是 __DIR__ . '/../../Resources/views'
        // 其中 __DIR__ 是 TwigPathCompilerPass 所在目录
        $expectedPath = dirname(__DIR__, 3) . '/src/DependencyInjection/Compiler/../../Resources/views';

        $this->assertEquals($expectedPath, $path);
        $this->assertDirectoryExists($path);
    }

    public function testNamespaceIsCorrect(): void
    {
        $twigLoaderDefinition = new Definition();
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        $this->compilerPass->process($this->container);

        $definition = $this->container->getDefinition('twig.loader.native_filesystem');
        $methodCalls = $definition->getMethodCalls();

        $namespace = $methodCalls[0][1][1];

        $this->assertEquals('EasyAdminTreeSelectField', $namespace);
    }

    public function testCompilerPassCanBeInstantiated(): void
    {
        $compilerPass = new TwigPathCompilerPass();

        $this->assertInstanceOf(TwigPathCompilerPass::class, $compilerPass);
        $this->assertInstanceOf(CompilerPassInterface::class, $compilerPass);
    }

    public function testProcessDoesNotModifyOtherDefinitions(): void
    {
        // 添加其他定义
        $otherDefinition = new Definition();
        $otherDefinition->addMethodCall('someMethod', ['arg']);
        $this->container->setDefinition('some.other.service', $otherDefinition);

        // 添加 twig 加载器定义
        $twigLoaderDefinition = new Definition();
        $this->container->setDefinition('twig.loader.native_filesystem', $twigLoaderDefinition);

        // 记录处理前的状态
        $otherDefinitionBefore = clone $this->container->getDefinition('some.other.service');

        $this->compilerPass->process($this->container);

        // 验证其他定义未被修改
        $otherDefinitionAfter = $this->container->getDefinition('some.other.service');
        $this->assertEquals($otherDefinitionBefore->getMethodCalls(), $otherDefinitionAfter->getMethodCalls());

        // 验证 twig 定义被修改
        $twigDefinitionAfter = $this->container->getDefinition('twig.loader.native_filesystem');
        $this->assertNotEmpty($twigDefinitionAfter->getMethodCalls());
    }
}

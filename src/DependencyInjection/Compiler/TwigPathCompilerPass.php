<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigPathCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig.loader.native_filesystem')) {
            return;
        }

        $twigLoader = $container->getDefinition('twig.loader.native_filesystem');
        $twigLoader->addMethodCall('addPath', [
            __DIR__ . '/../../Resources/views',
            'EasyAdminTreeSelectField',
        ]);
    }
}

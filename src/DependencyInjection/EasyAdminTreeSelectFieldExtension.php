<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class EasyAdminTreeSelectFieldExtension extends AutoExtension implements PrependExtensionInterface
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        // 注册 Twig 模板路径
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    __DIR__ . '/../Resources/views' => 'EasyAdminTreeSelectField',
                ],
            ]);
        }
    }

    public function getAlias(): string
    {
        return 'easy_admin_tree_select_field';
    }
}

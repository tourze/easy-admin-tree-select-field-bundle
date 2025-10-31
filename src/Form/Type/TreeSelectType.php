<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\TreeDataProviderInterface;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\DataTransformer\IdToEntityTransformer;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNodeInterface;

/**
 * 树形选择器表单类型
 */
class TreeSelectType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 当处理多选字段且是 Collection 类型时，添加数据转换器
        $entityClass = $options['entity_class'];
        if (true === $options['multiple'] && null !== $entityClass && is_string($entityClass) && class_exists($entityClass)) {
            /** @var class-string $entityClass */
            $transformer = new CollectionToArrayTransformer(
                $this->entityManager,
                $entityClass
            );
            $builder->addModelTransformer($transformer);

            return;
        }

        // 当处理单选且目标属性是实体时，添加 ID <-> 实体 转换器
        if (false === $options['multiple'] && null !== $entityClass && is_string($entityClass) && class_exists($entityClass)) {
            /** @var class-string $entityClass */
            $builder->addModelTransformer(new IdToEntityTransformer(
                $this->entityManager,
                $entityClass
            ));
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $dataProvider = $this->getDataProvider($options);
        $treeData = $this->getTreeData($dataProvider, $options);

        $this->setViewVars($view, $options, $treeData);
        $this->setEntityData($view, $form);
    }

    /**
     * 获取数据提供者
     * @param array<string, mixed> $options
     */
    private function getDataProvider(array $options): TreeDataProviderInterface
    {
        $dataProvider = $options['data_provider'] ?? null;

        if (null === $dataProvider) {
            return new ArrayTreeDataProvider([]);
        }

        if (!$dataProvider instanceof TreeDataProviderInterface) {
            throw new \InvalidArgumentException('data_provider must implement TreeDataProviderInterface');
        }

        return $dataProvider;
    }

    /**
     * 获取树形数据
     * @param array<string, mixed> $options
     * @return array<TreeNodeInterface>
     */
    private function getTreeData(TreeDataProviderInterface $dataProvider, array $options): array
    {
        $providerOptions = $options['provider_options'] ?? [];

        if (!is_array($providerOptions)) {
            return $dataProvider->getTreeData([]);
        }

        $stringKeysOptions = $this->filterStringKeys($providerOptions);

        return $dataProvider->getTreeData($stringKeysOptions);
    }

    /**
     * 过滤字符串键
     * @param array<mixed> $options
     * @return array<string, mixed>
     */
    private function filterStringKeys(array $options): array
    {
        $stringKeysOptions = [];
        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $stringKeysOptions[$key] = $value;
            }
        }

        return $stringKeysOptions;
    }

    /**
     * 设置视图变量
     * @param array<string, mixed> $options
     * @param array<TreeNodeInterface> $treeData
     */
    private function setViewVars(FormView $view, array $options, array $treeData): void
    {
        $view->vars['tree_data'] = $this->convertNodesToArray($treeData);
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['expand_all'] = $options['expand_all'];
        $view->vars['expanded_level'] = $options['expanded_level'];
        $view->vars['searchable'] = $options['searchable'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['max_depth'] = $options['max_depth'];
        $view->vars['sortable'] = $options['sortable'];
        $view->vars['show_checkbox'] = $options['show_checkbox'];
        $view->vars['lazy_load'] = $options['lazy_load'];
        $view->vars['node_icon'] = $options['node_icon'];
        $view->vars['leaf_icon'] = $options['leaf_icon'];
        $view->vars['attr']['data-tree-select'] = 'true';

        $choices = $this->flattenTreeToChoices($treeData);
        $view->vars['choices'] = $choices;
    }

    /**
     * 设置实体数据
     */
    private function setEntityData(FormView $view, FormInterface $form): void
    {
        $originalData = $form->getData();

        if (null === $originalData) {
            $view->vars['entity_data'] = null;

            return;
        }

        if ($originalData instanceof Collection) {
            $view->vars['entity_data'] = $originalData->toArray();
        } elseif (is_object($originalData)) {
            $view->vars['entity_data'] = $originalData;
        } else {
            $view->vars['entity_data'] = null;
        }
    }

    /**
     * 将树形数据扁平化为 ChoiceType 选择列表
     *
     * @param array<TreeNodeInterface> $nodes
     * @return array<string, mixed>
     */
    private function flattenTreeToChoices(array $nodes): array
    {
        $choices = [];

        foreach ($nodes as $node) {
            $choices[$node->getLabel()] = $node->getId();

            if ([] !== $node->getChildren()) {
                $choices = array_merge($choices, $this->flattenTreeToChoices($node->getChildren()));
            }
        }

        return $choices;
    }

    /**
     * 将节点对象转换为数组格式
     *
     * @param array<TreeNodeInterface> $nodes
     * @return array<mixed>
     */
    private function convertNodesToArray(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $nodeArray = [
                'id' => $node->getId(),
                'label' => $node->getLabel(),
                'parent_id' => $node->getParentId(),
                'level' => $node->getLevel(),
                'metadata' => $node->getMetadata(),
                'selectable' => $node->isSelectable(),
                'expanded' => $node->isExpanded(),
                'is_leaf' => $node->isLeaf(),
                'children' => [],
            ];

            if ([] !== $node->getChildren()) {
                $nodeArray['children'] = $this->convertNodesToArray($node->getChildren());
            }

            $result[] = $nodeArray;
        }

        return $result;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'expanded' => false,
            'choices' => [],
            'data_provider' => null,
            'provider_options' => [],
            'entity_class' => null,
            'expand_all' => false,
            'expanded_level' => 1,
            'searchable' => true,
            'placeholder' => null,
            'max_depth' => null,
            'sortable' => false,
            'show_checkbox' => true,
            'lazy_load' => false,
            'node_icon' => null,
            'leaf_icon' => null,
        ]);

        // 动态设置 choices
        $resolver->setNormalizer('choices', function (OptionsResolver $resolver, $value) {
            $dataProvider = $resolver->offsetGet('data_provider');
            $providerOptions = $resolver->offsetGet('provider_options');

            if (null === $dataProvider || !$dataProvider instanceof TreeDataProviderInterface) {
                return [];
            }

            if (is_array($providerOptions)) {
                /** @var array<string, mixed> $stringKeysOptions */
                $stringKeysOptions = [];
                foreach ($providerOptions as $optionKey => $optionValue) {
                    if (is_string($optionKey)) {
                        $stringKeysOptions[$optionKey] = $optionValue;
                    }
                }
                $treeData = $dataProvider->getTreeData($stringKeysOptions);
            } else {
                $treeData = $dataProvider->getTreeData([]);
            }

            return $this->flattenTreeToChoices($treeData);
        });

        $resolver->setAllowedTypes('data_provider', ['null', TreeDataProviderInterface::class]);
        $resolver->setAllowedTypes('provider_options', 'array');
        $resolver->setAllowedTypes('entity_class', ['null', 'string']);
        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedTypes('expand_all', 'bool');
        $resolver->setAllowedTypes('expanded_level', ['null', 'int']);
        $resolver->setAllowedTypes('searchable', 'bool');
        $resolver->setAllowedTypes('placeholder', ['null', 'string']);
        $resolver->setAllowedTypes('max_depth', ['null', 'int']);
        $resolver->setAllowedTypes('sortable', 'bool');
        $resolver->setAllowedTypes('show_checkbox', 'bool');
        $resolver->setAllowedTypes('lazy_load', 'bool');
        $resolver->setAllowedTypes('node_icon', ['null', 'string']);
        $resolver->setAllowedTypes('leaf_icon', ['null', 'string']);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'tree_select';
    }
}

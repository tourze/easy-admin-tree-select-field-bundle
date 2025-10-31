<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectMultiField;
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectMultiField as TreeSelectField;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

// 手动加载类文件（临时解决autoload问题）
require_once __DIR__ . '/../../src/DataProvider/TreeDataProviderInterface.php';
require_once __DIR__ . '/../../src/DataProvider/AbstractTreeDataProvider.php';
require_once __DIR__ . '/../../src/DataProvider/ArrayTreeDataProvider.php';
require_once __DIR__ . '/../../src/Field/AbstractTreeSelectField.php';
require_once __DIR__ . '/../../src/Field/TreeSelectMultiField.php';

/**
 * TreeSelectField基础功能测试
 * 由于EasyAdminBundle的FieldTrait依赖复杂，这个测试只验证基本的类实例化功能
 *
 * @internal
 */
#[CoversClass(TreeSelectMultiField::class)]
#[RunTestsInSeparateProcesses]
final class TreeSelectFieldBasicTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Field 测试不需要特殊的初始化
    }

    public function testNewCreatesFieldInstance(): void
    {
        $field = TreeSelectField::new('treeProperty', 'Tree Label');

        $this->assertInstanceOf(TreeSelectField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testNewWithoutLabelCreatesField(): void
    {
        $field = TreeSelectField::new('categories');

        $this->assertInstanceOf(TreeSelectField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testSetDataProviderReturnsVoid(): void
    {
        $field = TreeSelectField::new('test');
        $mockProvider = $this->createMock(ArrayTreeDataProvider::class);

        $field->setDataProvider($mockProvider);

        // Void方法调用成功，验证field对象状态
        $this->assertInstanceOf(TreeSelectField::class, $field);
    }

    public function testSetEntityClassReturnsVoid(): void
    {
        $field = TreeSelectField::new('test');
        $entityClass = 'App\Entity\Category';

        $field->setEntityClass($entityClass);

        // Void方法调用成功，验证field对象状态
        $this->assertInstanceOf(TreeSelectField::class, $field);
    }

    public function testSetDataReturnsVoid(): void
    {
        $field = TreeSelectField::new('test');
        $data = [
            ['id' => 1, 'label' => 'Root 1', 'parent_id' => null],
            ['id' => 2, 'label' => 'Child 1.1', 'parent_id' => 1],
        ];

        $field->setData($data);

        // Void方法调用成功，验证field对象状态
        $this->assertInstanceOf(TreeSelectField::class, $field);
    }

    public function testMethodChainingWithSeparateStatements(): void
    {
        $field = TreeSelectField::new('categories', 'Categories');
        $field->setMultiple(true);
        $field->setExpandAll(true);
        $field->setExpandedLevel(2);
        $field->setSearchable(true);
        $field->setMaxDepth(5);
        $field->setPlaceholder('Select categories');
        $field->setRequired(true);
        $field->setSortable(true);
        $field->setShowCheckbox(true);
        $field->setLazyLoad(false);
        $field->setNodeIcon('fa-folder');
        $field->setLeafIcon('fa-file');

        $this->assertInstanceOf(TreeSelectField::class, $field);
    }

    public function testAllSetterMethodsReturnVoid(): void
    {
        $field = TreeSelectField::new('test');

        // 这些方法都是void，应该直接调用而不获取返回值
        $field->setMultiple(false);
        $field->setExpandAll(true);
        $field->setExpandedLevel(3);
        $field->setSearchable(false);
        $field->setMaxDepth(10);
        $field->setPlaceholder('Test');
        $field->setRequired(true);
        $field->setSortable(true);
        $field->setShowCheckbox(false);
        $field->setLazyLoad(true);
        $field->setNodeIcon('icon1');
        $field->setLeafIcon('icon2');

        // 验证field对象仍然是正确的实例
        $this->assertInstanceOf(TreeSelectField::class, $field);
    }

    public function testFieldCanBeConfiguredWithComplexData(): void
    {
        $data = [
            ['id' => 1, 'label' => 'Electronics', 'parent_id' => null],
            ['id' => 2, 'label' => 'Computers', 'parent_id' => 1],
            ['id' => 3, 'label' => 'Laptops', 'parent_id' => 2],
        ];

        $field = TreeSelectField::new('product_categories', 'Product Categories');
        $field->setData($data);
        $field->setMultiple(true);
        $field->setExpandAll(false);
        $field->setExpandedLevel(1);
        $field->setSearchable(true);
        $field->setMaxDepth(3);
        $field->setPlaceholder('Choose categories');
        $field->setRequired(false);
        $field->setSortable(false);
        $field->setShowCheckbox(true);
        $field->setLazyLoad(false);
        $field->setNodeIcon('fa-folder-open');
        $field->setLeafIcon('fa-tag');

        $this->assertInstanceOf(TreeSelectField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }
}

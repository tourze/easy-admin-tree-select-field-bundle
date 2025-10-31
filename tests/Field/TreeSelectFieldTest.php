<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\TreeDataProviderInterface;
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectMultiField;
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectMultiField as TreeSelectField;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\Type\TreeSelectType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

// 手动加载类文件（临时解决autoload问题）
require_once __DIR__ . '/../../src/DataProvider/TreeDataProviderInterface.php';
require_once __DIR__ . '/../../src/DataProvider/AbstractTreeDataProvider.php';
require_once __DIR__ . '/../../src/DataProvider/ArrayTreeDataProvider.php';
require_once __DIR__ . '/../../src/Field/AbstractTreeSelectField.php';
require_once __DIR__ . '/../../src/Field/TreeSelectMultiField.php';

/**
 * @internal
 */
#[CoversClass(TreeSelectMultiField::class)]
#[RunTestsInSeparateProcesses]
final class TreeSelectFieldTest extends AbstractIntegrationTestCase
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

    public function testNewSetsBasicProperties(): void
    {
        $field = TreeSelectField::new('treeProperty', 'Tree Label');

        $this->assertEquals('treeProperty', $field->getProperty());
        $this->assertEquals('Tree Label', $field->getLabel());
        $this->assertEquals(TreeSelectType::class, $field->getFormType());
        $this->assertStringContainsString('field-tree-select', $field->getCssClass());
        $this->assertEquals('col-md-8', $field->getDefaultColumns());
    }

    public function testNewWithoutLabelUsesPropertyName(): void
    {
        $field = TreeSelectField::new('categories');

        $this->assertEquals('categories', $field->getProperty());
        $this->assertNull($field->getLabel());
    }

    public function testSetDataProviderSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');
        $mockProvider = $this->createMock(TreeDataProviderInterface::class);

        $field->setDataProvider($mockProvider);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertSame($mockProvider, $formTypeOptions['data_provider']);
    }

    public function testSetEntityClassSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');
        $entityClass = 'App\Entity\Category';

        $field->setEntityClass($entityClass);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals($entityClass, $formTypeOptions['entity_class']);
    }

    public function testSetDataCreatesArrayDataProvider(): void
    {
        $field = TreeSelectField::new('test');
        $data = [
            ['id' => 1, 'label' => 'Root 1', 'parent_id' => null],
            ['id' => 2, 'label' => 'Child 1.1', 'parent_id' => 1],
        ];

        $field->setData($data);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertInstanceOf(ArrayTreeDataProvider::class, $formTypeOptions['data_provider']);
    }

    public function testSetMultipleSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        // 多选字段固定为 multiple=true
        $field->setMultiple(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['multiple']);

        $field->setMultiple(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['multiple']);
    }

    public function testSetExpandAllSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setExpandAll(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['expand_all']);

        // Test with false (default value)
        $field->setExpandAll(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['expand_all']);
    }

    public function testSetExpandedLevelSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setExpandedLevel(3);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals(3, $formTypeOptions['expanded_level']);

        // Test with null
        $field->setExpandedLevel(null);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertNull($formTypeOptions['expanded_level']);
    }

    public function testSetSearchableSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setSearchable(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['searchable']);

        // Test with true (default value)
        $field->setSearchable(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['searchable']);
    }

    public function testSetMaxDepthSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setMaxDepth(5);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals(5, $formTypeOptions['max_depth']);

        // Test with null
        $field->setMaxDepth(null);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertNull($formTypeOptions['max_depth']);
    }

    public function testSetPlaceholderSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');
        $placeholder = 'Select a category';

        $field->setPlaceholder($placeholder);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals($placeholder, $formTypeOptions['placeholder']);

        // Test with null
        $field->setPlaceholder(null);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertNull($formTypeOptions['placeholder']);
    }

    public function testSetRequiredSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setRequired(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['required']);

        // Test with false (default value)
        $field->setRequired(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['required']);
    }

    public function testSetSortableSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setSortable(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['sortable']);

        // Test with false (default value)
        $field->setSortable(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['sortable']);
    }

    public function testSetShowCheckboxSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setShowCheckbox(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['show_checkbox']);

        // Test with true (default value)
        $field->setShowCheckbox(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['show_checkbox']);
    }

    public function testSetLazyLoadSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');

        $field->setLazyLoad(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['lazy_load']);

        // Test with false (default value)
        $field->setLazyLoad(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['lazy_load']);
    }

    public function testSetNodeIconSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');
        $icon = 'fa-folder';

        $field->setNodeIcon($icon);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals($icon, $formTypeOptions['node_icon']);

        // Test with null
        $field->setNodeIcon(null);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertNull($formTypeOptions['node_icon']);
    }

    public function testSetLeafIconSetsFormTypeOption(): void
    {
        $field = TreeSelectField::new('test');
        $icon = 'fa-file';

        $field->setLeafIcon($icon);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals($icon, $formTypeOptions['leaf_icon']);

        // Test with null
        $field->setLeafIcon(null);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertNull($formTypeOptions['leaf_icon']);
    }

    public function testMethodChaining(): void
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

        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['multiple']);
        $this->assertTrue($formTypeOptions['expand_all']);
        $this->assertEquals(2, $formTypeOptions['expanded_level']);
        $this->assertTrue($formTypeOptions['searchable']);
        $this->assertEquals(5, $formTypeOptions['max_depth']);
        $this->assertEquals('Select categories', $formTypeOptions['placeholder']);
        $this->assertTrue($formTypeOptions['required']);
        $this->assertTrue($formTypeOptions['sortable']);
        $this->assertTrue($formTypeOptions['show_checkbox']);
        $this->assertFalse($formTypeOptions['lazy_load']);
        $this->assertEquals('fa-folder', $formTypeOptions['node_icon']);
        $this->assertEquals('fa-file', $formTypeOptions['leaf_icon']);
    }

    public function testCompleteConfiguration(): void
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

        $this->assertEquals('product_categories', $field->getProperty());
        $this->assertEquals('Product Categories', $field->getLabel());
        $this->assertEquals(TreeSelectType::class, $field->getFormType());

        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertInstanceOf(ArrayTreeDataProvider::class, $formTypeOptions['data_provider']);
        $this->assertTrue($formTypeOptions['multiple']);
        $this->assertFalse($formTypeOptions['expand_all']);
        $this->assertEquals(1, $formTypeOptions['expanded_level']);
        $this->assertTrue($formTypeOptions['searchable']);
        $this->assertEquals(3, $formTypeOptions['max_depth']);
        $this->assertEquals('Choose categories', $formTypeOptions['placeholder']);
        $this->assertFalse($formTypeOptions['required']);
        $this->assertFalse($formTypeOptions['sortable']);
        $this->assertTrue($formTypeOptions['show_checkbox']);
        $this->assertFalse($formTypeOptions['lazy_load']);
        $this->assertEquals('fa-folder-open', $formTypeOptions['node_icon']);
        $this->assertEquals('fa-tag', $formTypeOptions['leaf_icon']);
    }
}

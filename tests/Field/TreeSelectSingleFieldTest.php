<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\TreeDataProviderInterface;
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectSingleField;
use Tourze\EasyAdminTreeSelectFieldBundle\Form\Type\TreeSelectType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TreeSelectSingleField::class)]
#[RunTestsInSeparateProcesses]
final class TreeSelectSingleFieldTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Field 测试不需要特殊的初始化
    }

    public function testNewCreatesFieldInstance(): void
    {
        $field = TreeSelectSingleField::new('treeProperty', 'Tree Label');

        $this->assertInstanceOf(TreeSelectSingleField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testNewSetsBasicProperties(): void
    {
        $field = TreeSelectSingleField::new('treeProperty', 'Tree Label');

        $this->assertEquals('treeProperty', $field->getProperty());
        $this->assertEquals('Tree Label', $field->getLabel());
        $this->assertEquals(TreeSelectType::class, $field->getFormType());
        $this->assertStringContainsString('field-tree-select', $field->getCssClass());
        $this->assertEquals('col-md-8', $field->getDefaultColumns());
    }

    public function testNewWithoutLabelUsesPropertyName(): void
    {
        $field = TreeSelectSingleField::new('category');

        $this->assertEquals('category', $field->getProperty());
        $this->assertNull($field->getLabel());
    }

    public function testSetDataProviderSetsFormTypeOption(): void
    {
        $field = TreeSelectSingleField::new('test');
        $mockProvider = $this->createMock(TreeDataProviderInterface::class);

        $field->setDataProvider($mockProvider);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertSame($mockProvider, $formTypeOptions['data_provider']);
    }

    public function testSetEntityClassSetsFormTypeOption(): void
    {
        $field = TreeSelectSingleField::new('test');
        $entityClass = 'App\Entity\Category';

        $field->setEntityClass($entityClass);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals($entityClass, $formTypeOptions['entity_class']);
    }

    public function testSetDataCreatesArrayDataProvider(): void
    {
        $field = TreeSelectSingleField::new('test');
        $data = [
            ['id' => 1, 'label' => 'Root 1', 'parent_id' => null],
            ['id' => 2, 'label' => 'Child 1.1', 'parent_id' => 1],
        ];

        $field->setData($data);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertInstanceOf(ArrayTreeDataProvider::class, $formTypeOptions['data_provider']);
    }

    public function testMultipleIsAlwaysFalse(): void
    {
        $field = TreeSelectSingleField::new('test');

        // 单选字段固定为 multiple=false，即使尝试设为true也会保持false
        $field->setMultiple(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['multiple']);

        $field->setMultiple(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['multiple']);
    }

    public function testSetExpandAllSetsFormTypeOption(): void
    {
        $field = TreeSelectSingleField::new('test');

        $field->setExpandAll(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['expand_all']);

        $field->setExpandAll(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['expand_all']);
    }

    public function testSetExpandedLevelSetsFormTypeOption(): void
    {
        $field = TreeSelectSingleField::new('test');

        $field->setExpandedLevel(2);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertEquals(2, $formTypeOptions['expanded_level']);

        $field->setExpandedLevel(null);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertNull($formTypeOptions['expanded_level']);
    }

    public function testSetSearchableSetsFormTypeOption(): void
    {
        $field = TreeSelectSingleField::new('test');

        $field->setSearchable(false);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertFalse($formTypeOptions['searchable']);

        $field->setSearchable(true);
        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertTrue($formTypeOptions['searchable']);
    }

    public function testMethodChaining(): void
    {
        $field = TreeSelectSingleField::new('category', 'Category');
        $field->setMultiple(true); // 这会被固化为 false
        $field->setExpandAll(true);
        $field->setExpandedLevel(2);
        $field->setSearchable(true);
        $field->setMaxDepth(5);
        $field->setPlaceholder('Select a category');
        $field->setRequired(true);
        $field->setSortable(true);
        $field->setShowCheckbox(false);
        $field->setLazyLoad(false);
        $field->setNodeIcon('fa-folder');
        $field->setLeafIcon('fa-file');

        $this->assertInstanceOf(TreeSelectSingleField::class, $field);

        $formTypeOptions = $field->getFormTypeOptions();
        // 注意：即使传入 true，单选字段也会固化为 false
        $this->assertFalse($formTypeOptions['multiple']);
        $this->assertTrue($formTypeOptions['expand_all']);
        $this->assertEquals(2, $formTypeOptions['expanded_level']);
        $this->assertTrue($formTypeOptions['searchable']);
        $this->assertEquals(5, $formTypeOptions['max_depth']);
        $this->assertEquals('Select a category', $formTypeOptions['placeholder']);
        $this->assertTrue($formTypeOptions['required']);
        $this->assertTrue($formTypeOptions['sortable']);
        $this->assertFalse($formTypeOptions['show_checkbox']);
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

        $field = TreeSelectSingleField::new('product_category', 'Product Category');
        $field->setData($data);
        $field->setMultiple(true); // 这会被固化为 false
        $field->setExpandAll(false);
        $field->setExpandedLevel(1);
        $field->setSearchable(true);
        $field->setMaxDepth(3);
        $field->setPlaceholder('Choose a category');
        $field->setRequired(false);
        $field->setSortable(false);
        $field->setShowCheckbox(false);
        $field->setLazyLoad(false);
        $field->setNodeIcon('fa-folder-open');
        $field->setLeafIcon('fa-tag');

        $this->assertEquals('product_category', $field->getProperty());
        $this->assertEquals('Product Category', $field->getLabel());
        $this->assertEquals(TreeSelectType::class, $field->getFormType());

        $formTypeOptions = $field->getFormTypeOptions();
        $this->assertInstanceOf(ArrayTreeDataProvider::class, $formTypeOptions['data_provider']);
        // 注意：即使传入 true，单选字段也会固化为 false
        $this->assertFalse($formTypeOptions['multiple']);
        $this->assertFalse($formTypeOptions['expand_all']);
        $this->assertEquals(1, $formTypeOptions['expanded_level']);
        $this->assertTrue($formTypeOptions['searchable']);
        $this->assertEquals(3, $formTypeOptions['max_depth']);
        $this->assertEquals('Choose a category', $formTypeOptions['placeholder']);
        $this->assertFalse($formTypeOptions['required']);
        $this->assertFalse($formTypeOptions['sortable']);
        $this->assertFalse($formTypeOptions['show_checkbox']);
        $this->assertFalse($formTypeOptions['lazy_load']);
        $this->assertEquals('fa-folder-open', $formTypeOptions['node_icon']);
        $this->assertEquals('fa-tag', $formTypeOptions['leaf_icon']);
    }
}

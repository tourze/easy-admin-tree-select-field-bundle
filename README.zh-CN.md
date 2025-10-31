# EasyAdmin 树选择字段（Tree Select）

[English](README.md) | [中文](README.zh-CN.md)

为 EasyAdmin 4 提供的树形选择器字段，交互参考 Ant Design TreeSelect，支持单选/多选、搜索、层级展开等能力。

## 特性

- 树形层级展示：父子节点展开/收起
- 支持单选与多选：单选（Radio）与多选（Checkbox）
- 即时搜索：高亮匹配并自动展开父节点
- 多数据源：数组、Doctrine Entity、自定义 Provider
- 可配置：默认展开层级、占位符、最大深度、是否显示复选框、懒加载等
- EasyAdmin 原生集成：一行配置即可使用

## 安装

```bash
composer require tourze/easy-admin-tree-select-field-bundle
php bin/console assets:install --symlink
```

在 `config/bundles.php` 中启用：

```php
return [
    // ...
    Tourze\EasyAdminTreeSelectFieldBundle\EasyAdminTreeSelectFieldBundle::class => ['all' => true],
];
```

> 本 Bundle 会自动：
> - 注册 Twig 视图命名空间 `@EasyAdminTreeSelectField`
> - 注入表单 Theme `@EasyAdminTreeSelectField/form/tree_select_theme.html.twig`
> - 注入前端资源 `bundles/easyadmintreeselectfield/js/tree-select.js` 与 `css/tree-select.css`

## 基本用法

```php
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectMultiField; // 多选
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectSingleField; // 单选

public function configureFields(string $pageName): iterable
{
    // 多选
    yield TreeSelectMultiField::new('categories', '分类')
        ->setData([
            ['id' => 1, 'label' => 'Node1',       'parent_id' => null],
            ['id' => 2, 'label' => 'Node2',       'parent_id' => null],
            ['id' => 3, 'label' => 'Child Node3', 'parent_id' => 2],
            ['id' => 4, 'label' => 'Child Node4', 'parent_id' => 2],
            ['id' => 5, 'label' => 'Child Node5', 'parent_id' => 2],
        ])
        ->setSearchable(true)
        ->setExpandedLevel(1);

    // 单选
    yield TreeSelectSingleField::new('parentCategory', '父分类')
        ->setData([
            ['id' => 1, 'label' => 'Node1', 'parent_id' => null],
            ['id' => 2, 'label' => 'Node2', 'parent_id' => null],
        ])
        ->setPlaceholder('请选择父分类');
}
```

## 使用 Entity 数据源

```php
use Doctrine\ORM\EntityManagerInterface;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\EntityTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectMultiField;

public function __construct(private EntityManagerInterface $em) {}

public function configureFields(string $pageName): iterable
{
    // 多选（比如 ManyToMany: Product.categories）
    yield TreeSelectMultiField::new('categories')
        ->setDataProvider(new EntityTreeDataProvider(
            $this->em,
            Category::class,
            [
                'id_field' => 'id',
                'label_field' => 'name',
                'parent_field' => 'parent'
            ]
        ))
        ->setEntityClass(Category::class)
        ->setPlaceholder('请选择分类');

    // 单选（比如 ManyToOne: Category.parent）
    yield TreeSelectSingleField::new('parent')
        ->setDataProvider(new EntityTreeDataProvider(
            $this->em,
            Category::class,
            [
                'id_field' => 'id',
                'label_field' => 'name',
                'parent_field' => 'parent'
            ]
        ))
        ->setEntityClass(Category::class) // 单选时提供 entity_class，将自动进行 ID<->Entity 转换
        ->setPlaceholder('请选择父分类');
}
```

## 常用配置

```php
// 多选
TreeSelectMultiField::new('field')
    ->setDataProvider($provider)
    ->setData($array)
    ->setSearchable(true)
    ->setExpandedLevel(2)
    ->setExpandAll(false)
    ->setShowCheckbox(true)
    ->setPlaceholder('请选择…')
    ->setMaxDepth(null)
    ->setLazyLoad(false)
    ->setSortable(false)
    ->setNodeIcon('fa fa-folder')
    ->setLeafIcon('fa fa-file');

// 单选
TreeSelectSingleField::new('field')
    ->setSearchable(true)
    ->setExpandedLevel(2)
    ->setPlaceholder('请选择…');
```

## 说明

本 Bundle 提供两个字段类型：`TreeSelectSingleField` 与 `TreeSelectMultiField`，分别对应单选与多选的语义，不再提供混合可配置的旧字段名。

## 故障排除

- 界面没有样式/交互：请确认执行过 `php bin/console assets:install --symlink`，并清缓存。
- 树不显示：检查数据的 `id` 与 `parent_id` 是否正确；确保形成有向无环层级。

## 许可

MIT 许可证 (MIT)。详见 [LICENSE](LICENSE)。

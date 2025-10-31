# EasyAdmin Tree Select Field Bundle

[English](README.md) | [中文](README.zh-CN.md)

一个为 EasyAdmin 4 提供树形选择器字段的 Symfony Bundle，支持多种数据源。

## 特性

- 🌳 **树形结构显示** - 直观的层级展示
- 🔍 **实时搜索** - 快速查找节点
- ✅ **多选/单选** - 灵活的选择模式
- 📱 **响应式设计** - 适配移动端
- 🎨 **主题支持** - 支持亮色/暗色模式
- 🔌 **多数据源** - 支持数组、Entity、回调函数等多种数据源
- ⚡ **懒加载** - 支持大数据集的按需加载
- 🎯 **易于集成** - 与 EasyAdmin 无缝集成

## 安装

```bash
composer require tourze/easy-admin-tree-select-field-bundle
```

## 配置

在 `config/bundles.php` 中注册 Bundle：

```php
return [
    // ...
    Tourze\EasyAdminTreeSelectFieldBundle\EasyAdminTreeSelectFieldBundle::class => ['all' => true],
];
```

安装资源文件：

```bash
php bin/console assets:install
```

## 使用方法

### 基本用法

在 EasyAdmin CRUD 控制器中使用：

```php
use Tourze\EasyAdminTreeSelectFieldBundle\Field\TreeSelectField;

public function configureFields(string $pageName): iterable
{
    yield TreeSelectField::new('categories')
        ->setData([
            ['id' => 1, 'label' => '根节点', 'parent_id' => null],
            ['id' => 2, 'label' => '子节点1', 'parent_id' => 1],
            ['id' => 3, 'label' => '子节点2', 'parent_id' => 1],
            ['id' => 4, 'label' => '孙节点', 'parent_id' => 2],
        ])
        ->setMultiple(true)
        ->setSearchable(true);
}
```

### 使用数组数据源

```php
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;

yield TreeSelectField::new('categories')
    ->setDataProvider(new ArrayTreeDataProvider([
        ['id' => 1, 'name' => '电子产品', 'parent_id' => null],
        ['id' => 2, 'name' => '手机', 'parent_id' => 1],
        ['id' => 3, 'name' => '电脑', 'parent_id' => 1],
        ['id' => 4, 'name' => '智能手机', 'parent_id' => 2],
        ['id' => 5, 'name' => '功能手机', 'parent_id' => 2],
    ]))
    ->setMultiple(true);
```

### 使用 Entity 数据源

```php
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\EntityTreeDataProvider;

public function __construct(
    private EntityManagerInterface $entityManager
) {}

public function configureFields(string $pageName): iterable
{
    yield TreeSelectField::new('categories')
        ->setDataProvider(new EntityTreeDataProvider(
            $this->entityManager,
            Category::class,
            [
                'id_field' => 'id',
                'label_field' => 'name',
                'parent_field' => 'parent'
            ]
        ))
        ->setMultiple(true)
        ->setExpandedLevel(2);
}
```

### 使用自定义数据提供者

创建自定义数据提供者：

```php
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\AbstractTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNode;

class CustomTreeDataProvider extends AbstractTreeDataProvider
{
    public function getTreeData(array $options = []): array
    {
        // 从你的数据源获取数据
        $data = $this->fetchDataFromSource();
        
        // 转换为树节点
        $nodes = [];
        foreach ($data as $item) {
            $node = new TreeNode(
                $item['id'],
                $item['name'],
                $item['parent_id']
            );
            $nodes[] = $node;
        }
        
        return $this->buildTree($nodes);
    }
    
    public function getRootNodes(): array
    {
        // 返回根节点
    }
    
    public function getChildrenNodes(mixed $parentId): array
    {
        // 返回子节点
    }
}
```

使用自定义数据提供者：

```php
yield TreeSelectField::new('categories')
    ->setDataProvider(new CustomTreeDataProvider())
    ->setMultiple(true);
```

## 配置选项

TreeSelectField 提供了丰富的配置选项：

```php
TreeSelectField::new('field_name')
    // 数据相关
    ->setDataProvider($provider)        // 设置数据提供者
    ->setData($arrayData)               // 直接设置数组数据
    
    // 选择相关
    ->setMultiple(true)                 // 是否多选
    ->setRequired(false)                // 是否必填
    ->setPlaceholder('请选择...')        // 占位符
    
    // 显示相关
    ->setExpandAll(false)               // 是否默认展开所有节点
    ->setExpandedLevel(2)               // 默认展开层级
    ->setMaxDepth(5)                    // 最大深度限制
    ->setShowCheckbox(true)             // 是否显示复选框
    
    // 功能相关
    ->setSearchable(true)               // 是否可搜索
    ->setSortable(false)                // 是否可排序
    ->setLazyLoad(false)                // 是否懒加载
    
    // 图标相关
    ->setNodeIcon('fa fa-folder')       // 节点图标
    ->setLeafIcon('fa fa-file')         // 叶子节点图标
;
```

## 节点数据格式

节点数据支持以下格式：

```php
[
    'id' => 1,                          // 节点ID（必需）
    'label' => '节点名称',               // 显示标签（必需）
    'parent_id' => null,                // 父节点ID
    'selectable' => true,               // 是否可选
    'expanded' => false,                // 是否默认展开
    'metadata' => [                     // 额外元数据
        'badge' => '新',
        'color' => 'primary'
    ]
]
```

## 事件监听

在 JavaScript 中监听选择变化事件：

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const treeSelect = document.querySelector('.tree-select-widget');
    const originalSelect = treeSelect.querySelector('select');
    
    originalSelect.addEventListener('change', function(e) {
        console.log('Selected values:', e.target.value);
    });
});
```

## 样式自定义

可以通过 CSS 变量自定义样式：

```css
.tree-select-widget {
    --tree-border-color: #dee2e6;
    --tree-hover-bg: #f8f9fa;
    --tree-selected-bg: #e7f3ff;
    --tree-search-highlight: #fff3cd;
}
```

## 高级用法

### 异步加载数据

```php
class AsyncTreeDataProvider extends AbstractTreeDataProvider
{
    public function getTreeData(array $options = []): array
    {
        // 只加载根节点
        return $this->getRootNodes();
    }
    
    public function getChildrenNodes(mixed $parentId): array
    {
        // 按需加载子节点
        // 这可以通过 AJAX 请求实现
        return $this->loadChildrenFromApi($parentId);
    }
}
```

### 节点权限控制

```php
class SecureTreeDataProvider extends AbstractTreeDataProvider
{
    public function getTreeData(array $options = []): array
    {
        $nodes = parent::getTreeData($options);
        
        // 根据用户权限过滤节点
        return array_filter($nodes, function($node) {
            return $this->userHasAccessToNode($node);
        });
    }
    
    private function userHasAccessToNode($node): bool
    {
        // 检查用户权限
        return true;
    }
}
```

## 故障排除

### 资源文件未加载

确保已执行：

```bash
php bin/console assets:install --symlink
php bin/console cache:clear
```

### 树形结构不显示

检查数据格式是否正确，特别是 `parent_id` 字段。

### 搜索功能不工作

确保设置了 `->setSearchable(true)`。

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License
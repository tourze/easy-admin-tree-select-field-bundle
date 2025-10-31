# Repository Classes

本目录包含 `TestTreeEntity` 的 Repository 类和相关测试。

## 文件说明

- `TestTreeEntityRepositoryInterface.php` - Repository 接口定义
- `TestTreeEntityRepository.php` - Repository 实现类
- `TestTreeEntityRepositoryTest.php` - Repository 单元测试
- `README.md` - 本使用说明文档

## TestTreeEntityRepository 功能

### 基础查询方法

- `findRootNodes()` - 查找所有根节点（无父节点）
- `findDirectChildren(TestTreeEntity $parent)` - 查找指定节点的直接子节点
- `findActiveNodes()` - 查找所有活跃节点
- `findActiveRootNodes()` - 查找活跃的根节点
- `findByNameLike(string $name)` - 按名称模糊搜索节点

### 树形结构查询

- `findAllDescendants(TestTreeEntity $parent)` - 查找所有子孙节点（递归）
- `findPath(TestTreeEntity $node)` - 查找节点路径（从根到指定节点）
- `findByDepth(int $depth)` - 查找指定深度的所有节点
- `getTreeStructure()` - 获取完整树形结构数据

### 树形数据分析

- `getNodeDepth(TestTreeEntity $node)` - 获取节点深度
- `isLeafNode(TestTreeEntity $node)` - 检查是否为叶子节点
- `getChildrenCount(TestTreeEntity $node)` - 获取子节点数量
- `getMaxDepth()` - 获取树的最大深度

### 数据操作

- `save(TestTreeEntity $entity, bool $flush = false)` - 保存实体
- `remove(TestTreeEntity $entity, bool $flush = false)` - 删除实体
- `createBaseQueryBuilder(string $alias = 't')` - 创建基础查询构建器

## 使用示例

### 在服务中注入 Repository

```php
use Tourze\EasyAdminTreeSelectFieldBundle\Tests\Repository\TestTreeEntityRepositoryInterface;

class TreeService
{
    public function __construct(
        private TestTreeEntityRepositoryInterface $repository
    ) {
    }

    public function getRootCategories(): array
    {
        return $this->repository->findActiveRootNodes();
    }

    public function getFullTree(): array
    {
        return $this->repository->getTreeStructure();
    }
}
```

### 在控制器中使用

```php
use Tourze\EasyAdminTreeSelectFieldBundle\Tests\Repository\TestTreeEntityRepository;

class TreeController
{
    public function index(TestTreeEntityRepository $repository): Response
    {
        $treeData = $repository->getTreeStructure();
        
        return $this->render('tree/index.html.twig', [
            'tree' => $treeData,
        ]);
    }
}
```

### 查询示例

```php
// 获取所有根节点
$roots = $repository->findRootNodes();

// 获取某节点的子节点
$children = $repository->findDirectChildren($parentNode);

// 搜索节点
$results = $repository->findByNameLike('电子');

// 获取节点路径
$path = $repository->findPath($someNode);

// 检查是否为叶子节点
$isLeaf = $repository->isLeafNode($node);

// 获取树形结构
$treeStructure = $repository->getTreeStructure();
```

## 性能优化

### 数据库索引

Repository 利用迁移文件中创建的索引来优化查询性能：

- `parent_id` 索引 - 优化父子关系查询
- `name` 索引 - 优化名称搜索
- `active` 索引 - 优化状态过滤
- `sort_order` 索引 - 优化排序
- 组合索引 - 优化复杂查询

### 递归查询

对于深层树形结构，Repository 使用 **递归 CTE (Common Table Expressions)** 来高效查询：

- `findAllDescendants()` - 使用递归 CTE 查找所有子孙节点
- `findByDepth()` - 使用递归 CTE 查找指定深度节点
- `getMaxDepth()` - 使用递归 CTE 计算最大深度

### 查询优化建议

1. **批量加载**: 使用 `getTreeStructure()` 一次性获取完整树结构
2. **懒加载**: 对于大型树结构，考虑按需加载子节点
3. **缓存**: 对于不经常变化的树结构，考虑使用缓存
4. **分页**: 对于大量节点，在根级别使用分页

## 测试

运行 Repository 测试：

```bash
# 运行所有Repository测试
php bin/phpunit tests/Repository/TestTreeEntityRepositoryTest.php

# 运行特定测试方法
php bin/phpunit tests/Repository/TestTreeEntityRepositoryTest.php --filter testFindRootNodes
```

## 扩展

要添加新的查询方法：

1. 在 `TestTreeEntityRepositoryInterface` 中定义方法
2. 在 `TestTreeEntityRepository` 中实现方法
3. 在 `TestTreeEntityRepositoryTest` 中添加测试用例

## 注意事项

1. **数据库兼容性**: 递归 CTE 需要 MySQL 8.0+ 或 PostgreSQL 支持
2. **性能考量**: 深层递归查询可能影响性能，建议限制递归深度
3. **事务处理**: 在批量操作时建议使用数据库事务
4. **内存使用**: 大型树结构可能消耗大量内存，考虑流式处理
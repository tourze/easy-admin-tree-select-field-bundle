# DataFixtures

本目录包含用于测试和演示 TreeSelect 组件的数据固件。

## 包含的 Fixtures

### 1. TestTreeEntityFixture

基础的树形测试数据，包含以下结构：

- **电子产品**
  - 电脑
    - 笔记本电脑
    - 台式机  
    - 电脑配件
  - 手机
    - 智能手机
    - 手机配件
  - 音响设备

- **图书**
  - 编程书籍
    - Web开发
    - 移动开发
  - 文学作品
  - 科技书籍

- **服装**
  - 男装
  - 女装
  - 童装

### 2. TreeSelectDemoFixture

更复杂的演示数据，包含：

- **组织架构**：多层级公司组织结构
- **地理区域**：全球地理位置分类树
- **技能树**：程序员技能分类
- **边界测试**：特殊字符、长名称、Unicode 等测试用例

## 使用方法

### 在测试中使用

```php
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

// 加载固件
$loader = new Loader();
$loader->addFixture(new TestTreeEntityFixtures());

$purger = new ORMPurger();
$executor = new ORMExecutor($this->entityManager, $purger);
$executor->execute($loader->getFixtures());
```

### 在 Symfony 应用中使用

如果你的项目安装了 `doctrine/doctrine-fixtures-bundle`：

```bash
# 加载所有固件
php bin/console doctrine:fixtures:load

# 只加载特定固件
php bin/console doctrine:fixtures:load --fixtures=src/DataFixtures/TestTreeEntityFixtures.php
```

## 数据特点

- 包含不同深度的层级结构（1-4层）
- 包含活跃和不活跃的节点
- 涵盖各种排序场景
- 包含特殊字符和 Unicode 测试用例
- 提供完整的引用关系

## 注意事项

1. 这些 Fixture 依赖于 `TestTreeEntity` 类
2. 确保在运行前已正确配置 Doctrine ORM
3. 大型演示数据可能需要较长加载时间
4. 测试环境建议使用内存数据库以提高性能
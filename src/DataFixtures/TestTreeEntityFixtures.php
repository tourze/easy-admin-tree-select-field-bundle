<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;

/**
 * 测试树形实体的数据固件
 * 创建完整的树形结构用于测试TreeSelect功能
 */
class TestTreeEntityFixtures extends Fixture
{
    public const ELECTRONICS = 'electronics';
    public const BOOKS = 'books';
    public const CLOTHING = 'clothing';
    public const COMPUTERS = 'computers';
    public const PHONES = 'phones';
    public const AUDIO = 'audio';

    public function load(ObjectManager $manager): void
    {
        // 根节点
        $electronics = new TestTreeEntity();
        $electronics->setName('电子产品');
        $electronics->setDescription('所有电子产品分类');
        $electronics->setActive(true);
        $electronics->setSortOrder(1);
        $manager->persist($electronics);
        $this->addReference(self::ELECTRONICS, $electronics);

        $books = new TestTreeEntity();
        $books->setName('图书');
        $books->setDescription('各类图书分类');
        $books->setActive(true);
        $books->setSortOrder(2);
        $manager->persist($books);
        $this->addReference(self::BOOKS, $books);

        $clothing = new TestTreeEntity();
        $clothing->setName('服装');
        $clothing->setDescription('服装配饰类');
        $clothing->setActive(true);
        $clothing->setSortOrder(3);
        $manager->persist($clothing);
        $this->addReference(self::CLOTHING, $clothing);

        // 电子产品子分类
        $computers = new TestTreeEntity();
        $computers->setName('电脑');
        $computers->setDescription('电脑及相关配件');
        $computers->setParent($electronics);
        $computers->setActive(true);
        $computers->setSortOrder(1);
        $manager->persist($computers);
        $this->addReference(self::COMPUTERS, $computers);

        $phones = new TestTreeEntity();
        $phones->setName('手机');
        $phones->setDescription('手机及配件');
        $phones->setParent($electronics);
        $phones->setActive(true);
        $phones->setSortOrder(2);
        $manager->persist($phones);
        $this->addReference(self::PHONES, $phones);

        $audio = new TestTreeEntity();
        $audio->setName('音响设备');
        $audio->setDescription('音响、耳机等音频设备');
        $audio->setParent($electronics);
        $audio->setActive(true);
        $audio->setSortOrder(3);
        $manager->persist($audio);
        $this->addReference(self::AUDIO, $audio);

        // 电脑子分类
        $laptops = new TestTreeEntity();
        $laptops->setName('笔记本电脑');
        $laptops->setDescription('便携式笔记本电脑');
        $laptops->setParent($computers);
        $laptops->setActive(true);
        $laptops->setSortOrder(1);
        $manager->persist($laptops);

        $desktops = new TestTreeEntity();
        $desktops->setName('台式机');
        $desktops->setDescription('桌面台式电脑');
        $desktops->setParent($computers);
        $desktops->setActive(true);
        $desktops->setSortOrder(2);
        $manager->persist($desktops);

        $accessories = new TestTreeEntity();
        $accessories->setName('电脑配件');
        $accessories->setDescription('键盘、鼠标、显示器等');
        $accessories->setParent($computers);
        $accessories->setActive(true);
        $accessories->setSortOrder(3);
        $manager->persist($accessories);

        // 手机子分类
        $smartphones = new TestTreeEntity();
        $smartphones->setName('智能手机');
        $smartphones->setDescription('各品牌智能手机');
        $smartphones->setParent($phones);
        $smartphones->setActive(true);
        $smartphones->setSortOrder(1);
        $manager->persist($smartphones);

        $phoneAccessories = new TestTreeEntity();
        $phoneAccessories->setName('手机配件');
        $phoneAccessories->setDescription('手机壳、充电器等');
        $phoneAccessories->setParent($phones);
        $phoneAccessories->setActive(true);
        $phoneAccessories->setSortOrder(2);
        $manager->persist($phoneAccessories);

        // 图书分类
        $programming = new TestTreeEntity();
        $programming->setName('编程书籍');
        $programming->setDescription('编程和软件开发相关书籍');
        $programming->setParent($books);
        $programming->setActive(true);
        $programming->setSortOrder(1);
        $manager->persist($programming);

        $literature = new TestTreeEntity();
        $literature->setName('文学作品');
        $literature->setDescription('小说、诗歌、散文等文学作品');
        $literature->setParent($books);
        $literature->setActive(true);
        $literature->setSortOrder(2);
        $manager->persist($literature);

        $science = new TestTreeEntity();
        $science->setName('科技书籍');
        $science->setDescription('科学技术类专业书籍');
        $science->setParent($books);
        $science->setActive(true);
        $science->setSortOrder(3);
        $manager->persist($science);

        // 编程书籍子分类
        $webDev = new TestTreeEntity();
        $webDev->setName('Web开发');
        $webDev->setDescription('前端、后端Web开发技术');
        $webDev->setParent($programming);
        $webDev->setActive(true);
        $webDev->setSortOrder(1);
        $manager->persist($webDev);

        $mobile = new TestTreeEntity();
        $mobile->setName('移动开发');
        $mobile->setDescription('iOS、Android移动应用开发');
        $mobile->setParent($programming);
        $mobile->setActive(true);
        $mobile->setSortOrder(2);
        $manager->persist($mobile);

        // 服装分类
        $menClothing = new TestTreeEntity();
        $menClothing->setName('男装');
        $menClothing->setDescription('男性服装');
        $menClothing->setParent($clothing);
        $menClothing->setActive(true);
        $menClothing->setSortOrder(1);
        $manager->persist($menClothing);

        $womenClothing = new TestTreeEntity();
        $womenClothing->setName('女装');
        $womenClothing->setDescription('女性服装');
        $womenClothing->setParent($clothing);
        $womenClothing->setActive(true);
        $womenClothing->setSortOrder(2);
        $manager->persist($womenClothing);

        $kidsClothing = new TestTreeEntity();
        $kidsClothing->setName('童装');
        $kidsClothing->setDescription('儿童服装');
        $kidsClothing->setParent($clothing);
        $kidsClothing->setActive(true);
        $kidsClothing->setSortOrder(3);
        $manager->persist($kidsClothing);

        // 添加一些不活跃的测试数据
        $inactiveCategory = new TestTreeEntity();
        $inactiveCategory->setName('已停用分类');
        $inactiveCategory->setDescription('用于测试不活跃状态的分类');
        $inactiveCategory->setActive(false);
        $inactiveCategory->setSortOrder(99);
        $manager->persist($inactiveCategory);

        $manager->flush();
    }
}

<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;

/**
 * TreeSelect组件演示数据固件
 * 创建用于演示各种TreeSelect功能场景的数据
 */
class TreeSelectDemoFixtures extends Fixture
{
    public const ORG_ROOT = 'org-root';
    public const GEOGRAPHY_ROOT = 'geography-root';
    public const SKILLS_ROOT = 'skills-root';
    public const EDGE_CASES_ROOT = 'edge-cases-root';
    public const CHINA = 'china';
    public const FRONTEND_SKILLS = 'frontend-skills';
    public const BACKEND_SKILLS = 'backend-skills';

    public function load(ObjectManager $manager): void
    {
        $this->createOrganizationStructure($manager);
        $this->createGeographyStructure($manager);
        $this->createSkillsStructure($manager);
        $this->createEdgeCasesStructure($manager);

        $manager->flush();
    }

    private function createOrganizationStructure(ObjectManager $manager): void
    {
        // 创建大型层级结构用于测试性能和深层嵌套
        $orgRoot = new TestTreeEntity();
        $orgRoot->setName('总公司');
        $orgRoot->setDescription('集团总部');
        $orgRoot->setActive(true);
        $orgRoot->setSortOrder(1);
        $manager->persist($orgRoot);
        $this->addReference(self::ORG_ROOT, $orgRoot);

        // 创建多个分公司
        for ($i = 1; $i <= 5; ++$i) {
            $division = new TestTreeEntity();
            $division->setName("第{$i}分公司");
            $division->setDescription("分公司业务单元 {$i}");
            $division->setParent($orgRoot);
            $division->setActive(true);
            $division->setSortOrder($i);
            $manager->persist($division);

            $this->createDepartments($manager, $division, $i);
        }
    }

    private function createDepartments(ObjectManager $manager, TestTreeEntity $division, int $divisionNumber): void
    {
        $departments = ['技术部', '销售部', '人事部', '财务部'];
        foreach ($departments as $deptIndex => $deptName) {
            $dept = new TestTreeEntity();
            $dept->setName($deptName);
            $dept->setDescription("第{$divisionNumber}分公司{$deptName}");
            $dept->setParent($division);
            $dept->setActive(true);
            $dept->setSortOrder($deptIndex + 1);
            $manager->persist($dept);

            // 技术部下创建小组
            if ('技术部' === $deptName) {
                $this->createTechTeams($manager, $dept, $divisionNumber);
            }
        }
    }

    private function createTechTeams(ObjectManager $manager, TestTreeEntity $dept, int $divisionNumber): void
    {
        $teams = ['前端组', '后端组', '测试组', 'DevOps组'];
        foreach ($teams as $teamIndex => $teamName) {
            $team = new TestTreeEntity();
            $team->setName($teamName);
            $team->setDescription("第{$divisionNumber}分公司技术部{$teamName}");
            $team->setParent($dept);
            $team->setActive(true);
            $team->setSortOrder($teamIndex + 1);
            $manager->persist($team);
        }
    }

    private function createGeographyStructure(ObjectManager $manager): void
    {
        // 创建地区分类树
        $geography = new TestTreeEntity();
        $geography->setName('地理区域');
        $geography->setDescription('全球地理区域分类');
        $geography->setActive(true);
        $geography->setSortOrder(10);
        $manager->persist($geography);
        $this->addReference(self::GEOGRAPHY_ROOT, $geography);

        // 亚洲
        $asia = new TestTreeEntity();
        $asia->setName('亚洲');
        $asia->setDescription('亚洲地区');
        $asia->setParent($geography);
        $asia->setActive(true);
        $asia->setSortOrder(1);
        $manager->persist($asia);

        $eastAsia = new TestTreeEntity();
        $eastAsia->setName('东亚');
        $eastAsia->setDescription('东亚地区');
        $eastAsia->setParent($asia);
        $eastAsia->setActive(true);
        $eastAsia->setSortOrder(1);
        $manager->persist($eastAsia);

        $china = new TestTreeEntity();
        $china->setName('中国');
        $china->setDescription('中华人民共和国');
        $china->setParent($eastAsia);
        $china->setActive(true);
        $china->setSortOrder(1);
        $manager->persist($china);
        $this->addReference(self::CHINA, $china);

        $this->createProvinces($manager, $china);
    }

    private function createProvinces(ObjectManager $manager, TestTreeEntity $china): void
    {
        $provinces = [
            '北京市', '上海市', '广东省', '江苏省', '浙江省',
            '山东省', '河南省', '四川省', '湖北省', '湖南省',
        ];

        foreach ($provinces as $index => $provinceName) {
            $province = new TestTreeEntity();
            $province->setName($provinceName);
            $province->setDescription("中国{$provinceName}");
            $province->setParent($china);
            $province->setActive(true);
            $province->setSortOrder($index + 1);
            $manager->persist($province);

            // 为部分省份添加城市
            if (in_array($provinceName, ['广东省', '江苏省', '浙江省'], true)) {
                $this->createCities($manager, $province, $provinceName);
            }
        }
    }

    private function createCities(ObjectManager $manager, TestTreeEntity $province, string $provinceName): void
    {
        $cities = match ($provinceName) {
            '广东省' => ['广州市', '深圳市', '珠海市', '东莞市'],
            '江苏省' => ['南京市', '苏州市', '无锡市', '常州市'],
            '浙江省' => ['杭州市', '宁波市', '温州市', '嘉兴市'],
            default => [],
        };

        foreach ($cities as $cityIndex => $cityName) {
            $city = new TestTreeEntity();
            $city->setName($cityName);
            $city->setDescription("{$provinceName}{$cityName}");
            $city->setParent($province);
            $city->setActive(true);
            $city->setSortOrder($cityIndex + 1);
            $manager->persist($city);
        }
    }

    private function createSkillsStructure(ObjectManager $manager): void
    {
        // 创建技能树结构
        $skills = new TestTreeEntity();
        $skills->setName('技能树');
        $skills->setDescription('程序员技能分类树');
        $skills->setActive(true);
        $skills->setSortOrder(20);
        $manager->persist($skills);
        $this->addReference(self::SKILLS_ROOT, $skills);

        $frontend = new TestTreeEntity();
        $frontend->setName('前端开发');
        $frontend->setDescription('前端开发技能');
        $frontend->setParent($skills);
        $frontend->setActive(true);
        $frontend->setSortOrder(1);
        $manager->persist($frontend);
        $this->addReference(self::FRONTEND_SKILLS, $frontend);

        $backend = new TestTreeEntity();
        $backend->setName('后端开发');
        $backend->setDescription('后端开发技能');
        $backend->setParent($skills);
        $backend->setActive(true);
        $backend->setSortOrder(2);
        $manager->persist($backend);
        $this->addReference(self::BACKEND_SKILLS, $backend);

        $this->createFrontendSkills($manager, $frontend);
        $this->createBackendSkills($manager, $backend);
    }

    private function createFrontendSkills(ObjectManager $manager, TestTreeEntity $frontend): void
    {
        $frontendSkills = [
            'HTML/CSS' => ['HTML5', 'CSS3', 'Sass', 'Less'],
            'JavaScript' => ['ES6+', 'TypeScript', 'React', 'Vue.js', 'Angular'],
            '构建工具' => ['Webpack', 'Vite', 'Rollup', 'Parcel'],
        ];

        $this->createSkillCategories($manager, $frontend, $frontendSkills);
    }

    private function createBackendSkills(ObjectManager $manager, TestTreeEntity $backend): void
    {
        $backendSkills = [
            '编程语言' => ['PHP', 'Java', 'Python', 'Go', 'Node.js'],
            '框架' => ['Symfony', 'Spring Boot', 'Django', 'Gin', 'Express'],
            '数据库' => ['MySQL', 'PostgreSQL', 'MongoDB', 'Redis'],
        ];

        $this->createSkillCategories($manager, $backend, $backendSkills);
    }

    /**
     * @param array<string, array<string>> $skillsData
     */
    private function createSkillCategories(ObjectManager $manager, TestTreeEntity $parent, array $skillsData): void
    {
        $categoryIndex = 0;
        foreach ($skillsData as $category => $subSkills) {
            $skillCategory = new TestTreeEntity();
            $skillCategory->setName($category);
            $skillCategory->setDescription("{$parent->getName()}{$category}相关技能");
            $skillCategory->setParent($parent);
            $skillCategory->setActive(true);
            $skillCategory->setSortOrder($categoryIndex + 1);
            $manager->persist($skillCategory);

            foreach ($subSkills as $index => $skill) {
                $skillItem = new TestTreeEntity();
                $skillItem->setName($skill);
                $skillItem->setDescription("{$category} - {$skill}");
                $skillItem->setParent($skillCategory);
                $skillItem->setActive(true);
                $skillItem->setSortOrder($index + 1);
                $manager->persist($skillItem);
            }

            ++$categoryIndex;
        }
    }

    private function createEdgeCasesStructure(ObjectManager $manager): void
    {
        // 创建边界测试用例
        $edgeCases = new TestTreeEntity();
        $edgeCases->setName('边界测试');
        $edgeCases->setDescription('用于测试特殊情况的数据');
        $edgeCases->setActive(true);
        $edgeCases->setSortOrder(99);
        $manager->persist($edgeCases);
        $this->addReference(self::EDGE_CASES_ROOT, $edgeCases);

        // 特殊字符测试
        $specialChars = new TestTreeEntity();
        $specialChars->setName('特殊字符测试: <>&"\'');
        $specialChars->setDescription('包含HTML特殊字符的节点');
        $specialChars->setParent($edgeCases);
        $specialChars->setActive(true);
        $specialChars->setSortOrder(1);
        $manager->persist($specialChars);

        // 很长名称测试
        $longName = new TestTreeEntity();
        $longName->setName('这是一个非常非常非常长的节点名称，用来测试TreeSelect组件在处理长文本时的表现，确保UI不会破坏并且可以正常显示和选择');
        $longName->setDescription('用于测试长名称处理的节点');
        $longName->setParent($edgeCases);
        $longName->setActive(true);
        $longName->setSortOrder(2);
        $manager->persist($longName);

        // Unicode测试
        $unicode = new TestTreeEntity();
        $unicode->setName('Unicode测试 🌟 🚀 📱 💻 🎯');
        $unicode->setDescription('包含emoji和多语言字符: Café, naïve, 日本語, العربية');
        $unicode->setParent($edgeCases);
        $unicode->setActive(true);
        $unicode->setSortOrder(3);
        $manager->persist($unicode);
    }
}

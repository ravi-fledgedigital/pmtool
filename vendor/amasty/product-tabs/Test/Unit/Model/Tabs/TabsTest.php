<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

/**
 * @codingStandardsIgnoreFile
 */

namespace Amasty\CustomTabs\Test\Unit\Model\Tabs;

use Amasty\CustomTabs\Model\Tabs\Tabs;
use Amasty\CustomTabs\Test\Unit\Traits;

/**
 * Class TabsTest
 *
 * @see Tabs
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TabsTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var Tabs
     */
    private $model;

    /**
     * @covers Tabs::addStores
     *
     * @dataProvider addStoresDataProvider
     *
     * @throws \ReflectionException
     */
    public function testAddStores(...$storeIds)
    {
        $this->model = $this->getObjectManager()->getObject(Tabs::class);

        $this->model->addStores($storeIds);

        $this->assertEquals($storeIds, $this->model->getStores());
    }

    /**
     * Data provider for addStores test
     * @return array
     */
    public function addStoresDataProvider()
    {
        return [
            [1, 3, 4],
            [2],
            []
        ];
    }
}

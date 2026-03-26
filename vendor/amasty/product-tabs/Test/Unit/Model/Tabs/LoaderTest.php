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

use Amasty\CustomTabs\Model\Tabs\Loader;
use Amasty\CustomTabs\Test\Unit\Traits;
use Magento\Framework\DataObject;

/**
 * Class LoaderTest
 *
 * @see Loader
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoaderTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var Loader
     */
    private $model;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Cache\Type\Layout
     */
    private $layoutCache;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\Loader\SaveHandler
     */
    private $saveHandler;

    /**
     * @covers Loader::execute
     */
    public function testExecute()
    {
        $this->model = $this->getObjectManager()->getObject(Loader::class);

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStores'])
            ->getMock();

        $this->layoutCache = $this->getMockBuilder(\Magento\Framework\App\Cache\Type\Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'save'])
            ->getMock();

        $this->saveHandler = $this->getMockBuilder(\Amasty\CustomTabs\Model\Tabs\Loader\SaveHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();

        $this->storeManager->expects($this->any())->method('getStores')->willReturn([
            new DataObject(['id' => 1]), new DataObject(['id' => 2])
        ]);

        $cacheParams = [
            Loader::TABS_LOADED_FLAG . 1 => 'loaded',
            Loader::TABS_LOADED_FLAG . 2 => false
        ];
        $this->layoutCache->expects($this->any())->method('load')->willReturnCallback(
            function ($cacheId) use ($cacheParams) {
                return $cacheParams[$cacheId];
            }
        );

        $this->saveHandler->expects($this->once())->method('execute');

        $this->setProperty($this->model, 'storeManager', $this->storeManager, Loader::class);
        $this->setProperty($this->model, 'layoutCache', $this->layoutCache, Loader::class);
        $this->setProperty($this->model, 'saveHandler', $this->saveHandler, Loader::class);

        $this->model->execute();
    }
}

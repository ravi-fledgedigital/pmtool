<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Plugin;

use Magento\AdminGws\Plugin\CollectionFilter;
use Magento\Cms\Model\ResourceModel\Page\Grid\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\AdminGws\Model\Role;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CollectionFilterTest extends TestCase
{

    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var CollectionFilter|MockObject
     */
    private $pluginObject;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->roleMock = $this->createMock(Role::class);
        $this->roleMock->method('getStoreGroupIds')->willReturn([2, 5]);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->pluginObject = new CollectionFilter(
            $this->roleMock,
            $this->requestMock,
            $this->storeManagerMock
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    public function testBeforeGetSelectCountSqlForCmsPages()
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addStoreFilter')->willReturnSelf();
        $this->pluginObject->beforeGetSelectCountSql($collection);
    }
}

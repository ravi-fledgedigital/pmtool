<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Controller\Adminhtml\Order\View;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test the order view page
 */
class ViewTest extends AbstractBackendController
{
    /** @var OrderInterfaceFactory */
    private $orderFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderFactory = $this->_objectManager->get(OrderInterfaceFactory::class);
        $this->orderRepository = $this->_objectManager->get(OrderRepositoryInterface::class);
    }

    /**
     * Assert that the order page can be opened if the order is in status Received and AsyncOrder is disabled
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoAppArea adminhtml
     * @return void
     */
    public function testViewWithStatusReceivedAndAsyncOrderDisabled(): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $order->setState(OrderManagement::STATUS_RECEIVED);
        $order->setStatus(OrderManagement::STATUS_RECEIVED);
        $this->orderRepository->save($order);

        $this->getRequest()->setParam('order_id', $order->getEntityId());
        $this->dispatch('backend/sales/order/view/');

        $this->assertFalse($this->getResponse()->isRedirect());
        $this->assertStringContainsString(
            'Order # ' . $order->getIncrementId(),
            $this->getResponse()->getBody()
        );
    }

    /**
     * Assert that the order page of the order with the status Received
     * redirects back to the grid when the AsyncOrder is enabled
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @return void
     */
    public function testViewWithStatusReceivedAndAsyncOrderEnabled(): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $order->setState(OrderManagement::STATUS_RECEIVED);
        $order->setStatus(OrderManagement::STATUS_RECEIVED);
        $this->orderRepository->save($order);

        $this->getRequest()->setParam('order_id', $order->getEntityId());
        $this->dispatch('backend/sales/order/view/');

        $this->assertTrue($this->getResponse()->isRedirect());
        $this->assertRedirect($this->stringContains('sales/order/index'));
    }
}

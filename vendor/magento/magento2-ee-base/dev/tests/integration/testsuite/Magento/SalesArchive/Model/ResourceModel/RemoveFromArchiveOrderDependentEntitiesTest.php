<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Model\ResourceModel;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Sales\Test\Fixture\Creditmemo as CreditmemoFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\SalesArchive\Model\Archive as Archive;
use Magento\SalesArchive\Model\ResourceModel\Archive as ResourceArchive;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class RemoveFromArchiveOrderDependentEntitiesTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var ResourceArchive
     */
    private $resourceArchive;

    /**
     * @var Archive
     */
    private $archive;

    /**
     * SetUp restoreFromDbDump
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public static function setUpBeforeClass(): void
    {
        $db = Bootstrap::getInstance()
            ->getBootstrap()
            ->getApplication()
            ->getDbInstance();
        if (!$db->isDbDumpExists()) {
            throw new \LogicException('DB dump does not exist.');
        }
        $db->restoreFromDbDump();

        parent::setUpBeforeClass();
    }

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->resourceArchive = Bootstrap::getObjectManager()->get(ResourceArchive::class);
        $this->archive = Bootstrap::getObjectManager()->get(Archive::class);
    }

    /**
     * Invoice, Shipment and Credit memo move to and from Archive together with Order which has different id
     */
    #[
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order1'),
        DataFixture(GuestCartFixture::class, as: 'cart2'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart2.id$', 'product_id' => '$product.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart2.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart2.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(ShipmentFixture::class, ['order_id' => '$order.id$'], 'shipment'),
        DataFixture(CreditmemoFixture::class, ['order_id' => '$order.id$'], 'creditmemo'),
    ]
    public function testMoveOrderToArchiveAndBack()
    {
        // Retrieve entity ids for Oder, Invoice, Credit Memo and Shipment
        $orderId = $this->fixtures->get('order')->getId();
        $invoiceId = $this->fixtures->get('invoice')->getId();
        $creditmemoId = $this->fixtures->get('creditmemo')->getId();
        $shipmentId = $this->fixtures->get('shipment')->getId();

        // Check that all entities are not in the Archive now
        $this->assertFalse(
            $this->resourceArchive->isOrderInArchive($orderId),
            'Order is in Archive already'
        );
        $this->assertEmpty(
            $this->resourceArchive->getIdsInArchive('invoice', $invoiceId, 'order_id'),
            'Invoice is in Archive already'
        );
        $this->assertEmpty(
            $this->resourceArchive->getIdsInArchive('shipment', $shipmentId),
            'Shipment is in Archive already'
        );
        $this->assertEmpty(
            $this->resourceArchive->getIdsInArchive('creditmemo', $creditmemoId),
            'Credit Memo is in Archive already'
        );

        // Move order to Archive. Invoice, Credit Memo and Shipment should be moved as well.
        $this->archive->archiveOrdersById($orderId);

        // Check that all entities are in the Archive now
        $this->assertTrue(
            $this->resourceArchive->isOrderInArchive($orderId),
            'Order was not moved to Archive as expected'
        );
        $this->assertNotEmpty(
            $this->resourceArchive->getIdsInArchive('invoice', $invoiceId),
            'Invoice was not moved to Archive as expected'
        );
        $this->assertNotEmpty(
            $this->resourceArchive->getIdsInArchive('shipment', $shipmentId),
            'Shipment was not moved to Archive as expected'
        );
        $this->assertNotEmpty(
            $this->resourceArchive->getIdsInArchive('creditmemo', $creditmemoId),
            'Credit Memo was not moved to Archive as expected'
        );

        // Move order from Archive. Invoice, Credit Memo and Shipment should be moved as well.
        $this->archive->removeOrdersFromArchiveById($orderId);

        // Check that all entities are not in the Archive again
        $this->assertFalse(
            $this->resourceArchive->isOrderInArchive($orderId),
            'Order was not moved from Archive as expected'
        );
        $this->assertEmpty(
            $this->resourceArchive->getIdsInArchive('invoice', $invoiceId),
            'Invoice was not moved from Archive as expected'
        );
        $this->assertEmpty(
            $this->resourceArchive->getIdsInArchive('shipment', $shipmentId),
            'Shipment was not moved from Archive as expected'
        );
        $this->assertEmpty(
            $this->resourceArchive->getIdsInArchive('creditmemo', $creditmemoId),
            'Credit Memo was not moved from Archive as expected'
        );
    }
}

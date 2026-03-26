<?php

namespace OnitsukaTiger\OrderStatus\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Custom shipped Order-Status code
     */
    const ORDER_STATUS_SHIPPED_CODE = 'shipped';

    /**
     * Custom Shipped Order-Status label
     */
    const ORDER_STATUS_SHIPPED_LABEL = 'Shipped';

    /**
     * label for packed status
     */
    const ORDER_STATUS_PACKED_LABEL = 'Packed';

    /**
     * label for stock pending status
     */
    const ORDER_STATUS_STOCK_PENDING_LABEL = 'Stock Pending';

    /**
     * label for prepacked status
     */
    const ORDER_STATUS_PREPACKED_LABEL = 'PrePacked';

    /**
     * label for pack state
     */
    const ORDER_STATE_PACKED_CODE = 'packed';

    /**
     * label for delivered status
     */
    const ORDER_STATUS_DELIVERED_LABEL = 'Delivered';

    /**
     * label for delivery failed status
     */
    const ORDER_STATUS_DELIVERY_FAILED_LABEL = 'Delivery Failed';

    /**
     * Status Factory
     *
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * Status Resource Factory
     *
     * @var StatusResourceFactory
     */
    protected $statusResourceFactory;


    /**
     * InstallData constructor
     *
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }


    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addCustomOrderShippedStatus();
        }
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addPackedStatus();
            $this->addStockPendingStatus();
        }
        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->addPrePackedStatus();
        }
        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->updateShippingFailedState();
        }
        if (version_compare($context->getVersion(), '1.5.0', '<')) {
            $this->addCancellingStatus();
        }
        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $this->removeShippingFailedStatus();
            $this->removeCancellingStatus();
        }
        if (version_compare($context->getVersion(), '1.7.0', '<')) {
            $this->addDeliveredStatus();
            $this->addDeliveryFailedStatus();
        }
    }

    /**
     * Create new order Shipped status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addCustomOrderShippedStatus()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => self::ORDER_STATUS_SHIPPED_CODE,
            'label' => self::ORDER_STATUS_SHIPPED_LABEL,
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {

            return;
        }

        $status->assignState(Order::STATE_PROCESSING, false, true);

    }

    /**
     * Create packed status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addPackedStatus()
    {
        // packed
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => OrderStatus::STATUS_PACKED,
            'label' => self::ORDER_STATUS_PACKED_LABEL,
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }

        $status->assignState(self::ORDER_STATE_PACKED_CODE, false, true);
    }

    /**
     * Create stock pending status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addStockPendingStatus()
    {
        // packed
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => OrderStatus::STATUS_STOCK_PENDING,
            'label' => self::ORDER_STATUS_STOCK_PENDING_LABEL,
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }

        $status->assignState(Order::STATE_PROCESSING, false, true);
    }

    /**
     * Create prepacked status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addPrePackedStatus()
    {
        // packed
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => OrderStatus::STATUS_PREPACKED,
            'label' => self::ORDER_STATUS_PREPACKED_LABEL,
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }

        $status->assignState(self::ORDER_STATE_PACKED_CODE, false, true);
    }

    /**
     * Update Shipping Failed State
     *
     * @return void
     *
     * @throws Exception
     */
    protected function updateShippingFailedState()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => 'shipping_failed',
            'label' => 'Shipping Failed',
        ]);

        $status->unassignState(
            Order::STATE_PROCESSING
        );

        $status->assignState(
            'shipping_failed',
            false,
            true
        );
    }

    /**
     * Create prepacked status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addCancellingStatus()
    {
        // packed
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => 'cancelling',
            'label' => 'Cancelling',
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }

        $status->assignState(Order::STATE_COMPLETE, false, true);
    }

    /**
     * Remove shipping_failed status
     *
     * @return void
     *
     * @throws Exception
     */
    protected function removeShippingFailedStatus()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create()->load('shipping_failed', 'status');

        try {
            $statusResource->delete($status);
        } catch (\Exception $exception) {
            return;
        }
    }

    /**
     * Remove cancelling status
     *
     * @return void
     *
     * @throws Exception
     */
    protected function removeCancellingStatus()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create()->load('cancelling', 'status');

        try {
            $statusResource->delete($status);
        } catch (\Exception $exception) {
            return;
        }
    }

    /**
     * Create Delivered status
     * @return void
     */
    protected function addDeliveredStatus()
    {
        $statusResource = $this->statusResourceFactory->create();

        $this->removeExistsStatus(OrderStatus::STATUS_DELIVERED);

        $status = $this->statusFactory->create();
        $status->setData([
            'status' => OrderStatus::STATUS_DELIVERED,
            'label' => self::ORDER_STATUS_DELIVERED_LABEL,
        ]);

        try {
            $statusResource->save($status);
            $status->assignState(Order::STATE_COMPLETE, false, true);
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * Create Delivered Failed status
     * @return void
     */
    protected function addDeliveryFailedStatus()
    {
        $statusResource = $this->statusResourceFactory->create();

        $this->removeExistsStatus(OrderStatus::STATUS_DELIVERY_FAILED);

        $status = $this->statusFactory->create();
        $status->setData([
            'status' => OrderStatus::STATUS_DELIVERY_FAILED,
            'label' => self::ORDER_STATUS_DELIVERY_FAILED_LABEL,
        ]);

        try {
            $statusResource->save($status);
            $status->assignState(Order::STATE_PROCESSING, false, true);
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * @param $status
     */
    protected function removeExistsStatus($status)
    {
        $statusResource = $this->statusResourceFactory->create();
        $status = $this->statusFactory->create()->load($status, 'status');
        try {
            $statusResource->delete($status);
        } catch (Exception $exception) {
            return;
        }
    }
}

<?php

namespace WeltPixel\GA4\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Sales\Model\OrderRepository;
use WeltPixel\GA4\Model\OrdersAddonTypes;

/**
 * Class PurchasePushServerSide
 */
class PurchasePushServerSide
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface */
    protected $purchaseBuilder;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrdersAddonTypes
     */
    protected $ordersAddonTypes;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * @var string
     */
    protected $cronPushLogPath;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder
     * @param OrderRepository $orderRepository
     * @param OrdersAddonTypes $ordersAddonTypes
     * @param ManagerInterface $eventManager
     * @param DirectoryList $directoryList
     * @param FileDriver $fileDriver
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder,
        OrderRepository $orderRepository,
        OrdersAddonTypes $ordersAddonTypes,
        ManagerInterface $eventManager,
        DirectoryList $directoryList,
        FileDriver $fileDriver
    ) {
        $this->ga4Helper = $ga4Helper;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->purchaseBuilder = $purchaseBuilder;
        $this->orderRepository = $orderRepository;
        $this->ordersAddonTypes = $ordersAddonTypes;
        $this->eventManager = $eventManager;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->cronPushLogPath = $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'ga4-cron-pushed.log';
    }

    public function execute()
    {
        $this->pushAddonMissedPurchaseEvents();

        if (!$this->ga4Helper->isServerSideTrakingEnabled()) {
            return $this;
        }

        if ($this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_PURCHASE)) {
            $orderIds = $this->purchaseBuilder->getMeasurementMissedOrderIds();
            foreach ($orderIds as $orderId) {
                $order = $this->orderRepository->get($orderId);
                if (!$order) {
                    continue;
                }

                $this->ga4Helper->reloadConfigOptions($order->getStoreId());

                if ($this->isFreeOrderTrackingAllowedForGoogleAnalytics($order)
                    && $this->ga4Helper->isOrderTrackingAllowedBasedOnOrderStatus($order)
                ) {
                    $this->logCronPushAttempt($order);
                    $purchaseEvent = $this->purchaseBuilder->getPurchaseEvent($order, true);
                    $this->ga4ServerSideApi->pushPurchaseEvent($purchaseEvent);
                }
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isFreeOrderTrackingAllowedForGoogleAnalytics($order)
    {
        $excludeFreeOrder = $this->ga4Helper->excludeFreeOrderFromPurchaseForGoogleAnalytics();
        return $this->isFreeOrderAllowed($order, $excludeFreeOrder);
    }

    /**
     * @param $order
     * @param bool $excludeFreeOrder
     * @return bool
     */
    protected function isFreeOrderAllowed($order, $excludeFreeOrder)
    {
        if (!$excludeFreeOrder) {
            return true;
        }

        $orderTotal = $order->getGrandtotal();
        if ($orderTotal > 0) {
            return true;
        }

        return false;
    }

    /**
     * Push missed purchase events for addons
     * @return void
     */
    public function pushAddonMissedPurchaseEvents()
    {
        try {
            $unpushedAddonPurchaseOrders = $this->ordersAddonTypes->getUnpushedOrders();

            if (empty($unpushedAddonPurchaseOrders)) {
                return;
            }

            foreach ($unpushedAddonPurchaseOrders as $orderData) {
                try {
                    $orderId = $orderData['order_id'];
                    $addonType = $orderData['addon_type'];

                    // Dispatch event to trigger the purchase event for the specific addon
                    $this->eventManager->dispatch('wpx_ga4_addon_purchase_push_' . $addonType, [
                        'order_id' => $orderId
                    ]);
                } catch (\Exception $e) {
                    continue;
                }
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Log attempted order push increment IDs for cron execution.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order|null $order
     * @return void
     */
    protected function logCronPushAttempt($order)
    {
        if (!$order || !$order->getIncrementId()) {
            return;
        }

        try {
            if (!$this->ga4Helper->getDebugFileEnabled()) {
                return;
            }

            $logDir = dirname($this->cronPushLogPath);
            if (!$this->fileDriver->isExists($logDir)) {
                $this->fileDriver->createDirectory($logDir);
            }

            $message = sprintf(
                '[%s] Attempted to push order increment ID: %s',
                gmdate('Y-m-d H:i:s'),
                $order->getIncrementId()
            );
            $this->fileDriver->filePutContents(
                $this->cronPushLogPath,
                $message . PHP_EOL,
                FILE_APPEND
            );
        } catch (\Exception $exception) {
            return;
        }
    }
}

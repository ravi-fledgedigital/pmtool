<?php
/** phpcs:ignoreFile */
namespace OnitsukaTigerKorea\SftpImportExport\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\CreditCancel;

class CancelCreditMemo implements ObserverInterface
{
    /**
     * @param CreditCancel $sftpCancel
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     */
    public function __construct(
       protected CreditCancel $sftpCancel,
       protected OrderRepositoryInterface $orderRepository,
       protected Logger $logger
    ) {
        $this->sftpCancel = $sftpCancel;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $ordersync = $observer->getEvent()->getCreditmemo();
        $order = $this->orderRepository->get($ordersync->getOrderId());
        if (!$order->getOrderSynced() && $order->getStoreId() == 5) {
            try {
                $this->sftpCancel->execute($ordersync);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}

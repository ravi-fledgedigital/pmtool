<?php

namespace OnitsukaTiger\OrderStatus\Plugin\Omise;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Omise\Payment\Cron\OrderSyncStatus;

class OrderSyncStatusPlugin
{

    /**
     * @var array
     */
    private $paymentMethodArray = [
        "omise_cc",
        "omise_offline_conveniencestore",
        "omise_offline_paynow",
        "omise_offline_promptpay",
        "omise_offline_tesco",
        "omise_offsite_alipay",
        "omise_offsite_truemoney",
        "omise_offsite_installment",
        "omise_offsite_alipaycn",
        "omise_offsite_alipayhk",
        "omise_offsite_dana",
        "omise_offsite_gcash",
        "omise_offsite_kakaopay",
        "omise_offsite_touchngo",
        "omise_offsite_internetbanking",
        "omise_offsite_mobilebanking",
        "omise_offsite_rabbitlinepay",
    ];

    /**
     * @var array
     */
    private $orderStatusArray = ['pending_payment', 'processing', 'payment_review'];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * OrderSyncStatusPlugin constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @param OrderSyncStatus $subject
     * @param $result
     * @return array
     */
    public function afterGetOrderIds(OrderSyncStatus $subject, $result)
    {
        $lastProcessedOrderId = $this->scopeConfig->getValue(
            'payment/omise/cron_last_order_id'
        );
        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSort('entity_id', 'desc')
            ->setPageSize(50)
            ->setCurPage(1);

        $collection->getSelect()
            ->join(
                ['sop' => $collection->getTable('sales_order_payment')],
                'sop.parent_id = main_table.entity_id',
                ['method']
            )
            ->where('main_table.status in (?)', $this->orderStatusArray)
            ->where('sop.method in (?)', $this->paymentMethodArray);
        if (isset($lastProcessedOrderId) && (int) $lastProcessedOrderId) {
            $collection->getSelect()->where('main_table.entity_id < ?', $lastProcessedOrderId);
        }
        $result = $collection->getData();
        return $result;
    }
}

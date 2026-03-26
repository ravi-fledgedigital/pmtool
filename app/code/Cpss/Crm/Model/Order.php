<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cpss\Crm\Model;

use Magento\Config\Model\Config\Source\Nooptreq;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

class Order extends \Magento\Sales\Model\Order
{
        /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $addressCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $memoCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        ScopeConfigInterface $scopeConfig = null
    ) {
        $this->scopeConfig = $scopeConfig ?: ObjectManager::getInstance()->get(ScopeConfigInterface::class);

        $this->creditmemoRepository = $creditmemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $timezone,
            $storeManager,
            $orderConfig,
            $productRepository,
            $orderItemCollectionFactory,
            $productVisibility,
            $invoiceManagement,
            $currencyFactory,
            $eavConfig,
            $orderHistoryFactory,
            $addressCollectionFactory,
            $paymentCollectionFactory,
            $historyCollectionFactory,
            $invoiceCollectionFactory,
            $shipmentCollectionFactory,
            $memoCollectionFactory,
            $trackCollectionFactory,
            $salesOrderCollectionFactory,
            $priceCurrency,
            $productListFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Retrieve order credit memo (refund) availability
     *
     * @return bool
     */
    public function canCreditmemo()
    {
        if ($this->hasForcedCanCreditmemo()) {
            return $this->getForcedCanCreditmemo();
        }

        if ($this->canUnhold() || $this->isPaymentReview() ||
            $this->isCanceled() || $this->getState() === self::STATE_CLOSED) {
            return false;
        }

        $this->getCreditMemoByOrderId($this->getId());
        if($this->getPayment()->getMethod() == "fullpoint" && 
            $this->getStatus() != "closed" && 
            $this->getStatus() != "pending" ) {
            return true;
        } else {
            /**
             * We can have problem with float in php (on some server $a=762.73;$b=762.73; $a-$b!=0)
             * for this we have additional diapason for 0
             * TotalPaid - contains amount, that were not rounded.
             */
            $totalRefunded = $this->priceCurrency->round($this->getTotalPaid()) - $this->getTotalRefunded();
            if (abs($this->getGrandTotal()) < .0001) {
                return $this->canCreditmemoForZeroTotal($totalRefunded);
            }

            $isRefundedAll = true;

            foreach ($this->getAllVisibleItems() as $item) {
                // if ($item->getProductType() == 'simple') {
                //     continue;
                // }

                if ($item->getQtyInvoiced() > $item->getQtyRefunded()) {
                    $isRefundedAll = false;
                    break;
                }
            }

            if (!$isRefundedAll) {
                return true;
            }
    
            return $this->canCreditmemoForZeroTotalRefunded($totalRefunded);
        }
    }

    public function getCreditMemoByOrderId(int $orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
            $creditmemoRecords = $creditmemos->getItems();
        } catch (\Exception $exception)  {
            $creditmemoRecords = null;
        }

        return $creditmemoRecords;
    }

    /**
     * Retrieve credit memo for zero total refunded availability.
     *
     * @param float $totalRefunded
     * @return bool
     */
    private function canCreditmemoForZeroTotalRefunded($totalRefunded)
    {
        $isRefundZero = abs($totalRefunded) < .0001;
        // Case when Adjustment Fee (adjustment_negative) has been used for first creditmemo
        $hasAdjustmentFee = abs($totalRefunded - $this->getAdjustmentNegative()) < .0001;
        $hasActionFlag = $this->getActionFlag(self::ACTION_FLAG_EDIT) === false;
        if ($isRefundZero || $hasAdjustmentFee || $hasActionFlag) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve credit memo for zero total availability.
     *
     * @param float $totalRefunded
     * @return bool
     */
    private function canCreditmemoForZeroTotal($totalRefunded)
    {
        $totalPaid = $this->getTotalPaid();
        //check if total paid is less than grandtotal
        $checkAmtTotalPaid = $totalPaid <= $this->getGrandTotal();
        //case when amount is due for invoice
        $hasDueAmount = $this->canInvoice() && ($checkAmtTotalPaid);
        //case when paid amount is refunded and order has creditmemo created
        $creditmemos = ($this->getCreditmemosCollection() === false) ?
             true : ($this->_memoCollectionFactory->create()->setOrderFilter($this)->getTotalCount() > 0);
        $paidAmtIsRefunded = $this->getTotalRefunded() == $totalPaid && $creditmemos;
        if (($hasDueAmount || $paidAmtIsRefunded) ||
            (!$checkAmtTotalPaid &&
            abs($totalRefunded - $this->getAdjustmentNegative()) < .0001)) {
            return false;
        }
        return true;
    }

    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        if (null === $this->getCustomerFirstname()) {
            return (string)__('Guest');
        }

        $customerName = '';
        if ($this->isVisibleCustomerPrefix() && !empty($this->getCustomerPrefix())) {
            $customerName .= $this->getCustomerPrefix() . ' ';
        }
        $customerName .= $this->getCustomerLastname();
        if ($this->isVisibleCustomerMiddlename() && !empty($this->getCustomerMiddlename())) {
            $customerName .= ' ' . $this->getCustomerMiddlename();
        }
        $customerName .= ' ' . $this->getCustomerFirstname();
        if ($this->isVisibleCustomerSuffix() && !empty($this->getCustomerSuffix())) {
            $customerName .= ' ' . $this->getCustomerSuffix();
        }

        return $customerName;
    }

    /**
     * Is visible customer middlename
     *
     * @return bool
     */
    private function isVisibleCustomerMiddlename(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'customer/address/middlename_show',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is visible customer prefix
     *
     * @return bool
     */
    private function isVisibleCustomerPrefix(): bool
    {
        $prefixShowValue = $this->scopeConfig->getValue(
            'customer/address/prefix_show',
            ScopeInterface::SCOPE_STORE
        );

        return $prefixShowValue !== Nooptreq::VALUE_NO;
    }

    /**
     * Is visible customer suffix
     *
     * @return bool
     */
    private function isVisibleCustomerSuffix(): bool
    {
        $prefixShowValue = $this->scopeConfig->getValue(
            'customer/address/suffix_show',
            ScopeInterface::SCOPE_STORE
        );

        return $prefixShowValue !== Nooptreq::VALUE_NO;
    }
}

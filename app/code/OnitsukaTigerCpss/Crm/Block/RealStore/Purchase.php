<?php
/**
 * Copyright © OnitsukaTiger All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerCpss\Crm\Block\RealStore;

use Cpss\Crm\Helper\Customer as CustomerHelper;
use Cpss\Crm\Model\ResourceModel\RealStore\Collection as RealStoreCollection;
use Cpss\Crm\Model\ResourceModel\ShopReceipt\CollectionFactory as ShopReceiptCollection;
use Cpss\Crm\Model\Shop\Config\Param;
use Cpss\Pos\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;

class Purchase extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Session
     */
    protected Session $customerSession;
    /**
     * @var Data
     */
    protected Data $posHelper;
    /**
     * @var ShopReceiptCollection
     */
    protected ShopReceiptCollection $shopReceiptCollection;
    protected $realStoreCollection;
    /**
     * @var CustomerHelper
     */
    protected CustomerHelper $customerHelper;
    /**
     * Constructor
     *
     * @param Context  $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Data $posHelper,
        ShopReceiptCollection $shopReceiptCollection,
        RealStoreCollection $realStoreCollection,
        CustomerHelper $customerHelper,
        private \Magento\Framework\App\Request\Http $http,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->posHelper = $posHelper;
        $this->shopReceiptCollection = $shopReceiptCollection;
        $this->customerHelper = $customerHelper;
        $this->realStoreCollection = $realStoreCollection;
        parent::__construct($context, $data);
    }

    public function getPurchaseHistoryByPagination()
    {
        $pager = $this->getLayout()->createBlock(
            \Magento\Theme\Block\Html\Pager::class,
            'purchase.history.pager'
        )->setCollection(
            $this->getShopPurchaseHistory()
        );

        $this->setChild('pager', $pager);
        return $this->getChildHtml('pager');
    }

    public function getShopPurchaseHistory()
    {
        $page = $this->http->getParam("p") ? $this->http->getParam("p") : 1;
        $limit = $this->http->getParam("limit") ? $this->http->getParam("limit") : 10;

        $shopReceiptCollection = $this->shopReceiptCollection->create();
        $shopReceiptCollection->addFieldToFilter('member_id', $this->customerSession->getCustomer()->getId());
        $shopReceiptCollection->getSelect()->join(
            ['real_store' =>$this->realStoreCollection->getTable('crm_real_stores')],
            'real_store.shop_id = main_table.shop_id',
            'real_store.shop_name'
        );
        $shopReceiptCollection->setPageSize($limit);
        $shopReceiptCollection->setCurPage($page);
        $shopReceiptCollection->setOrder('main_table.entity_id', 'DESC');

        return $shopReceiptCollection;
    }

    /**
     * @return array
     */
    public function getShopPurchaseList()
    {
        $shopPurchaseList = [];

        $shopReceiptCollection =  $this->getShopPurchaseHistory();

        foreach ($shopReceiptCollection as $receipt) {
            $transactionDateTime = $receipt->getPurchaseDate() ? date('Y/m/d', strtotime($receipt->getPurchaseDate())) : '';
            $returnTransactionDateTime = $receipt->getReturnDate() ? date('Y/m/d', strtotime($receipt->getReturnDate())) : '';
            $pointTransactionDateTime = $receipt->getAddedPointDate() ? date('Y/m/d', strtotime($receipt->getAddedPointDate())) : '';
            if ($transType = $receipt->getTransactionType()) {
                if ($transactionDateTime) {
                    if ($transType == 1) {
                        $transactionDateTime = $this->posHelper->convertTimezone($transactionDateTime, "UTC", "Y/m/d");
                    } elseif ($transType == 2) {
                        $transactionDateTime = $this->posHelper->convertTimezone($returnTransactionDateTime, "UTC", "Y/m/d");
                    }
                }
            }
            $transactionType =  $receipt->getTransactionType() ? Param::TRANSACTION_TYPE_VALUES[$receipt->getTransactionType()] : '';
            $totalPrice = ($receipt->getTotalAmount() + $receipt->getTaxAmount());
            $shopPurchaseList[] = [
                'shopId'                    => $this->customerHelper->getShopId(),
                'shopName'                  => $receipt->getShopName(),
                'purchaseId'                => $receipt->getPurchaseId(),
                'originPurchaseId'          => $receipt->getOriginPurchaseId() ?? '',
                'terminalNo'                => $receipt->getPosTerminalNo() ?? '',
                'receiptNo'                 => $receipt->getReceiptNo(),
                'transactionType'           => $transactionType,
                'transactionDateTime'       => $transactionDateTime,
                'paymentMethod'             => $receipt->getPaymentMethod(),
                'totalAmount'               => (float)$totalPrice,
                'discountAmount'            => (float)$receipt->getDiscountAmount(),
                'totalTax'                  => (float)$receipt->getTaxAmount(),
                'usedPoint'                 => $receipt->getUsedPoint(),
                'addedPoint'                => $receipt->getAddedPoint(),
                'pointTransactionDateTime'  => $pointTransactionDateTime,
                'pointHistoryId'            => $receipt->getPointHistoryId() ?? '',
                'productDetailsList'        => $this->getShopPurchaseDetails($receipt->getId())
            ];
        }

        return $shopPurchaseList;
    }
}

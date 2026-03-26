<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\Rma\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Rma\Helper\OrderStatusHistory;

/**
 * Class MemoTab
 *
 * @package OnitsukaTiger/Rma/Block/Adminhtml/Customer/MemoTab
 */
class RmaHistoryTab extends TabWrapper
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @var bool|StoreManagerInterface
     */
    protected $storeManager = true;

    /**
     * @var OrderStatusHistory
     */
    protected $helperStatusHistory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param OrderStatusHistory $orderStatusHistory
     * @param array $data
     */
    public function __construct(
        Context  $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        OrderStatusHistory $orderStatusHistory,
        array    $data = [])
    {
        $this->coreRegistry = $registry;
        $this->storeManager = $storeManager;
        $this->helperStatusHistory = $orderStatusHistory;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            $enable = $this->helperStatusHistory->getIsShowHistoryOfRma();
            if(!$enable){
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Return Tab label
     *
     * @codeCoverageIgnore
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('History of RMA');
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('amrma_cancel/customer/history', ['_current' => true]);
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(\Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID);
    }
}

<?php

namespace Cpss\Crm\Controller\Adminhtml\Receipt;

class View extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Cpss_Crm::ShopReceiptView';

    const PAGE_TITLE = 'View Shop Receipt';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Cpss\Crm\Model\ShopReceipt
     */
    protected $shopReceipt;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Cpss\Crm\Model\ShopReceipt $shopReceipt
    ) {
        $this->_pageFactory = $pageFactory;
        $this->shopReceipt = $shopReceipt;
        return parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $requests = $this->getRequest()->getParams();
        $shopData = $this->shopReceipt->loadByPurchaseId($requests['purchase_id']);
        if ($shopData->getEntityId()) {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->_pageFactory->create();
            $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
            $resultPage->addBreadcrumb(__(static::PAGE_TITLE), __(static::PAGE_TITLE));
            $resultPage->getConfig()->getTitle()->prepend(__("#%1", $requests['purchase_id']));

            return $resultPage;
        } else {
            return $this->_redirect('admincrm/receipt/index');
        }
    }

    /**
     * Is the user allowed to view the page.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}

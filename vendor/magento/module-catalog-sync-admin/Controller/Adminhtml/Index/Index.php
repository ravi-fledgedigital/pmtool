<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdmin\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Main page for Data Management UI React application
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_CatalogSyncAdmin::catalog_sync_admin';

    public const MENU_ID = 'Magento_CatalogSyncAdmin::catalog_sync_admin';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface  $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Load the page defined in view/adminhtml/layout/catalog_sync_admin_index_index.xml
     *
     * @return Page
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_CatalogSyncAdmin::catalog_sync_admin');
        $resultPage->getConfig()->getTitle()->prepend(__('Data Management Dashboard'));
        $this->setStoreView();
        return $resultPage;
    }

    /**
     * Set store view to store switcher
     *
     * @throws NoSuchEntityException
     */
    private function setStoreView(): void
    {
        $params = $this->getRequest()->getParams();
        $storeId = isset($params['store']) ? $params['store'] : $this->getRequest()->getParam('store');
        $store = $this->storeManager->getStore($storeId);
        $params['store'] = $store->getId();
        $this->getRequest()->setParams($params);
    }
}

<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controller responsible for loading product recommendations admin ui js from CDN
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

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
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Load Admin UI JS into page
     *
     * @return Page
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_ProductRecommendationsAdmin::product_recommendations');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Recommendations'));
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

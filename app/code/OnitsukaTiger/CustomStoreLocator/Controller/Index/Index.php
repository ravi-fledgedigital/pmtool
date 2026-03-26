<?php
namespace OnitsukaTiger\CustomStoreLocator\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{
    protected $resultPageFactory;
    protected $scopeConfig;
    protected $storeManager;

    const XML_PATH_ENABLE = 'storelocator/general/enable';
    const XML_PATH_META_TITLE = 'storelocator/general/meta_title';
    const XML_PATH_META_DESCRIPTION = 'storelocator/general/meta_description';

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function getCurrentStoreId()
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    public function isEnabled()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function execute()
    {
        $storeId = $this->getCurrentStoreId();
        if (!$this->isEnabled()) {
            return $this->_forward('defaultNoRoute');
        }

        $resultPage = $this->resultPageFactory->create();

        // Set meta title and description
        $metaTitle = $this->scopeConfig->getValue(self::XML_PATH_META_TITLE, ScopeInterface::SCOPE_STORE, $storeId);
        $metaDescription = $this->scopeConfig->getValue(self::XML_PATH_META_DESCRIPTION, ScopeInterface::SCOPE_STORE, $storeId);

        if ($metaTitle) {
            $resultPage->getConfig()->getTitle()->set($metaTitle); // Fixed here
        }

        if ($metaDescription) {
            $resultPage->getConfig()->setDescription($metaDescription);
        }

        return $resultPage;
    }
}

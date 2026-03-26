<?php

namespace OnitsukaTiger\Store\Observer;


class AddBodyClass implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\View\Page\Config
     */
    private $_pageConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * AddBodyClass constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Page\Config $pageConfig
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Page\Config $pageConfig
    )
    {
        $this->_pageConfig = $pageConfig;
        $this->_storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_pageConfig->addBodyClass($this->_storeManager->getStore()->getCode());
        return $this;
    }
}

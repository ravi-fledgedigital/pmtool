<?php
namespace OnitsukaTiger\Directory\Observer;

/**
 * Class LayoutLoadBeforeObserver
 * @package OnitsukaTiger\Directory\Observer
 */
class LayoutLoadBeforeObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \OnitsukaTiger\Customer\Helper\Data
     */
    private $helper;

    /**
     * LayoutLoadBeforeObserver constructor.
     * @param \OnitsukaTiger\Directory\Helper\Data $helper
     */
    public function __construct(
        \OnitsukaTiger\Directory\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helper->isShowTelephonePrefix()) {
            $layout = $observer->getData('layout');
            $layout->getUpdate()->addHandle('telephone_update_locale');
        }

        return $this;
    }
}

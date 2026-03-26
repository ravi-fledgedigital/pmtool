<?php
namespace Vaimo\OTAdobeDataLayer\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteRemoveItemObserver implements ObserverInterface
{
    /**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    protected $helper;

    /**
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $helper
     */
    public function __construct(
    	\Vaimo\OTAdobeDataLayer\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getData('quote_item');
        $productId = $quoteItem->getData('product_id');

        if($productId && $this->helper->isEnabledAdobeLaunch()){
            $returnData = $this->helper->getProductListItem($productId);
            if(!empty($returnData)){
                $data = ['status' => true,'item' => $returnData];
                $this->helper->getRemoveItemCartEvent($data);
            }
        }
    }
}

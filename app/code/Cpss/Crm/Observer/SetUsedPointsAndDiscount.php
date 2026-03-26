<?php
namespace Cpss\Crm\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SetUsedPointsAndDiscount implements ObserverInterface
{
    protected $storeConfig;

    public function __construct(
        ScopeConfigInterface $storeConfig
    ) {
        $this->storeConfig = $storeConfig;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if($quote->getHowToUse() == "use_points" || $quote->getHowToUse() == "use_all") {
            $quoteDetails = [];
            foreach($quote->getAllVisibleItems() as $item){
                $quoteDetails[$item->getItemId()] = $item->getUsedPoint();
            }

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/SaveUsedPointAndDiscount.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================Point Discount Log Start============================');

            $sumUsedPoint = 0;
            foreach($order->getAllVisibleItems() as $item){
                $usedPoint = $quoteDetails[$item->getQuoteItemId()];
                $logger->info('Used Points: ' . $usedPoint);
                $item->setUsedPoint($usedPoint);
                try{
                    $item->save();
                    $sumUsedPoint += $usedPoint;
                }catch(\Exception $e){}
            }

            $logger->info('==========================Point Discount Log Start============================');

            // $ordereDiscountAmount = $order->getDiscountAmount();
            // $orderBaseDiscountAmount = $order->getBaseDiscountAmount();
            // $order->setDiscountAmount($ordereDiscountAmount + $sumUsedPoint);
            // $order->setBaseDiscountAmount($orderBaseDiscountAmount + $sumUsedPoint);
        }

    }
}

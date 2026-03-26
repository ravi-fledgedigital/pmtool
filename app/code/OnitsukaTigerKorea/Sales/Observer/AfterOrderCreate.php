<?php

namespace OnitsukaTigerKorea\Sales\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use OnitsukaTigerKorea\Sales\Model\OrderXmlId;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;


/**
 * for save the order xml id, 'checkout_submit_all_after' event
 */
class AfterOrderCreate implements ObserverInterface
{

    /**
     * @var OrderXmlId
     */
    protected $orderXmlId;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @param OrderXmlId $orderXmlId
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     */
    public function __construct(
        OrderXmlId $orderXmlId,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        $this->orderXmlId = $orderXmlId;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /**
         * @var OrderInterface $order
         */
        $order = $observer->getEvent()->getOrder();
        $this->orderXmlId->updateOrderXmlId($order->getId(), $order->getId(), ExportXml::PREFIX_ORDER);

        $billingPhone = $order->getBillingAddress()->getTelephone();
        $shippingPhone = $order->getShippingAddress()->getTelephone();
        $order->setData('shipping_telephone', $shippingPhone);
        $order->setData('billing_telephone', $billingPhone);
        $order->save();

        $quote = $observer->getEvent()->getQuote();

        if ($quote && $quote->getSendNewsletterSubscription() == "1") {
            $this->subscriberFactory->create()->subscribe($order->getCustomerEmail());
        }

    }
}

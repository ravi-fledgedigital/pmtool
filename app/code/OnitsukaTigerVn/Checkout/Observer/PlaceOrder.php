<?php
namespace OnitsukaTigerVn\Checkout\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;

class PlaceOrder implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $quoteFactory;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */

    public function __construct(LoggerInterface $logger,
                                QuoteFactory $quoteFactory) {
        $this->_logger = $logger;
        $this->quoteFactory = $quoteFactory;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $quoteId = $order->getQuoteId();
        $quote  = $this->quoteFactory->create()->load($quoteId);

        $order->setPurchaserName($quote->getPurchaserName());
        $order->setCompanyTaxCode($quote->getCompanyTaxCode());
        /*$order->setCompanyName($quote->getCompanyName());
        $order->setCustomerAddress($quote->getCustomerAddress());
        $order->setCompanyEmailAddress($quote->getCompanyEmailAddress());
        $order->setCompanyPhoneNumber($quote->getCompanyPhoneNumber());*/

        $order->setVatInvoiceAgree($quote->getVatInvoiceAgree());
        $order->setTermsAndConditionAgree($quote->getTermsAndConditionAgree());
        $order->save();
    }
}

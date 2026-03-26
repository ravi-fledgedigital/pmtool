<?php

namespace Klaviyo\Reclaim\Model\Checkout;

use Magento\Quote\Model\QuoteRepository;

class ShippingInformationManagement
{
    protected $quoteRepository;

    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/setShippingInformation.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Set Shipping Information Log Start============================');
        if (!$extAttributes = $addressInformation->getExtensionAttributes()) {
            $logger->info('No Extension Attributes found');
            return;
        }

        $quote = $this->quoteRepository->getActive($cartId);
        $logger->info('Quote ID: ' . $quote->getId());
        $quote->setKlSmsConsent($extAttributes->getKlSmsConsent());
        $quote->setKlEmailConsent($extAttributes->getKlEmailConsent());
        $logger->info('==========================Set Shipping Information Log End============================');
    }
}

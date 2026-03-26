<?php

namespace OnitsukaTigerKorea\Checkout\Model\Checkout;

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

        $extAttributes = $addressInformation->getExtensionAttributes();
        $quote = $this->quoteRepository->getActive($cartId);
        if($extAttributes->getIsGift()) {
            $quote->setGiftPackaging($extAttributes->getIsGift());
        }
    }
}

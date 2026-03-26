<?php

namespace OnitsukaTiger\OrderAttribute\Model\ValidationRules;

use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Quote\Model\Quote;

class ShippingMethodValidationRule extends \Magento\Quote\Model\ValidationRules\ShippingMethodValidationRule
{
    private $generalMessage;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    public function __construct(ValidationResultFactory $validationResultFactory, string $generalMessage = '')
    {
        parent::__construct($validationResultFactory, $generalMessage);
    }

    public function validate(Quote $quote): array
    {
        $validationErrors = [];

        if (!$quote->isVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setStoreId($quote->getStoreId());
            $shippingMethod = $shippingAddress->getShippingMethod();
            $shippingRate = $shippingAddress->getShippingRateByCode($shippingMethod);
            $validationResult = $shippingMethod && $shippingRate;

            if ($quote->getStoreId() == 3) {
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shippingValidation.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info('$validationResult: ' . $validationResult);
                $logger->info('Shipping Rates: ' . print_r(json_decode(json_encode($shippingRate->getData())), true));
                $logger->info('$shippingMethod: ' . $shippingMethod);
                $logger->info('Shipping Rates: ' . print_r(json_decode(json_encode($shippingAddress->getData())), true));
                $logger->info('Shipping Rates: ' . print_r(json_decode(json_encode($quote->getData())), true));
            }

            if (!$validationResult) {
                $validationErrors = [__($this->generalMessage)];
            }
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }
}

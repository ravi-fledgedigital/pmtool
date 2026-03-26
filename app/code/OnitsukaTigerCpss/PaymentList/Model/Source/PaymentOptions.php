<?php

namespace OnitsukaTigerCpss\PaymentList\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use OnitsukaTigerCpss\PaymentList\Model\PaymentMethodFactory;

class PaymentOptions implements OptionSourceInterface
{
    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;

    public function __construct(
        PaymentMethodFactory $paymentMethodFactory
    ) {
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->paymentMethodFactory->create()->getCollection();
        $options = [];

        foreach ($collection as $paymentMethod) {
            $options[] = ['value' => $paymentMethod->getCode(), 'label' => $paymentMethod->getTitle()];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $collection = $this->paymentMethodFactory->create()->getCollection();
        $options = [];
        foreach ($collection as $paymentMethod) {
            $options[$paymentMethod->getCode()] = $paymentMethod->getTitle();
        }
        return $options;
    }
}

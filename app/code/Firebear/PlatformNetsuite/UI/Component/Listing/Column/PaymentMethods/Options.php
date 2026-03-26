<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\UI\Component\Listing\Column\PaymentMethods;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentsData;

    /**
     * @var array
     */
    protected $options;


    /**
     * Options constructor.
     * @param \Magento\Payment\Helper\Data $paymentsData
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentsData
    ) {
        $this->paymentsData = $paymentsData;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $paymentMethods = $this->paymentsData->getPaymentMethods();
        $this->options = [];

        foreach ($paymentMethods as $paymentMethodCode => $paymentMethodData) {
            if (isset($paymentMethodData['title']) && $paymentMethodData['title']) {
                $this->options[] = [
                    'label' => $paymentMethodData['title'],
                    'value' => $paymentMethodCode
                ];
            }
        }
        return $this->options;
    }
}

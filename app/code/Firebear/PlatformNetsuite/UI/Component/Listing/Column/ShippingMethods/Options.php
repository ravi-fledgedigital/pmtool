<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\UI\Component\Listing\Column\ShippingMethods;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var array
     */
    protected $options;


    /**
     * Options constructor.
     * @param \Magento\Payment\Helper\Data $paymentsData
     */
    public function __construct(
        \Magento\Shipping\Model\Config $shippingConfig
    ) {
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $shippingMethods = $this->shippingConfig->getAllCarriers();
        $this->options = [];

        foreach ($shippingMethods as $shippingMethodCode => $shippingMethodData) {
                $this->options[] = [
                    'label' => $shippingMethodData->getCarrierCode(),
                    'value' => $shippingMethodData->getCarrierCode()
                ];
        }
        return $this->options;
    }
}

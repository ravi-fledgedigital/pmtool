<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\UI\Component\Listing\Column\Currencies;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    /**
     * @var array
     */
    protected $storeManager;


    /**
     * Options constructor.
     * @param \Magento\Payment\Helper\Data $paymentsData
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $currencies = $this->storeManager->getStore()->getAllowedCurrencies();
        $this->options = [];

        if (is_array($currencies)) {
            foreach ($currencies as $currency) {
                $this->options[] = [
                    'label' => $currency,
                    'value' => $currency
                ];
            }
        }
        return $this->options;
    }
}

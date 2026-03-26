<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Customer;

/**
 * Class UpdateConsumer
 * @package Firebear\PlatformNetsuite\Model\Customer
 */
class UpdateConsumer
{
    /**
     * @var \Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway\Customer
     */
    private $gateway;

    /**
     * UpdateConsumer constructor.
     * @param \Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway\Customer $gateway
     */
    public function __construct(
        \Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway\Customer $gateway
    ) {
        $this->gateway = $gateway;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    public function processMessage(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $this->gateway->addCustomer($customer);
    }
}

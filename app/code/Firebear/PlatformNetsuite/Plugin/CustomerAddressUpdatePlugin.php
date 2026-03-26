<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Plugin;

use Firebear\PlatformNetsuite\Model\Customer\Address\UpdatePublisher;
use Magento\Customer\Model\ResourceModel\AddressRepository as AddressRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CustomerAddressUpdatePlugin
 * @package Firebear\PlatformNetsuite\Plugin
 */
class CustomerAddressUpdatePlugin
{
    /**
     * @var \Magento\Quote\Model\Product\QuoteItemsCleanerInterface
     */
    private $customerUpdatePublisher;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CustomerAddressUpdatePlugin constructor.
     * @param UpdatePublisher $customerUpdatePublisher
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UpdatePublisher $customerUpdatePublisher,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerUpdatePublisher = $customerUpdatePublisher;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param AddressRepository $subject
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function afterSave(
        AddressRepository $subject,
        \Magento\Customer\Api\Data\AddressInterface $address
    ) {
        if ($this->scopeConfig->getValue('firebear_importexport/netsuite/export_customers')) {
            $this->customerUpdatePublisher->execute($address);
        }
        return $address;
    }
}

<?php

namespace OnitsukaTigerIndo\Checkout\Plugin\Quote\Address;

/**
 * Class ToOrderAddressPlugin
 *
 * @package OnitsukaTigerIndo\Checkout\Plugin\Quote\Address
 */
class ToOrderAddressPlugin
{
    /**
     * @param \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress
     * @param \Magento\Quote\Model\Quote\Address $object
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     */
    public function afterConvert(
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject,
        \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress,
        \Magento\Quote\Model\Quote\Address $object
    ) {
        $orderAddress->setDistrict($object->getDistrict());

        return $orderAddress;
    }
}

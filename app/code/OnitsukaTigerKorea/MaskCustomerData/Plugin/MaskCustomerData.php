<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\MaskCustomerData\Plugin;

class MaskCustomerData
{
    /**
     * Customer grid construct
     *
     * @param \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
     */
    public function __construct(
        private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
    ) {
    }

    public function afterGetItems($subject, $result)
    {
        foreach ($result as $item) {
            if ($item->getStoreId() == 5) {
                $email = $item->getData('customer_email');
                $customerName = $item->getData('customer_name');

                $shippingName = $item->getData('shipping_name');
                $shippinigTelephone = $item->getData('shipping_telephone');
                $shippingAddress = $item->getData('shipping_address');

                $billingName = $item->getData('billing_name');
                $billingTelephone = $item->getData('billing_telephone');
                $billingAddress = $item->getData('billing_address');

                // Mask customer information
                if ($email) {
                    $item->setData('customer_email', $this->helper->maskEmail($email));
                }
                if ($customerName) {
                    $item->setData('customer_name', $this->helper->maskName($customerName));
                }

                // Mask shipping information
                if ($shippingName) {
                    $item->setData('shipping_name', $this->helper->maskName($shippingName));
                }
                if ($shippinigTelephone) {
                    $item->setData('shipping_telephone', $this->helper->maskPhoneNumber($shippinigTelephone));
                }
                if ($shippingAddress) {
                    $item->setData('shipping_address', $this->helper->maskAddress($shippingAddress));
                }

                // Mask billing information
                if ($billingName) {
                    $item->setData('billing_name', $this->helper->maskName($billingName));
                }
                if ($billingTelephone) {
                    $item->setData('billing_telephone', $this->helper->maskPhoneNumber($billingTelephone));
                }
                if ($billingAddress) {
                    $item->setData('billing_address', $this->helper->maskAddress($billingAddress));
                }
            }

        }
        return $result;
    }

}

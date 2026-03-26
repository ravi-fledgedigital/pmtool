<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\MaskCustomerData\Plugin\CreditMemo;

class Grid
{
    /**
     * Creditmemo grid construct
     *
     * @param \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
     */
    public function __construct(
        private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
    ) {
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection $subject
     * @param $result
     * @return mixed
     */
    public function afterGetItems(\Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection $subject, $result)
    {
        foreach ($result as $item) {
            if ($item->getStoreId() == 5) {
                if (isset($item['customer_email']) && !empty($item['customer_email'])) {
                    $item->setData('customer_email', $this->helper->maskEmail($item->getData('customer_email')));
                }
                if (isset($item['customer_name']) && !empty($item['customer_name'])) {
                    $item->setData('customer_name', $this->helper->maskName($item->getData('customer_name')));
                }
                if (isset($item['billing_address']) && !empty($item['billing_address'])) {
                    $item->setData('billing_address', $this->helper->maskAddress($item->getData('billing_address')));
                }
                if (isset($item['billing_name']) && !empty($item['billing_name'])) {
                    $item->setData('billing_name', $this->helper->maskName($item->getData('billing_name')));
                }
                if (isset($item['shipping_address']) && !empty($item['shipping_address'])) {
                    $item->setData('shipping_address', $this->helper->maskAddress($item->getData('shipping_address')));
                }
            }
        }
        return $result;
    }
}

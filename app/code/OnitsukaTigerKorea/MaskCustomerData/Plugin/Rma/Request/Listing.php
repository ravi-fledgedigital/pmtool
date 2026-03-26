<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\MaskCustomerData\Plugin\Rma\Request;

class Listing
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

    public function afterGetData(
        \Amasty\Rma\Model\Request\DataProvider\Listing $subject,
        array $result
    ) {
        foreach ($result['items'] as &$item) {
            if ($item['store_id'] == 5) {
                if (isset($item['customer_name']) && !empty($item['customer_name'])) {
                    $item['customer_name'] = $this->helper->maskName($item['customer_name']);
                }
            }
        }
        return $result;
    }
}

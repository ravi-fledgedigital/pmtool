<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\MaskCustomerData\Plugin\NowOnline;

class Grid
{
    /**
     * Online customer grid construct
     *
     * @param \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
     */
    public function __construct(
        private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
    ) {
    }

    public function afterGetData(\Magento\Customer\Model\ResourceModel\Online\Grid\Collection $subject, array $result)
    {
        foreach ($result as &$item) {
            if (isset($item['email'])) {
                $item['email'] = $this->helper->maskEmail($item['email']);
            }
            if (isset($item['firstname'])) {
                $item['firstname'] = $this->helper->maskName($item['firstname']);;
            }
            if (isset($item['lastname'])) {
                $item['lastname'] = $this->helper->maskName($item['lastname']);;
            }
            /*if (isset($item['telephone'])) {
                $item['telephone'] = $this->helper->maskPhoneNumber($item['telephone']);
            }*/
        }
        return $result;
    }
}

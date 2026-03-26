<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\MaskCustomerData\Plugin\Newsletter;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;

class Grid
{
    /**
     * Newsletter subscriber grid construct
     *
     * @param \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
     */
    public function __construct(
        private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
    ) {
    }

    /**
     * Mask customer data in the newsletter subscriber grid
     *
     * @param Collection $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(Collection $subject, $result)
    {
        foreach ($result as &$subscriber) {
            if (isset($subscriber['website_id']) && $subscriber['website_id'] == 4) {
                if (isset($subscriber['subscriber_email'])) {
                    $subscriber['subscriber_email'] = $this->helper->maskEmail($subscriber['subscriber_email']);
                }
                if (isset($subscriber['firstname'])) {
                    $subscriber['firstname'] = $this->helper->maskName($subscriber['firstname']);
                }
                if (isset($subscriber['lastname'])) {
                    $subscriber['lastname'] = $this->helper->maskName($subscriber['lastname']);
                }
            }
        }

        return $result;
    }
}

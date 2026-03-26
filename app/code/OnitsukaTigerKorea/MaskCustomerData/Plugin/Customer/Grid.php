<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\MaskCustomerData\Plugin\Customer;

use Magento\Framework\Encryption\EncryptorInterface;

class Grid
{
    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptor;

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        EncryptorInterface $encryptor,
        private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper
    ) {
        $this->encryptor = $encryptor;
    }

    public function afterGetData(\Magento\Customer\Model\ResourceModel\Grid\Collection $subject, array $result)
    {
        foreach ($result as &$item) {
            if ($item['website_id'] == 4) {

                if (isset($item['email'])) {
                    $item['email'] = $this->helper->maskEmail($item['email']);
                }
                if (isset($item['billing_telephone'])) {
                    $item['billing_telephone'] = $this->helper->maskPhoneNumber($item['billing_telephone']);
                }
                if (isset($item['billing_firstname'])) {
                    $item['billing_firstname'] = $this->helper->maskName($item['billing_firstname']);
                }
                if (isset($item['billing_lastname'])) {
                    $item['billing_lastname'] = $this->helper->maskName($item['billing_lastname']);
                }

                try {
                    if (isset($item['shipping_full']) && !empty(trim($item['shipping_full']))) {
                        $item['shipping_full'] = $this->helper->maskAddress($item['shipping_full']);
                    }
                    if (isset($item['billing_full']) && !empty(trim($item['billing_full']))) {
                        $item['billing_full'] = $this->helper->maskAddress($item['billing_full']);
                    }
                    if (isset($item['billing_street']) && !empty(trim($item['billing_street']))) {
                        $item['billing_street'] = $this->helper->maskAddress($item['billing_street']);
                    }
                } catch (\Exception $e) {
                    $result = ['error' => true, 'message' => $e->getMessage()];
                }
            }
        }
        return $result;
    }
}

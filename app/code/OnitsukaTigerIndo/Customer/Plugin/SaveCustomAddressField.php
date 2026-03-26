<?php
/** phpcs:ignoreFile */
namespace OnitsukaTigerIndo\Customer\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface;
use OnitsukaTigerIndo\Customer\Helper\Data;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class SaveCustomAddressField
 *
 * @package OnitsukaTigerIndo\Customer\Plugin
 */
class SaveCustomAddressField
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * SaveCustomAddressField constructor.
     * @param Data $helperData
     * @param RequestInterface $request
     */
    public function __construct(
        Data $helperData,
        RequestInterface $request
    ) {
        $this->helperData = $helperData;
        $this->request = $request;
    }

    /**
     * @param AddressRepositoryInterface $subject
     * @param AddressInterface $address
     * @return array
     */
    public function beforeSave(AddressRepositoryInterface $subject, AddressInterface $address) {
        if ($this->helperData->isEnableModule()) {
            $customAttributes = $this->request->getParam('custom_attributes');
            if ($customAttributes && is_array($customAttributes)) {
                $district = $customAttributes['district'];
                $address->setCustomAttribute('district', $district);
            }
        }

        return [$address];
    }
}

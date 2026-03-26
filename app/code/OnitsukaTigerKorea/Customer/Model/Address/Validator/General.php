<?php

namespace OnitsukaTigerKorea\Customer\Model\Address\Validator;

use Magento\Customer\Model\Address\AbstractAddress;
use OnitsukaTigerKorea\Customer\Helper\Data;
use Laminas\Validator\NotEmpty;


class General extends \Magento\Customer\Model\Address\Validator\General
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    private $directoryData;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * General constructor.
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param Data $dataHelper
     */
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Directory\Helper\Data $directoryData,
        Data $dataHelper
    ) {
        $this->eavConfig = $eavConfig;
        $this->directoryData = $directoryData;
        $this->dataHelper = $dataHelper;
        parent::__construct($eavConfig, $directoryData);
    }

    /**
     * @inheritdoc
     */
    public function validate(AbstractAddress $address)
    {
        if (!$this->dataHelper->isCustomerEnabled()) {
            return parent::validate($address);
        }

        $errors = array_merge(
            $this->checkRequiredFields($address),
            $this->checkOptionalFields($address)
        );

        return $errors;
    }

    /**
     * Check fields that are generally required.
     *
     * @param AbstractAddress $address
     * @return array
     */
    private function checkRequiredFields(AbstractAddress $address)
    {
        $errors =[];
        $validate = new NotEmpty();
        if (!$validate->isValid($address->getFirstname())) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'firstname']);
        }

        if (!$validate->isValid($address->getStreetLine(1))) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'street']);
        }

        return $errors;
    }

    /**
     * Check fields that are conditionally required.
     *
     * @param AbstractAddress $address
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkOptionalFields(AbstractAddress $address)
    {
        $errors = [];
        $validate = new NotEmpty();
        if ($this->isTelephoneRequired() &&
            !$validate->isValid($address->getTelephone())
        ) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'telephone']);
        }

        if ($this->isFaxRequired()
            && !$validate->isValid($address->getFax())
        ) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'fax']);
        }

        if ($this->isCompanyRequired()
            && !$validate->isValid($address->getCompany())
        ) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'company']);
        }

        $havingOptionalZip = $this->directoryData->getCountriesWithOptionalZip();
        if (!in_array($address->getCountryId(), $havingOptionalZip)
            && !$validate->isValid($address->getPostcode())
        ) {
            $errors[] = __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'postcode']);
        }

        return $errors;
    }

    /**
     * Check if company field required in configuration.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isCompanyRequired()
    {
        return $this->eavConfig->getAttribute('customer_address', 'company')->getIsRequired();
    }

    /**
     * Check if telephone field required in configuration.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isTelephoneRequired()
    {
        return $this->eavConfig->getAttribute('customer_address', 'telephone')->getIsRequired();
    }

    /**
     * Check if fax field required in configuration.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isFaxRequired()
    {
        return $this->eavConfig->getAttribute('customer_address', 'fax')->getIsRequired();
    }
}

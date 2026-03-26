<?php

namespace OnitsukaTigerKorea\Customer\Plugin\Model\Data;

use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class Address
 * @package OnitsukaTigerKorea\Customer\Plugin\Model\Data
 */
class Address
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * Address constructor.
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Customer\Model\Data\Address $subject
     * @param $result
     * @return string
     */
    public function afterGetLastname(\Magento\Customer\Model\Data\Address $subject, $result)
    {
        if ($this->dataHelper->isCustomerEnabled()) {
            return '';
        }

        return $result;
    }

    /**
     * @param \Magento\Customer\Model\Data\Address $subject
     * @param $lastName
     * @return string
     */
    public function beforeSetLastname(\Magento\Customer\Model\Data\Address $subject, $lastName)
    {
        if ($this->dataHelper->isCustomerEnabled()) {
            return '&nbsp';
        }

        return $lastName;
    }

    /**
     * @param \Magento\Customer\Model\Data\Address $subject
     * @param $result
     * @return string
     */
    public function afterGetCity(\Magento\Customer\Model\Data\Address $subject, $result)
    {
        if ($this->dataHelper->isCustomerEnabled() && $result == '&nbsp') {
            return '';
        }

        return $result;
    }

    /**
     * @param \Magento\Customer\Model\Data\Address $subject
     * @param $city
     * @return string
     */
    public function beforeSetCity(\Magento\Customer\Model\Data\Address $subject, $city)
    {
        if ($this->dataHelper->isCustomerEnabled() && $city == '') {
            return '&nbsp';
        }

        return $city;
    }
}

<?php

namespace OnitsukaTigerKorea\Customer\Plugin\Model\Data;

use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class Customer
 * @package OnitsukaTigerKorea\Customer\Plugin\Model\Data
 */
class Customer
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
     * @param \Magento\Customer\Model\Data\Customer $subject
     * @param $result
     * @return string
     */
    public function afterGetLastname(\Magento\Customer\Model\Data\Customer $subject, $result)
    {
        if ($this->dataHelper->isCustomerEnabled()) {
            return '';
        }

        return $result;
    }

    /**
     * @param \Magento\Customer\Model\Data\Customer $subject
     * @param $lastName
     * @return string
     */
    public function beforeSetLastname(\Magento\Customer\Model\Data\Customer $subject, $lastName)
    {
        if ($this->dataHelper->isCustomerEnabled()) {
            return '&nbsp';
        }

        return $lastName;
    }
}

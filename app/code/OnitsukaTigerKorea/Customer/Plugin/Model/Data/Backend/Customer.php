<?php

namespace OnitsukaTigerKorea\Customer\Plugin\Model\Data\Backend;

use Magento\Framework\Exception\NoSuchEntityException;
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
     * @throws NoSuchEntityException
     */
    public function afterGetLastname(\Magento\Customer\Model\Data\Customer $subject, $result)
    {
        if ($this->dataHelper->isCustomerEnabled($subject->getStoreId())) {
            return ' ';
        }
        return $result;
    }

    /**
     * @param \Magento\Customer\Model\Data\Customer $subject
     * @param $lastName
     * @return string
     * @throws NoSuchEntityException
     */
    public function beforeSetLastname(\Magento\Customer\Model\Data\Customer $subject, $lastName)
    {
        if ($this->dataHelper->isCustomerEnabled($subject->getStoreId()) && empty($lastName)) {
            return '&nbsp';
        }
        return $lastName;
    }
}

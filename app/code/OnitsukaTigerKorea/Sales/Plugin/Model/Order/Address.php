<?php

namespace OnitsukaTigerKorea\Sales\Plugin\Model\Order;

use OnitsukaTigerKorea\Sales\Helper\Data;

/**
 * Class Address
 * @package OnitsukaTigerKorea\Sales\Plugin\Model\Order
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
     * @param \Magento\Sales\Model\Order\Address $subject
     * @param $result
     * @return string
     */
    public function afterGetLastname(\Magento\Sales\Model\Order\Address $subject, $result)
    {
        if ($this->dataHelper->isSalesEnabled()) {
            return '';
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $subject
     * @param $lastName
     * @return string
     */
    public function beforeSetLastname(\Magento\Sales\Model\Order\Address $subject, $lastName)
    {
        if ($this->dataHelper->isSalesEnabled()) {
            return '';
        }

        return $lastName;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $subject
     * @param $result
     * @return string
     */
    public function afterGetCity(\Magento\Sales\Model\Order\Address $subject, $result)
    {
        if ($this->dataHelper->isSalesEnabled() && $result == '&nbsp') {
            return '';
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $subject
     * @param $city
     * @return string
     */
    public function beforeSetCity(\Magento\Sales\Model\Order\Address $subject, $city)
    {
        if ($this->dataHelper->isSalesEnabled() && $city == '') {
            return '';
        }

        return $city;
    }
}

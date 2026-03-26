<?php
namespace OnitsukaTiger\KerryConNo\Model\ResourceModel\ShippingTrackHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Data\Collection as DataCollection;

class Collection extends AbstractCollection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\KerryConNo\Model\ShippingTrackHistory::class,
            \OnitsukaTiger\KerryConNo\Model\ResourceModel\ShippingTrackHistory::class
        );
    }

    /**
     * @param string $conNo
     * @param string $serviceCode
     * @return int
     */
    public function getExistsByConNoAndServiceCode(string $conNo, string $serviceCode)
    {
        return $this->_reset()
            ->addFieldToFilter('con_no', $conNo)
            ->addFieldToFilter('service_code', $serviceCode)
            ->count();
    }
}

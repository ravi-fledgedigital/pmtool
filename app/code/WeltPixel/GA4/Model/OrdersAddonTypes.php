<?php
namespace WeltPixel\GA4\Model;

use Magento\Framework\Model\AbstractModel;

class OrdersAddonTypes extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\WeltPixel\GA4\Model\ResourceModel\OrdersAddonTypes::class);
    }

    /**
     * Update is_pushed status for a specific addon type
     *
     * @param int $pushedId
     * @param string $addonType
     * @return int Number of affected rows
     */
    public function updatePushStatus($pushedId, $addonType)
    {
        return $this->getResource()->updatePushStatus($pushedId, $addonType);
    }

    /**
     * Check if addon type is already processed for a specific pushed_id
     *
     * @param int $pushedId
     * @param string $addonType
     * @return bool
     */
    public function isAddonTypeProcessed($pushedId, $addonType)
    {
        return $this->getResource()->isAddonTypeProcessed($pushedId, $addonType);
    }

    /**
     * Get unpushed orders from the last 2 minutes
     *
     * @return array
     */
    public function getUnpushedOrders()
    {
        return $this->getResource()->getUnpushedOrders();
    }
} 
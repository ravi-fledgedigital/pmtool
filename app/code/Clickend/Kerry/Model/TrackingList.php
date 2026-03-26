<?php
/**
 * Clickend Kerry module
 * @package Clickend\Kerry
 */

namespace Clickend\Kerry\Model;

class TrackingList extends \Magento\Framework\Model\AbstractModel
{
	protected function _construct()
    {
        $this->_init('Clickend\Kerry\Model\ResourceModel\TrackingList');
    }	
}

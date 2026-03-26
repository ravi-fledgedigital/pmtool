<?php

namespace OnitsukaTiger\Restock\Model;

class Restock extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'product_alert_stock_grid';

	protected $_cacheTag = 'product_alert_stock_grid';

	protected $_eventPrefix = 'product_alert_stock_grid';

	protected function _construct()
	{
		$this->_init('OnitsukaTiger\Restock\Model\ResourceModel\Restock');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}
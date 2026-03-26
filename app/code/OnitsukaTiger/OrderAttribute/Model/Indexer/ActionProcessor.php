<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Indexer;

use Magento\Framework\Indexer\AbstractProcessor;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;

class ActionProcessor extends AbstractProcessor
{
    public const INDEXER_ID = Entity::GRID_INDEXER_ID;
}

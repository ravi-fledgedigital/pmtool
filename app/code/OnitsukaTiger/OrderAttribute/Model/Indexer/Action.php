<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Indexer;

use Magento\Framework\App\ResourceConnection\SourceProviderInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface;

class Action extends \Magento\Framework\Indexer\Action\Entity
{
    /**
     * Prepare select query
     *
     * @param array|int|null $ids
     * @return SourceProviderInterface
     */
    protected function prepareDataSource(array $ids = [])
    {
        $collection = $this->createResultCollection();
        if (!empty($ids)) {
            $collection->addFieldToFilter($this->getPrimaryResource()->getRowIdFieldName(), $ids);
        }

        return $collection;
    }
}

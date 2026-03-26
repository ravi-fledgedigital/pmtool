<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Value\Plugin\Order\Grid;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface;
use OnitsukaTiger\OrderAttribute\Model\ConfigProvider;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;
use Magento\Framework\App\ResourceConnection;

class SearchResult
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var string
     */
    protected $flatTable;

    public function __construct(
        ConfigProvider $configProvider,
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
        $this->configProvider = $configProvider;
        $this->flatTable = $this->resource->getTableName(Entity::GRID_INDEXER_ID . '_flat');
    }

    public function afterGetSelect(
        \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $collection,
        $select
    ) {

        if ($collection->getResource() instanceof \Magento\Sales\Model\ResourceModel\Order) {

            return $this->addColumnsToGrid($select, 'entity_id');
        } elseif ($collection->getResource() instanceof \Magento\Sales\Model\ResourceModel\Order\Invoice) {
            if ($this->configProvider->isShowInvoiceGrid()) {

                return $this->addColumnsToGrid($select, 'order_id');
            }
        } elseif ($collection->getResource() instanceof \Magento\Sales\Model\ResourceModel\Order\Shipment) {
            if ($this->configProvider->isShowShipmentGrid()) {

                return $this->addColumnsToGrid($select, 'order_id');
            }
        }

        return $select;
    }

    protected function addColumnsToGrid($select, $orderField)
    {
        if ((string)$select == "") {
            return $select;
        }

        if (!$this->columns) {
            $connection = $this->resource->getConnection();
            $fields = $connection->describeTable($this->flatTable);
            unset($fields['parent_id']);
            unset($fields['entity_id']);
            foreach ($fields as $field => $value) {
                $this->columns[] = 'otorderattribute.' . $field;
            }
        }

        if (!array_key_exists('otorderattribute', $select->getPart('from')) && strpos($select, 'COUNT') === false) {
            $select->joinLeft(
                ['otorderattribute' => $this->flatTable],
                'main_table.' . $orderField . ' = otorderattribute.' . CheckoutEntityInterface::PARENT_ID,
                $this->columns
            );
        }

        return $select;
    }
}

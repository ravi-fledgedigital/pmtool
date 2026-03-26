<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\DataProvider;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory;

/**
 * DataProvider for checkout attributes listing
 *
 * @property \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection $collection
 * @method \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection getCollection()
 */
class Listing extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $items = [];
        foreach ($this->getCollection()->getItems() as $attribute) {
            $items[] = $attribute->toArray();
        }

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => $items
        ];
    }

    /**
     * self::setOrder() alias
     *
     * @param string $field
     * @param string $direction
     * @return void
     */
    public function addOrder($field, $direction)
    {
        if ($field == 'attribute_id') {
            $field = 'main_table.attribute_id';
        }

        parent::addOrder($field, $direction);
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'attribute_id') {
            $filter->setField('main_table.attribute_id');
        }
        parent::addFilter($filter);
    }
}

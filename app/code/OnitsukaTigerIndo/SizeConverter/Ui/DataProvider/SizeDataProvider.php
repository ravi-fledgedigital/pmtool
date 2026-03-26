<?php

namespace OnitsukaTigerIndo\SizeConverter\Ui\DataProvider;

use OnitsukaTigerIndo\SizeConverter\Model\ResourceModel\IndoSize\Collection as SizeCollection;

class SizeDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var SizeCollection
     */
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        SizeCollection $collection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collection;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() === 'store_ids') {

            $storeId = $filter->getValue();

            if ($storeId !== null && $storeId !== '') {

                if (is_array($storeId)) {
                    $storeId = reset($storeId);
                }

                $this->getCollection()->getSelect()->where(
                    '(FIND_IN_SET(?, main_table.store_ids) OR FIND_IN_SET(0, main_table.store_ids))',
                    $storeId
                );
            }
        } else {
            parent::addFilter($filter);
        }
    }
}

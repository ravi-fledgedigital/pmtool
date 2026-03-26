<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\DataProvider;

use Amasty\CustomTabs\Model\Tabs\ResourceModel\Grid;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\GridFactory as GridCollectionFactory;
use Amasty\CustomTabs\Model\Source\CustomerGroup;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Listing extends AbstractDataProvider
{
    /**
     * @var Grid
     */
    protected $collection;

    /**
     * @var array
     */
    private $customerGroups;

    public function __construct(
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        GridCollectionFactory $collectionFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->customerGroups = $customerGroupCollectionFactory->create()->getAllIds();
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @param Filter $filter
     * @return mixed|void
     */
    public function addFilter(Filter $filter)
    {
        // @phpstan-ignore-next-line as adding return statement cause of backward compatibility issue
        if ($filter->getField() === 'customer_groups') {
            $select = $this->getCollection()->getSelect();

            $values = $filter->getValue();
            if (!is_array($values)) {
                $values = [$values];
            }

            $query = '';
            foreach ($values as $value) {
                if ($value === '-1') {
                    $query .= sprintf(
                        " OR customer_groups LIKE '%s'",
                        '%' . implode(',', $this->customerGroups) . '%'
                    );
                    continue;
                }

                $query .= sprintf(
                    " OR CONCAT(',', customer_groups, ',') LIKE '%s'",
                    '%,' . (int)$value . ',%'
                );
            }

            $query = trim($query, ' OR');
            $select->where('(' . $query . ')');
        } elseif ($filter->getField() === 'stores') {
            $values = $filter->getValue();
            if (!is_array($values)) {
                $values = [$values];
            }

            $values[] = 0;
            $filter->setConditionType('in');
            $filter->setValue($values);
            parent::addFilter($filter);
        } else {
            parent::addFilter($filter);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        $this->collection->addDefaultStore();
        $data = parent::getData();
        if ($data['totalRecords'] > 0) {
            foreach ($data['items'] as &$item) {
                $customerGroups = explode(',', $item['customer_groups']);
                $item['customer_groups'] = count($customerGroups) == count($this->customerGroups)
                    ? [CustomerGroup::ALL]
                    : $customerGroups;
            }
        }

        return $data;
    }
}

<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */
declare(strict_types=1);

namespace OnitsukaTiger\Coupon\Ui\DataProvider\Coupon;

use Magento\Sales\Model\Order;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory;

class Listing extends AbstractDataProvider
{
    public function __construct(
        CollectionFactory $collectionFactory,
                          $name,
                          $primaryFieldName,
                          $requestFieldName,
        array             $meta = [],
        array             $data = []
    )
    {
        $this->collection = $collectionFactory->create();
        $this->collection->addFieldToSelect('code');
        $this->collection->addFieldToSelect('rule_id');
        $this->collection->getSelect()->where('main_table.times_used != 0');

        $this->collection = $this->collection->join(
            ['used' => 'salesrule_coupon_usage'],
            'main_table.coupon_id = used.coupon_id',
            [
                'customer_id' => 'used.customer_id',
                'times_used_usage' => 'used.times_used',
            ]
        );

        $this->collection->join(
            ['salesrule' => 'salesrule'],
            'main_table.rule_id = salesrule.rule_id',
            ['name', 'discount_amount']
        );

        $this->collection->join(
            ['customer' => 'customer_entity'],
            'used.customer_id = customer.entity_id',
            ['customer_email_usage' => 'customer.email']
        );

        $this->collection->join(
            ['order' => 'sales_order'],
            'order.customer_id = used.customer_id AND order.coupon_code = code',
            [
                'created_at' => 'order.created_at',
                'entity_id' => 'order.entity_id',
                'store_id' => 'order.store_id'
            ]
        );

        $condition = $this->getConditionLastTimeUsed();
        $this->collection->getSelect()->where("order.entity_id IN (" . $condition . ")");
        $this->collection->addFilterToMap('times_used_usage', 'used.times_used')
            ->addFilterToMap('discount_amount', 'salesrule.discount_amount')
            ->addFilterToMap('name', 'salesrule.name')
            ->addFilterToMap('customer_email_usage', 'customer.email')
            ->addFilterToMap('created_at', 'order.created_at')
            ->addFilterToMap('store_id', 'order.store_id');

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return string
     */
    public function getConditionLastTimeUsed(): string
    {
        $ids = '';
        $countSelect = $this->collection->getSelect();
        $aggregateSelect = clone $countSelect;
        $aggregateSelect2 = clone $countSelect;
        $aggregateSelect->reset();
        $aggregateSelect2->reset();

        $aggregateSelect->from(
            ['r' => 'sales_order'], ['entity_id', 'last_time_used' => 'MAX(created_at)', 'customer_id', 'coupon_code'])
            ->group(['customer_id', 'coupon_code']);

        $aggregateSelect2->from(['r' => $aggregateSelect], ['t.entity_id'])->joinInner(
            ['t' => 'sales_order'],
            't.customer_id = r.customer_id AND  t.coupon_code = r.coupon_code AND t.created_at = r.last_time_used',
            []
        );
        $aggregateSelect2->where('t.status <> ?', Order::STATE_CANCELED);

        foreach ($aggregateSelect2->query()->fetchAll() as $id) {
            $ids .= "'" . $id['entity_id'] . "',";
        }

        return rtrim($ids, ",");
    }

}

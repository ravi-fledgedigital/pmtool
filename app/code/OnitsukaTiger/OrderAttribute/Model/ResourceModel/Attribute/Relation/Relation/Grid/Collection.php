<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{

    /**
     * @var string
     */
    protected $document = Document::class;

    /**
     * @var string[]
     */
    protected $_map = [
        'parent_label' => 'parent.frontend_label',
        'attribute_codes' => 'dependent.attribute_code'
    ];

    /**
     * Init Select for Relation Grid
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinInner(
                ['detail' => $this->getTable('onitsukatiger_order_attribute_relation_details')],
                'main_table.relation_id = detail.relation_id',
                ['detail.attribute_id']
            )
            ->joinInner(
                ['attribute' => $this->getTable('onitsukatiger_order_attribute_eav_attribute')],
                'attribute.attribute_id = detail.attribute_id',
                ['attribute.checkout_step']
            )
            ->joinInner(
                ['parent' => $this->getTable('eav_attribute')],
                'detail.attribute_id = parent.attribute_id',
                [
                    'parent.frontend_label as parent_label',
                    'CONCAT(parent.attribute_code, ",", GROUP_CONCAT(dependent.attribute_code)) as attribute_codes'
                ]
            )
            ->joinInner(
                ['dependent' => $this->getTable('eav_attribute')],
                'detail.dependent_attribute_id = dependent.attribute_id',
                ['GROUP_CONCAT(dependent.frontend_label) as dependent_label']
            )
            ->group('main_table.relation_id');

        return $this;
    }

    /**
     * Show only unique labels for columns with concat
     *
     * @inheritdoc
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        if ($item->getDependentLabel()) {
            $labels = implode(', ', array_unique(explode(',', $item->getDependentLabel())));
            $item->setDependentLabel($labels);
        }
        if ($item->getAttributeCodes()) {
            $labels = implode(', ', array_unique(explode(',', $item->getAttributeCodes())));
            $item->setAttributeCodes($labels);
        }

        return parent::beforeAddLoadedItem($item);
    }
}

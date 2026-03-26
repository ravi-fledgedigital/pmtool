<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Cpss\Crm\Ui\DataProvider;

use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\Filter;

/**
 * Class Fulltext
 */
class FulltextFilter implements \Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface
{
    /**
     * Patterns using for escaping special characters
     */
    private $escapePatterns = [
        '/[@\.]/' => '\_',
        '/([+\-><\(\)~*]+)/' => ' ',
    ];

    /**
     * Returns list of columns from fulltext index (doesn't support more then one FTI per table)
     *
     * @param AbstractDb $collection
     * @param string $indexTable
     * @return array
     */
    protected function getFulltextIndexColumns(AbstractDb $collection, $indexTable)
    {
        $indexes = $collection->getConnection()->getIndexList($indexTable);
        foreach ($indexes as $index) {
            if (strtoupper($index['INDEX_TYPE']) == 'FULLTEXT') {
                return $index['COLUMNS_LIST'];
            }
        }
        return [];
    }

    /**
     * Add table alias to columns
     *
     * @param array $columns
     * @param AbstractDb $collection
     * @param string $indexTable
     * @return array
     */
    protected function addTableAliasToColumns(array $columns, AbstractDb $collection, $indexTable)
    {
        $alias = '';
        foreach ($collection->getSelect()->getPart('from') as $tableAlias => $data) {
            if ($indexTable == $data['tableName']) {
                $alias = $tableAlias;
                break;
            }
        }
        if ($alias) {
            $columns = array_map(
                function ($column) use ($alias) {
                    return '`' . $alias . '`.' . $column;
                },
                $columns
            );
        }

        return $columns;
    }

    /**
     * Escape against value
     *
     * @param string $value
     * @return string
     */
    private function escapeAgainstValue(string $value): string
    {
        return preg_replace(array_keys($this->escapePatterns), array_values($this->escapePatterns), $value);
    }

    /**
     * Apply fulltext filters
     *
     * @param Collection $collection
     * @param Filter $filter
     * @return void
     */
    public function apply(Collection $collection, Filter $filter)
    {
        if (!$collection instanceof AbstractDb) {
            throw new \InvalidArgumentException('Database collection required.');
        }

        $mainTable = $collection->getMainTable();
        if(!empty($filter->getValue()) && $mainTable == "sales_real_store_order"){
            $columns = [
                "`main_table`.purchase_id",
                "`main_table`.return_purchase_id",
                "`main_table`.pos_terminal_no",
                "`main_table`.payment_method",
                "`main_table`.point_history_id",
                "`realstore`.shop_name",
                "`customer`.firstname",
                "`customer`.lastname",
                "`main_table`.transaction_type" // Should always be last item of the $columns array due to search criteria customization
            ];
            $search = $this->escapeAgainstValue($filter->getValue());

            $transactionTypeSearch = "";
            if (strtolower($search) == 'complete') {
                $transactionTypeSearch = 1;
            } elseif (strtolower($search) == 'closed') {
                $transactionTypeSearch = 2;
            } else {
                $transactionTypeSearch = $search;
            }
            
            $searchDetails = implode(" like '%$search%' OR ", $columns) . " like '%$transactionTypeSearch%'";

            $collection->getSelect()->joinLeft(
                ['realstore' => 'crm_real_stores'],
                'main_table.shop_id = realstore.shop_id',
                ['realstore_shopname' => 'realstore.shop_name']
            );

            $collection->getSelect()->joinLeft(
                ['fulltext_customer' => 'customer_entity'],
                'main_table.member_id = fulltext_customer.entity_id',
                ['customer_firstname' => 'fulltext_customer.firstname', 'customer_lastname' => 'fulltext_customer.lastname']
            );

            $collection->getSelect()->where(
                $searchDetails
            );
        }else{
            $columns = $this->getFulltextIndexColumns($collection, $mainTable);
            if (!$columns) {
                return;
            }
    
            $columns = $this->addTableAliasToColumns($columns, $collection, $mainTable);
            $collection->getSelect()
                ->where(
                    'MATCH(' . implode(',', $columns) . ') AGAINST(?)',
                    $this->escapeAgainstValue($filter->getValue())
                );
        }
    }
}

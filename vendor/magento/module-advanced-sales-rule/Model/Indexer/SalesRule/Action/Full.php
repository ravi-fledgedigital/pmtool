<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\AbstractAction;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\App\ResourceConnection;
use Magento\SalesRule\Model\RuleFactory;

class Full extends AbstractAction
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param RuleFactory $ruleFactory
     * @param Filter $filterResourceModel
     * @param Generator|null $generator
     * @param ResourceConnection|null $resourceConnection
     */
    public function __construct(
        RuleFactory $ruleFactory,
        Filter $filterResourceModel,
        Generator $generator = null,
        ResourceConnection $resourceConnection = null
    ) {
        parent::__construct($ruleFactory, $filterResourceModel);
        $this->generator = $generator ?: ObjectManager::getInstance()->get(Generator::class);
        $this->resourceConnection = $resourceConnection ?: ObjectManager::getInstance()->get(ResourceConnection::class);
    }

    /**
     * Refresh entities index
     *
     * @return $this
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $batchSelectIterator = $this->generator->generate(
            'rule_id',
            $connection->select()
                ->from($this->resourceConnection->getTableName('salesrule'), 'rule_id'),
            1000,
            \Magento\Framework\DB\Query\BatchIteratorInterface::NON_UNIQUE_FIELD_ITERATOR
        );

        foreach ($batchSelectIterator as $select) {
            $this->setActionIds($connection->fetchCol($select));
            $this->reindex();
        }

        return $this;
    }
}

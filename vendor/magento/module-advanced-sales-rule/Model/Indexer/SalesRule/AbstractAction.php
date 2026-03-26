<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Model\Indexer\SalesRule;

use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter as RuleFilter;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\SalesRule\Model\RuleFactory;

/**
 * Class AbstractAction
 */
abstract class AbstractAction
{
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var RuleFilter
     */
    protected $filterResourceModel;

    /**
     * @var int[]
     */
    protected $actionIds = [];

    /**
     * @param RuleFactory $ruleFactory
     * @param RuleFilter $filterResourceModel
     */
    public function __construct(
        RuleFactory $ruleFactory,
        RuleFilter $filterResourceModel
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->filterResourceModel = $filterResourceModel;
    }

    /**
     * Run full reindex
     *
     * @return $this
     */
    abstract public function execute();

    /**
     * Run reindexation.
     *
     * @param bool $fullReindex
     * @return void
     */
    protected function reindex($fullReindex = false)
    {
        if (!empty($this->actionIds)) {
            if ($fullReindex) {
                $this->filterResourceModel->deleteRuleFilters([]);
            } else {
                $this->filterResourceModel->deleteRuleFilters($this->actionIds);
            }
            foreach ($this->actionIds as $actionId) {
                $salesRule = $this->ruleFactory->create()->load($actionId);
                $this->saveFilters($salesRule);
            }
        }
    }

    /**
     * Set ids for action.
     *
     * @param int[] $actionIds
     * @return void
     */
    protected function setActionIds($actionIds)
    {
        $this->actionIds = $actionIds;
    }

    /**
     * Save filters for specific rule.
     *
     * @param SalesRule $rule
     * @return void
     */
    protected function saveFilters(SalesRule $rule)
    {
        $ruleId = $rule->getId();
        if ($ruleId) {
            $condition = $rule->getConditions();
            $isCouponCode = $rule->getCouponType() != SalesRule::COUPON_TYPE_NO_COUPON;
            $data = [];
            if ($condition instanceof FilterableConditionInterface && $condition->isFilterable()) {
                $filterGroups = $condition->getFilterGroups();
                $groupId = 1;
                foreach ($filterGroups as $filterGroup) {
                    $filters = $filterGroup->getFilters();
                    foreach ($filters as $filter) {
                        $data[] = [
                            'rule_id' => $ruleId,
                            'group_id' => $groupId,
                            'weight' => $filter->getWeight(),
                            Filter::KEY_FILTER_TEXT => $filter->getFilterText(),
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => $filter->getFilterTextGeneratorClass(),
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => $filter->getFilterTextGeneratorArguments(),
                            Filter::IS_COUPON => $isCouponCode,
                        ];
                    }
                    $groupId++;
                }
            }

            if (empty($data)) {
                $data = $this->getTruePlaceHolder($ruleId);
                $data[Filter::IS_COUPON] = $isCouponCode;
            }

            $this->filterResourceModel->insertFilters($data);
        }
    }

    /**
     * Get placeholder for rules without data.
     *
     * @param int $ruleId
     * @return array
     */
    protected function getTruePlaceHolder($ruleId)
    {
        return [
            'rule_id' => $ruleId,
            'group_id' => 1,
            'weight' => 1,
            Filter::KEY_FILTER_TEXT => FilterInterface::FILTER_TEXT_TRUE,
            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
        ];
    }
}

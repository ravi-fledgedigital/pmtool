<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedRule\Model\Condition;

/**
 * Interface \Magento\AdvancedRule\Model\Condition\FilterInterface
 *
 * @api
 */
interface FilterInterface
{
    /**
     * Const to show if filter is text.
     */
    public const FILTER_TEXT_TRUE = 'true';

    /**
     * Return filter text.
     *
     * @return string
     */
    public function getFilterText();

    /**
     * Set filter text.
     *
     * @param string $filterText
     * @return $this
     */
    public function setFilterText($filterText);

    /**
     * Return weight of the rule to see if it can be applied.
     *
     * @return float
     */
    public function getWeight();

    /**
     * Set weight of the rule to see if it can be applied.
     *
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight);

    /**
     * Return name of the FilterTextGenerator class.
     *
     * @return string
     */
    public function getFilterTextGeneratorClass();

    /**
     * Set name of the FilterTextGenerator class.
     *
     * @param string $filterTextGeneratorClass
     * @return $this
     */
    public function setFilterTextGeneratorClass($filterTextGeneratorClass);

    /**
     * Return arguments for FilterTextGenerator class.
     *
     * @return string
     */
    public function getFilterTextGeneratorArguments();

    /**
     * Set arguments for FilterTextGenerator class.
     *
     * @param string $arguments
     * @return $this
     */
    public function setFilterTextGeneratorArguments($arguments);

    /**
     * Set flag if a rule is coupon based.
     *
     * @param bool $isCoupon
     * @return $this
     */
    public function setIsCoupon(bool $isCoupon);

    /**
     * Return flag if coupon is rule based.
     *
     * @return bool
     */
    public function isCoupon():bool;
}

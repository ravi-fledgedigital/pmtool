<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Model\Condition;

use Magento\AdvancedRule\Model\Condition\FilterInterface;

/**
 * Class to save Filter related logic for rules into database.
 *
 * @codeCoverageIgnore
 */
class Filter extends \Magento\Framework\Model\AbstractModel implements FilterInterface
{
    public const KEY_FILTER_TEXT = 'filter_text';
    public const KEY_WEIGHT = 'weight';
    public const KEY_FILTER_TEXT_GENERATOR_CLASS = 'filter_text_generator_class';
    public const KEY_FILTER_TEXT_GENERATOR_ARGUMENTS = 'filter_text_generator_arguments';
    public const IS_COUPON = 'is_coupon';

    /**
     * @inheritdoc
     */
    public function getFilterText()
    {
        return $this->getData(self::KEY_FILTER_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setFilterText($filterText)
    {
        return $this->setData(self::KEY_FILTER_TEXT, $filterText);
    }

    /**
     * @inheritdoc
     */
    public function getWeight()
    {
        return $this->getData(self::KEY_WEIGHT);
    }

    /**
     * @inheritdoc
     */
    public function setWeight($weight)
    {
        return $this->setData(self::KEY_WEIGHT, $weight);
    }

    /**
     * @inheritdoc
     */
    public function getFilterTextGeneratorClass()
    {
        return $this->getData(self::KEY_FILTER_TEXT_GENERATOR_CLASS);
    }

    /**
     * @inheritdoc
     */
    public function setFilterTextGeneratorClass($filterTextGeneratorClass)
    {
        return $this->setData(self::KEY_FILTER_TEXT_GENERATOR_CLASS, $filterTextGeneratorClass);
    }

    /**
     * @inheritdoc
     */
    public function getFilterTextGeneratorArguments()
    {
        return $this->getData(self::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS);
    }

    /**
     * @inheritdoc
     */
    public function setFilterTextGeneratorArguments($arguments)
    {
        return $this->setData(self::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function setIsCoupon(bool $isCoupon)
    {
        return $this->setData(self::IS_COUPON, $isCoupon);
    }

    /**
     * @inheritdoc
     */
    public function isCoupon():bool
    {
        return $this->getData(self::IS_COUPON);
    }
}

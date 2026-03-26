<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Data;

use Magento\AdobeCommerceWebhooks\Api\Data\HookRuleInterface;
use Magento\Framework\DataObject;

/**
 * Webhook rule for processing webapi requests.
 */
class HookRule extends DataObject implements HookRuleInterface
{
    /**
     * @inheritDoc
     */
    public function setField(string $field): HookRuleInterface
    {
        return $this->setData(self::FIELD, $field);
    }

    /**
     * @inheritDoc
     */
    public function getField(): string
    {
        return (string)$this->getData(self::FIELD);
    }

    /**
     * @inheritDoc
     */
    public function setOperator(string $operator): HookRuleInterface
    {
        return $this->setData(self::OPERATOR, $operator);
    }

    /**
     * @inheritDoc
     */
    public function getOperator(): string
    {
        return (string)$this->getData(self::OPERATOR);
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): HookRuleInterface
    {
        return $this->setData(self::VALUE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return (string)$this->getData(self::VALUE);
    }
}

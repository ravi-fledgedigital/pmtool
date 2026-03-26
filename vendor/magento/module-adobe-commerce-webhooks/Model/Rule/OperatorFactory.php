<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Rule;

/**
 * Factory for creating operator objects
 */
class OperatorFactory
{
    /**
     * @param OperatorInterface[] $operators
     */
    public function __construct(private array $operators)
    {
    }

    /**
     * Creates operator object based on the given name.
     *
     * Throws an exception in the case when an operator with a given name is not registered.
     *
     * @param string $operatorName
     * @return OperatorInterface
     * @throws OperatorException
     */
    public function create(string $operatorName): OperatorInterface
    {
        if (!isset($this->operators[$operatorName])) {
            throw new OperatorException(__('Operator %1 is not registered', $operatorName));
        }

        return $this->operators[$operatorName];
    }

    /**
     * Returns a list of the registered rule operators.
     *
     * @return array
     */
    public function getOperatorsList(): array
    {
        return array_keys($this->operators);
    }
}

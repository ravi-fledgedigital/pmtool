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

namespace Magento\AdobeCommerceWebhooks\Model\Filter\Converter;

use Exception;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Creates an instance of a field converter.
 */
class ConverterFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Creates an instance of a field converter given the input converter class name.
     *
     * Throws an exception if the converter class cannot be created or doesn't implement @see FieldConverterInterface.
     *
     * @param string $converterClass
     * @return FieldConverterInterface
     * @throws InvalidArgumentException
     */
    public function create(string $converterClass): FieldConverterInterface
    {
        try {
            $converterClassInstance = $this->objectManager->get($converterClass);
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                __(
                    'Can\'t create field converter class "%1": "%2"',
                    $converterClass,
                    $e->getMessage()
                )
            );
        }

        if (!$converterClassInstance instanceof FieldConverterInterface) {
            throw new InvalidArgumentException(
                __(
                    'Converter class "%1" does not implement "%2"',
                    $converterClass,
                    FieldConverterInterface::class
                )
            );
        }

        return $converterClassInstance;
    }
}

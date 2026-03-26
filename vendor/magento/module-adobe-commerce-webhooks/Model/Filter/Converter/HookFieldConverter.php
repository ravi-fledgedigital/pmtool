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

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use Magento\Framework\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Executes conversion of a value using a field's converter class.
 */
class HookFieldConverter
{
    /**
     * @param ConverterFactory $converterFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ConverterFactory $converterFactory,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Converts an input value using the toExternalFormat method of the input field's converter class.
     *
     * @param mixed $value
     * @param HookField $field
     * @param array $pluginData
     * @return mixed
     */
    public function convertToExternalFormat(mixed $value, HookField $field, array $pluginData)
    {
        $converterClass = $field->getConverter();
        try {
            $classInstance = $this->converterFactory->create($converterClass);
            return $classInstance->toExternalFormat($value, $field, $pluginData);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Unable to apply the converter to hook field \'%s\'. Exception: %s',
                    $field->getName(),
                    $e->getMessage()
                )
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Field conversion failed for hook field \'%s\'. Error: %s',
                    $field->getName(),
                    $e->getMessage()
                )
            );
        }
        return $value;
    }

    /**
     * Converts an input value using the fromExternalFormat method of the input field's converter class.
     *
     * @param mixed $value
     * @param HookField $field
     * @param array $pluginData
     * @return mixed
     */
    public function convertFromExternalFormat(mixed $value, HookField $field, array $pluginData)
    {
        $converterClass = $field->getConverter();
        try {
            $classInstance = $this->converterFactory->create($converterClass);
            return $classInstance->fromExternalFormat($value, $field, $pluginData);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Unable to apply the converter for hook field \'%s\' to the operation value. Exception: %s',
                    $field->getName(),
                    $e->getMessage()
                )
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Operation value conversion failed with the converter for hook field \'%s\'. Error: %s',
                    $field->getName(),
                    $e->getMessage()
                )
            );
        }
        return $value;
    }
}

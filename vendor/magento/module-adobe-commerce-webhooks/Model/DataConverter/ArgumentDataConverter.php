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

namespace Magento\AdobeCommerceWebhooks\Model\DataConverter;

use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\DataObject;

/**
 * Converts mixed input arguments to the array structure
 */
class ArgumentDataConverter implements ArgumentDataConverterInterface
{
    /**
     * @param HydratorInterface $hydrator
     */
    public function __construct(private HydratorInterface $hydrator)
    {
    }

    /**
     * Converts array of mixed values to array representations.
     *
     * @param array $arguments
     * @param int $depth
     * @return array
     */
    public function convert(array $arguments, int $depth = 1): array
    {
        if ($depth > self::MAX_DEPTH) {
            return $arguments;
        }

        $data = [];

        foreach ($arguments as $key => $variable) {
            if (is_array($variable)) {
                $data[$key] = $this->convert($variable, $depth + 1);
                continue;
            }
            if (is_scalar($variable) || $variable === null) {
                $data[$key] = $variable;
                continue;
            }
            if ($variable instanceof DataObject) {
                $data[$key] = $this->convert($variable->getData(), $depth + 1);
                continue;
            }
            if (method_exists($variable, 'toArray')) {
                $data[$key] = $this->convert($variable->toArray(), $depth + 1);
                continue;
            }
            $data[$key] = $this->extract($variable);
        }

        return $data;
    }

    /**
     * Extracts data using "hydrator" class, returns null in case of exception
     *
     * @param mixed $variable
     * @return array|null
     */
    private function extract(mixed $variable): ?array
    {
        try {
            return $this->hydrator->extract($variable);
        } catch (\Exception $e) {
            return null;
        }
    }
}

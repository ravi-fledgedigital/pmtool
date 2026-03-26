<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Grid;

use DateTime;
use DateTimeInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validate and format received custom column data
 */
class ColumnsDataFormatter
{
    /**
     * Validate and Format data depending on the type
     *
     * @param mixed $data
     * @param string $type
     * @return mixed
     * @throws ValidatorException
     */
    public function format(mixed $data, string $type): mixed
    {
        $this->validateDataType($data, $type);
        if ($type === 'date') {
            return (new DateTime())->createFromFormat(
                DateTimeInterface::ATOM,
                $data
            )
            ->format('M d, Y h:i:s A');
        }
        return $data;
    }

    /**
     * Convert the received JSON data
     *
     * @param mixed $data
     * @param string $type
     * @return void
     * @throws ValidatorException
     */
    public function validateDataType(mixed $data, string $type): void
    {
        match ($type) {
            'date' => $this->validateDate($data),
            default => $this->validatePrimitiveType($data, $type)
        };
    }

    /**
     * Validate received value matches registered column data type
     *
     * @param mixed $data
     * @param string $type
     * @return void
     * @throws ValidatorException
     */
    private function validatePrimitiveType(mixed $data, string $type): void
    {
        $valid = match ($type) {
            'string' => is_string($data),
            'integer' => is_int($data),
            'float' => is_float($data),
            'boolean' => is_bool($data),
            default => false
        };
        if (!$valid) {
            throw new ValidatorException(__('Unexpected data format'));
        }
    }

    /**
     * Validate received ISO8601 date
     *
     * @param mixed $data
     * @return void
     * @throws ValidatorException
     */
    private function validateDate(mixed $data): void
    {
        $dateTime = (new DateTime())->createFromFormat(DateTimeInterface::ATOM, $data);
        if (!$dateTime || $dateTime->format(DateTimeInterface::ATOM) !== $data) {
            throw new ValidatorException(__('Unexpected data format'));
        }
    }
}

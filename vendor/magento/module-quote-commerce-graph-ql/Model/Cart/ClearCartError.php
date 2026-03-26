<?php
/************************************************************************
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

namespace Magento\QuoteCommerceGraphQl\Model\Cart;

/**
 * Clear Cart Error. Identify error code based on the message
 */
class ClearCartError
{
    private const NOT_FOUND = 'NOT_FOUND';
    private const UNDEFINED = 'UNDEFINED';
    private const UNAUTHORISED = 'UNAUTHORISED';
    private const INACTIVE = 'INACTIVE';

    /**
     * List of error messages and codes.
     */
    private const MESSAGE_CODES = [
        "Could not find a cart" => self::NOT_FOUND,
        "The current user cannot perform operations on cart" => self::UNAUTHORISED,
        "The cart isn't active" => self::INACTIVE
    ];

    /**
     * Get message error code.
     *
     * @param string $message
     * @return string
     */
    public function getErrorCode(string $message): string
    {
        foreach (self::MESSAGE_CODES as $codeMessage => $code) {
            if (stripos($message, $codeMessage) !== false) {
                return $code;
            }
        }
        /* If no code was matched, return the default one */
        return self::UNDEFINED;
    }
}

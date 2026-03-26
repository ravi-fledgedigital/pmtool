<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2026 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Request-scoped registry that holds the active quote loaded during the current request.
 *
 * This provides a transport-agnostic way to access the quote being operated on,
 * without relying on checkout session state (which doesn't exist in stateless contexts like GraphQL).
 */
class ActiveQuoteRegistry implements ResetAfterRequestInterface
{
    /**
     * @var CartInterface|null
     */
    private ?CartInterface $quote = null;

    /**
     * Register the active quote for the current request.
     *
     * @param CartInterface $quote
     * @return void
     */
    public function set(?CartInterface $quote): void
    {
        $this->quote = $quote;
    }

    /**
     * Retrieve the active quote for the current request.
     *
     * @return CartInterface|null
     */
    public function get(): ?CartInterface
    {
        return $this->quote;
    }

    /**
     * Clear the active quote registry after the request completes.
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->quote = null;
    }
}

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

namespace Magento\AdobeCommerceWebhooks\Plugin\Quote;

use Magento\AdobeCommerceWebhooks\Model\ActiveQuoteRegistry;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Registers the active quote in the ActiveQuoteRegistry when loaded via GetCartForUser::execute().
 */
class RegisterActiveQuotePlugin
{
    /**
     * Constructor.
     *
     * @param ActiveQuoteRegistry $quoteRegistry
     */
    public function __construct(
        private readonly ActiveQuoteRegistry $quoteRegistry
    ) {
    }

    /**
     * Register the quote after it is loaded via GetCartForUser::execute().
     *
     * @param GetCartForUser $subject
     * @param Quote $result
     * @param string $cartHash
     * @param int|null $customerId
     * @param int $storeId
     * @return Quote
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        GetCartForUser $subject,
        Quote $result,
        string $cartHash,
        ?int $customerId,
        int $storeId
    ): Quote {
        if ((null === $customerId) || (0 === $customerId)) {
            $this->quoteRegistry->set($result);
        } else {
            $this->quoteRegistry->set(null);
        }
        return $result;
    }
}

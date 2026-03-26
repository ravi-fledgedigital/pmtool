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

namespace Magento\AdobeCommerceWebhooks\Model\Context;

use Magento\AdobeCommerceWebhooks\Model\ActiveQuoteRegistry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * Webhook context provider for `context_checkout_session`.
 *
 * Replaces Magento\Checkout\Model\Session in the ContextPool. The default mapping calls
 * Session::getQuote(), which relies on PHP session storage or customer auth state — neither
 * of which exist for guest users in stateless contexts (GraphQL/REST).
 *
 * This class resolves the quote from the ActiveQuoteRegistry, which is populated by a plugin
 * on GetCartForUser::execute() — the transport-agnostic layer all checkout flows use.
 *
 * All other method calls are proxied to the real checkout session to preserve existing behavior
 * for any non-quote context fields admins may have configured.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CheckoutSessionContext
{
    /**
     * @param ActiveQuoteRegistry $quoteRegistry
     * @param QuoteFactory $quoteFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private readonly ActiveQuoteRegistry $quoteRegistry,
        private readonly QuoteFactory $quoteFactory,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * Get the active quote from the registry, bypassing session.
     *
     * @return CartInterface
     */
    public function getQuote(): CartInterface
    {
        $registeredQuote = $this->quoteRegistry->get();
        if (null !== $registeredQuote && $registeredQuote->getId()) {
            return $registeredQuote;
        }
        return $this->checkoutSession->getQuote();
    }

    /**
     * Proxy all other method calls to the real checkout session.
     *
     * Preserves existing behavior for any non-quote context fields
     * (e.g. context_checkout_session.get_step_data) that admins may have configured.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->checkoutSession->{$name}(...$arguments);
    }
}

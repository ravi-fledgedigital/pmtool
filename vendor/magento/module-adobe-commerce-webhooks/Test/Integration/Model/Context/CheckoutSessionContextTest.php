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

namespace Magento\AdobeCommerceWebhooks\Test\Integration\Model\Context;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\ActiveQuoteRegistry;
use Magento\AdobeCommerceWebhooks\Model\Context\CheckoutSessionContext;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for CheckoutSessionContext and the ActiveQuoteRegistry plugin chain.
 *
 * Verifies that context_checkout_session.get_quote returns complete quote data
 * for guest carts in stateless (GraphQL) contexts where session is unavailable.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckoutSessionContextTest extends TestCase
{
    /**
     * Test that loading a guest cart via GetCartForUser::execute() populates the ActiveQuoteRegistry
     * and that CheckoutSessionContext::getQuote() returns the correct quote with items.
     */
    #[
        AppArea('graphql'),
        DataFixture(ProductFixture::class, ['price' => 10.00], as: 'product'),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(
            AddProductToCart::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 2]
        )
    ]
    public function testGuestQuoteAvailableInContextAfterCartLoad(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Quote $fixtureCart */
        $fixtureCart = DataFixtureStorageManager::getStorage()->get('cart');
        $this->assertNotNull($fixtureCart->getId(), 'Fixture cart must have a persisted ID');
        $cartId = (int) $fixtureCart->getId();
        $cartHash = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class)->execute($cartId);
        $registry = $objectManager->get(ActiveQuoteRegistry::class);
        /** @var GetCartForUser $getCartForUser */
        $getCartForUser = $objectManager->get(GetCartForUser::class);
        // Simulate a fresh request: the GuestCart fixture already called get(),
        // so clear the registry to prove afterGet repopulates it.
        $registry->set($objectManager->create(Quote::class));
        // Load the cart via GetCartForUser::execute() — the afterGet plugin should register it
        $loadedCart = $getCartForUser->execute($cartHash, null, 1);
        // Verify the registry was populated by the plugin
        $registeredQuote = $registry->get();
        $this->assertNotNull($registeredQuote);
        $this->assertNotNull($registeredQuote->getId(), 'Registered quote must have an ID');
        $this->assertEquals($cartId, (int) $registeredQuote->getId());
        $this->assertSame($loadedCart, $registeredQuote);
        // Verify CheckoutSessionContext returns the same quote
        /** @var CheckoutSessionContext $context */
        $context = $objectManager->get(CheckoutSessionContext::class);
        $contextQuote = $context->getQuote();
        $this->assertNotNull($contextQuote->getId(), 'Context quote must have an ID');
        $this->assertEquals($cartId, (int) $contextQuote->getId());
        $this->assertFalse((bool) $contextQuote->getCustomerId(), 'Quote should be a guest cart');
        $this->assertCount(1, $contextQuote->getAllVisibleItems());
        $this->assertEquals(2, (int) $contextQuote->getAllVisibleItems()[0]->getQty());
    }

    /**
     * Test the full ContextRetriever chain: context_checkout_session.get_quote resolves to
     * a quote object with data when the registry is populated.
     *
     */
    #[
        AppArea('graphql'),
        DataFixture(ProductFixture::class, ['price' => 25.00], as: 'product'),
        DataFixture(GuestCart::class, as: 'cart'),
        DataFixture(
            AddProductToCart::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        )
    ]
    public function testContextRetrieverResolvesGuestQuote(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Quote $fixtureCart */
        $fixtureCart = DataFixtureStorageManager::getStorage()->get('cart');
        $this->assertNotNull($fixtureCart->getId(), 'Fixture cart must have a persisted ID');
        $cartId = (int) $fixtureCart->getId();
        // Load the cart to trigger the afterGet plugin
        $getCartForUser = $objectManager->get(GetCartForUser::class);
        $cartHash = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class)->execute($cartId);
        $getCartForUser->execute($cartHash, null, 1);
        /** @var ContextRetriever $contextRetriever */
        $contextRetriever = $objectManager->get(ContextRetriever::class);
        // Resolve context_checkout_session.get_quote — the same path webhooks use
        $quote = $contextRetriever->getContextValue('context_checkout_session.get_quote');
        $this->assertInstanceOf(Quote::class, $quote);
        $this->assertNotNull($quote->getId(), 'Context-resolved quote must have an ID');
        $this->assertEquals($cartId, (int) $quote->getId());
        $this->assertFalse((bool) $quote->getCustomerId());
    }

    /**
     * Test that CheckoutSessionContext returns an empty quote when the registry is not populated.
     */
    #[AppArea('graphql')]
    public function testEmptyQuoteReturnedWhenRegistryIsEmpty(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        // Fresh registry — nothing loaded
        $registry = $objectManager->create(ActiveQuoteRegistry::class);
        $context = $objectManager->create(CheckoutSessionContext::class, [
            'quoteRegistry' => $registry,
        ]);
        $quote = $context->getQuote();
        $this->assertInstanceOf(Quote::class, $quote);
        $this->assertNull($quote->getId());
    }
}

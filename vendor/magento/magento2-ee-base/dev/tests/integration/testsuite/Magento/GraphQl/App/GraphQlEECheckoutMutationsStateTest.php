<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\App;

use Magento\GraphQl\App\State\GraphQlStateDiff;

/**
 * Tests the dispatch method in the GraphQl Controller class using a simple product query
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @magentoAppArea graphql
 */
class GraphQlEECheckoutMutationsStateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GraphQlStateDiff
     */
    private ?GraphQlStateDiff $graphQlStateDiff  = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->graphQlStateDiff = new GraphQlStateDiff($this);
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->graphQlStateDiff->tearDown();
        $this->graphQlStateDiff = null;
        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @return void
     */
    public function testAddGiftCardToCart(): void
    {
        $cartId = $this->graphQlStateDiff->getCartIdHash('test_quote');
        $query = $this->getAddGiftCardToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId, 'giftCardCode' => 'giftcardaccount_fixture'],
            [],
            [],
            'applyGiftCardToCart',
            '"data":{"applyGiftCardToCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/Reward/_files/reward_points.php
     * @return void
     */

    public function testAddRewardPointsToCart(): void
    {
        $cartId = $this->graphQlStateDiff->getCartIdHash('test_quote');
        $query = $this->getAddRewardPointsToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId],
            [],
            ['email' => 'customer@example.com', 'password' => 'password'],
            'applyRewardPointsToCart',
            '"data":{"applyRewardPointsToCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     * @return void
     */
    public function testAddStoreCreditToCart(): void
    {
        $cartId = $this->graphQlStateDiff->getCartIdHash('test_quote');
        $query = $this->getAddStoreCreditToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId],
            [],
            ['email'=>'customer@example.com', 'password'=>'password'],
            'applyStoreCreditToCart',
            '"data":{"applyStoreCreditToCart":',
            $this
        );
    }

    private function getAddGiftCardToCartQuery()
    {
        return <<<'QUERY'
            mutation($cartId: String!, $giftCardCode: String!) {
              applyGiftCardToCart(
                input: {
                  cart_id: $cartId
                  gift_card_code: $giftCardCode
                }
              ) {
                cart {
                  id
                  applied_gift_cards {
                    code
                  }
                }
              }
            }
            QUERY;
    }

    private function getAddRewardPointsToCartQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: ID!) {
              applyRewardPointsToCart(
                cartId: $cartId
              ) {
                cart {
                  prices{
                    grand_total {
                      value
                    }
                  }
                }
              }
            }
            QUERY;
    }

    private function getAddStoreCreditToCartQuery()
    {
        return <<<'QUERY'
            mutation($cartId: String!)
            {
              applyStoreCreditToCart(input:{cart_id:$cartId})
              {
                cart{
                  applied_store_credit{
                  enabled
                    applied_balance
                    {
                      currency
                      value
                    }
                    current_balance{
                      currency
                      value
                    }
                  }

                }

              }
            }
        QUERY;
    }
}

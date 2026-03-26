<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCard;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class UpdateGiftCardProductInCartTest extends GraphQlAbstract
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedId;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quote = $objectManager->get(Quote::class);
        $this->quoteIdToMaskedId = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/quote_with_items_saved.php
     */
    public function testUpdateGiftCardDataInCart()
    {
        $qtyUpdate = 2.0;
        $sku = 'gift-card-with-allowed-messages';
        $this->quote->load('test_order_item_with_gift_card_items', 'reserved_order_id');

        $quoteItems = $this->quote->getAllItems();
        $guestQuoteMaskedId = (string)$this->quoteIdToMaskedId->execute((int)$this->quote->getId());
        $quoteItemId = (int)$quoteItems[0]->getId();
        $giftCardUpdateData = [
            'giftcard_sender_name' => 'Sender 2',
            'giftcard_sender_email' => 'sender2@email.com',
            'giftcard_recipient_name' => 'Recipient 2',
            'giftcard_recipient_email' => 'recipient2@email.com',
            'giftcard_message' => 'Message 2',
            'custom_giftcard_amount' =>'20',
        ];

        $query = $this->getUpdateQuery(
            $guestQuoteMaskedId,
            $quoteItemId,
            $qtyUpdate,
            $giftCardUpdateData,
            $this->getUidMap()
        );

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        self::assertArrayHasKey('updateCartItems', $response);
        self::assertArrayHasKey('cart', $response['updateCartItems']);
        $cart = $response['updateCartItems']['cart'];
        $giftCardItem = end($cart['items']);
        self::assertEquals($sku, $giftCardItem['product']['sku']);
        self::assertArrayHasKey('amount', $giftCardItem);
        self::assertArrayHasKey('sender_name', $giftCardItem);
        self::assertArrayHasKey('sender_email', $giftCardItem);
        self::assertArrayHasKey('recipient_name', $giftCardItem);
        self::assertArrayHasKey('recipient_email', $giftCardItem);
        self::assertArrayHasKey('message', $giftCardItem);
        self::assertEquals(
            (float) $giftCardUpdateData['custom_giftcard_amount'],
            (float) $giftCardItem['amount']['value']
        );
    }

    /**
     * @return string[]
     */
    private function getUidMap(): array
    {
        return [
            'Custom Giftcard Amount' => 'Z2lmdGNhcmQvY3VzdG9tX2dpZnRjYXJkX2Ftb3VudA==',
            'Sender Name' => 'Z2lmdGNhcmQvZ2lmdGNhcmRfc2VuZGVyX25hbWU=',
            'Sender Email' => 'Z2lmdGNhcmQvZ2lmdGNhcmRfc2VuZGVyX2VtYWls',
            'Recipient Name' => 'Z2lmdGNhcmQvZ2lmdGNhcmRfcmVjaXBpZW50X25hbWU=',
            'Recipient Email' => 'Z2lmdGNhcmQvZ2lmdGNhcmRfcmVjaXBpZW50X2VtYWls',
            'Message' => 'Z2lmdGNhcmQvZ2lmdGNhcmRfbWVzc2FnZQ=='
        ];
    }

    /**
     * @param string $maskedQuoteId
     * @param int $itemId
     * @param float $quantity
     * @param array $giftCardUpdateData
     * @param array $uidMap
     * @return string
     */
    private function getUpdateQuery(
        string $maskedQuoteId,
        int $itemId,
        float $quantity,
        array $giftCardUpdateData,
        array $uidMap
    ): string {
        return <<<QUERY
mutation {
  updateCartItems(input: {
    cart_id: "{$maskedQuoteId}"
    cart_items:[
      {
        cart_item_id: {$itemId}
        quantity: {$quantity}
        customizable_options: [{
              uid: "{$uidMap['Custom Giftcard Amount']}"
              value_string: "{$giftCardUpdateData['custom_giftcard_amount']}"
            }, {
              uid: "{$uidMap['Sender Name']}"
              value_string: "{$giftCardUpdateData['giftcard_sender_name']}"
            }, {
              uid: "{$uidMap['Sender Email']}"
              value_string: "{$giftCardUpdateData['giftcard_sender_email']}"
            }, {
              uid: "{$uidMap['Recipient Name']}"
              value_string: "{$giftCardUpdateData['giftcard_recipient_name']}"
            }, {
              uid: "{$uidMap['Recipient Email']}"
              value_string: "{$giftCardUpdateData['giftcard_recipient_email']}"
            }, {
              uid: "{$uidMap['Message']}"
              value_string: "{$giftCardUpdateData['giftcard_message']}"
      	}]
      }
    ]
  }) {
    cart {
      items {
        uid
        quantity
        product {
          sku
        }
        ... on GiftCardCartItem {
          sender_name
          sender_email
          recipient_name
          recipient_email
          message
          amount {
            value
            currency
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }
}

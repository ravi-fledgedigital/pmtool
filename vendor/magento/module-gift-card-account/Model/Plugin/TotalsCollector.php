<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\GiftCardAccount\Helper\Data;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\Quote\Model\Quote;
use Magento\GiftCardAccount\Model\Giftcardaccount as ModelGiftcardaccount;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Plugin to make right collection for Gift Card Accounts
 */
class TotalsCollector
{
    /**
     * @var Data
     */
    protected $giftCardAccountData;

    /**
     * Gift card account giftcardaccount
     *
     * @var GiftcardaccountFactory
     */
    protected $giftCAFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param Data $giftCardAccountData
     * @param GiftcardaccountFactory $giftCAFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        Data $giftCardAccountData,
        GiftcardaccountFactory $giftCAFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->giftCAFactory = $giftCAFactory;
        $this->giftCardAccountData = $giftCardAccountData;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Apply before collect
     *
     * @param Quote\TotalsCollector $subject
     * @param Quote $quote
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollect(
        Quote\TotalsCollector $subject,
        Quote                 $quote
    ) {
        $this->resetGiftCardAmount($quote);
    }

    /**
     * Apply before collectQuoteTotals
     *
     * @param Quote\TotalsCollector $subject
     * @param Quote $quote
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollectQuoteTotals(
        Quote\TotalsCollector $subject,
        Quote                 $quote
    ) {
        $this->resetGiftCardAmount($quote);
    }

    /**
     * Reset quote Gift Card Accounts amount
     *
     * @param Quote $quote
     * @return void
     */
    private function resetGiftCardAmount(Quote $quote) : void
    {
        $quote->setBaseGiftCardsAmount(0);
        $quote->setGiftCardsAmount(0);

        $quote->setBaseGiftCardsAmountUsed(0);
        $quote->setGiftCardsAmountUsed(0);
        $quote->unsUnusedGiftCards();

        $baseAmount = 0;
        $amount = 0;
        $cards = $this->giftCardAccountData->getCards($quote);
        foreach ($cards as $k => &$card) {
            $model = $this->giftCAFactory->create()->load($card[ModelGiftcardaccount::ID]);
            if ($model->isExpired() || $model->getBalance() == 0) {
                unset($cards[$k]);
            } elseif ($model->getBalance() != $card[ModelGiftcardaccount::BASE_AMOUNT]) {
                $card[ModelGiftcardaccount::BASE_AMOUNT] = $model->getBalance();
                $card[ModelGiftcardaccount::AMOUNT] = $this->priceCurrency->round(
                    $this->priceCurrency->convert(
                        $card[ModelGiftcardaccount::BASE_AMOUNT],
                        $quote->getStore()
                    )
                );
                $baseAmount += $card[ModelGiftcardaccount::BASE_AMOUNT];
                $amount += $card[ModelGiftcardaccount::AMOUNT];
            } else {
                $card[ModelGiftcardaccount::AMOUNT] = $this->priceCurrency->round(
                    $this->priceCurrency->convert(
                        $card[ModelGiftcardaccount::BASE_AMOUNT],
                        $quote->getStore()
                    )
                );
                $baseAmount += $card[ModelGiftcardaccount::BASE_AMOUNT];
                $amount += $card[ModelGiftcardaccount::AMOUNT];
            }
        }
        if (!empty($cards)) {
            $this->giftCardAccountData->setCards($quote, $cards);
            $this->giftCardAccountData->setUnusedCards($quote, $cards);
        }

        $quote->setBaseGiftCardsAmount($baseAmount);
        $quote->setGiftCardsAmount($amount);
    }
}

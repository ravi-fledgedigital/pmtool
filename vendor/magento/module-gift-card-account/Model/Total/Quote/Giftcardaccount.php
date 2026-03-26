<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Total\Quote;

use Magento\GiftCardAccount\Helper\Data;
use Magento\GiftCardAccount\Model\Giftcardaccount as ModelGiftcardaccount;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class Giftcardaccount extends AbstractTotal
{
    /**
     * @var Data
     */
    protected $_giftCardAccountData = null;

    /**
     * Gift card account giftcardaccount
     *
     * @var GiftcardaccountFactory
     */
    protected $_giftCAFactory;

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
        $this->_giftCAFactory = $giftCAFactory;
        $this->_giftCardAccountData = $giftCardAccountData;
        $this->priceCurrency = $priceCurrency;
        $this->setCode('giftcardaccount');
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        parent::_resetState();
        $this->setCode('giftcardaccount');
    }

    /**
     * Collect giftcertificate totals for specified address
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(
        Quote                                    $quote,
        ShippingAssignmentInterface              $shippingAssignment,
        Total $total
    ) {
        $baseGiftAmountLeft = $quote->getBaseGiftCardsAmount() - $quote->getBaseGiftCardsAmountUsed();
        $giftAmountLeft = $quote->getGiftCardsAmount() - $quote->getGiftCardsAmountUsed();

        if ($baseGiftAmountLeft >= $total->getBaseGrandTotal()) {
            $baseAdjustedGiftAmount = $total->getBaseGrandTotal();
            $adjustedGiftAmount = $total->getGrandTotal();

            $total->setBaseGrandTotal(0);
            $total->setGrandTotal(0);
        } else {
            $baseAdjustedGiftAmount = $baseGiftAmountLeft;
            $adjustedGiftAmount = $giftAmountLeft;

            $total->setBaseGrandTotal($total->getBaseGrandTotal() - $baseGiftAmountLeft);
            $total->setGrandTotal($total->getGrandTotal() - $giftAmountLeft);
        }

        $addressCards = [];
        $usedAddressCards = [];
        $quoteCards = $this->_sortGiftCards($this->_giftCardAccountData->getUnusedCards($quote));
        if ($baseAdjustedGiftAmount) {
            $baseAdjustedUsedGiftAmountLeft = $baseAdjustedGiftAmount;
            $adjustedUsedGiftAmountLeft = $adjustedGiftAmount;
            foreach ($quoteCards as &$quoteCard) {
                $card = $quoteCard;
                if ($baseAdjustedUsedGiftAmountLeft > 0) {
                    $baseThisCardUsedAmount = min(
                        $quoteCard[ModelGiftcardaccount::BASE_AMOUNT],
                        $baseAdjustedUsedGiftAmountLeft
                    );
                    $thisCardUsedAmount = min(
                        $quoteCard[ModelGiftcardaccount::AMOUNT],
                        $adjustedUsedGiftAmountLeft
                    );
                } else {
                    $baseThisCardUsedAmount = $thisCardUsedAmount = 0;
                }

                // avoid possible errors in future comparisons
                $card[ModelGiftcardaccount::BASE_AMOUNT] = round($baseThisCardUsedAmount, 4);
                $card[ModelGiftcardaccount::AMOUNT] = round($thisCardUsedAmount, 4);
                $quoteCard[ModelGiftcardaccount::BASE_AMOUNT] -= round($baseThisCardUsedAmount, 4);
                $quoteCard[ModelGiftcardaccount::AMOUNT] -= round($thisCardUsedAmount, 4);
                $addressCards[] = $card;
                $baseAdjustedUsedGiftAmountLeft -= $baseThisCardUsedAmount;
                $adjustedUsedGiftAmountLeft -= $thisCardUsedAmount;
                if ($baseThisCardUsedAmount) {
                    $usedAddressCards[] = $card;
                }
            }
        }
        $this->_giftCardAccountData->setUsedCards($total, $usedAddressCards);
        $this->_giftCardAccountData->setCards($total, $addressCards);
        $this->_giftCardAccountData->setUnusedCards($quote, $quoteCards);

        $baseTotalUsed = $quote->getBaseGiftCardsAmountUsed() + $baseAdjustedGiftAmount;
        $totalUsed = $quote->getGiftCardsAmountUsed() + $adjustedGiftAmount;

        $quote->setBaseGiftCardsAmountUsed($baseTotalUsed);
        $quote->setGiftCardsAmountUsed($totalUsed);

        $total->setBaseGiftCardsAmount($baseAdjustedGiftAmount);
        $total->setGiftCardsAmount($adjustedGiftAmount);

        return $this;
    }

    /**
     * Return shopping cart total row items
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(Quote $quote, Total $total)
    {
        $giftCards = $this->_giftCardAccountData->getCards($total);
        if (!empty($giftCards)) {
            return [
                'code' => $this->getCode(),
                'title' => __('Gift Cards'),
                'value' => -$total->getGiftCardsAmount(),
                'gift_cards' => $giftCards
            ];
        }

        return null;
    }

    /**
     * Sort gift cards based on the amount
     *
     * @param array $in
     * @return mixed
     */
    protected function _sortGiftCards(array $in)
    {
        usort($in, [$this, 'compareGiftCards']);
        return $in;
    }

    /**
     * Compare gift cards amount and sort thereby
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public function compareGiftCards(array $a, array $b)
    {
        if ($a[ModelGiftcardaccount::BASE_AMOUNT] == $b[ModelGiftcardaccount::BASE_AMOUNT]) {
            return 0;
        }
        return $a[ModelGiftcardaccount::BASE_AMOUNT] > $b[ModelGiftcardaccount::BASE_AMOUNT] ? 1 : -1;
    }
}

<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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

namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\Sales\Model\Order;

class SaveGiftCards implements ObserverInterface
{
    /**
     * @param Json $serializer
     */
    public function __construct(
        private readonly Json $serializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof Order) {
            return;
        }

        if ($order->getExtensionAttributes() == null) {
            return;
        }

        $extensionAttributes = $order->getExtensionAttributes();

        $attributes = [
            'gift_cards' => $extensionAttributes->getGiftCards()
                ? $this->serializer->serialize($this->getGiftCards($order))
                : null,
            'base_gift_cards_amount' => $extensionAttributes->getBaseGiftCardsAmount(),
            'gift_cards_amount' => $extensionAttributes->getGiftCardsAmount(),
            'base_gift_cards_invoiced' => $extensionAttributes->getBaseGiftCardsInvoiced(),
            'gift_cards_invoiced' => $extensionAttributes->getGiftCardsInvoiced(),
            'base_gift_cards_refunded' => $extensionAttributes->getBaseGiftCardsRefunded(),
            'gift_cards_refunded' => $extensionAttributes->getGiftCardsRefunded()
        ];

        foreach ($attributes as $name => $value) {
            // Prioritize raw data changes over changes to extension attributes
            if ($value !== null && !$order->dataHasChangedFor($name)) {
                $order->setData($name, $value);
            }
        }
    }

    /**
     * Get gift cards as array
     *
     * @param Order $order
     * @return array
     */
    private function getGiftCards(Order $order): array
    {
        $result = [];
        $existingGiftCardsById = [];
        $existingGiftCards = $order->getGiftCards() ? $this->serializer->unserialize($order->getGiftCards()) : [];

        foreach ($existingGiftCards as $existingGiftCard) {
            if (isset($existingGiftCard[Giftcardaccount::ID])) {
                $existingGiftCardsById[$existingGiftCard[Giftcardaccount::ID]] = $existingGiftCard;
            }
        }

        foreach ($order->getExtensionAttributes()->getGiftCards() as $giftCard) {
            $result[] = [
                Giftcardaccount::ID => $giftCard->getId(),
                Giftcardaccount::CODE => $giftCard->getCode(),
                Giftcardaccount::AMOUNT => $giftCard->getAmount(),
                Giftcardaccount::BASE_AMOUNT => $giftCard->getBaseAmount(),
                Giftcardaccount::AUTHORIZED => $existingGiftCardsById[$giftCard->getId()] ?? $giftCard->getBaseAmount()
            ];
        }
        return $result;
    }
}

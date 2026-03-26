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

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Clear Customer Cart
 */
class ClearCartItems
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * ClearCartItems constructor
     *
     * @param CartRepositoryInterface $cartRepository
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GetCartForUser $getCartForUser
    ) {
        $this->cartRepository = $cartRepository;
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * Remove all items from cart
     *
     * @param string $maskedCartId
     * @param int|null $customerId
     * @param int $storeId
     * @return Quote
     * @throws GraphQlNoSuchEntityException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function execute($maskedCartId, $customerId, $storeId):Quote
    {
        $cart = $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);
        if ($cart->getItemsCount()) {
            $cart->removeAllItems();
            $this->cartRepository->save($cart);
        }
        return $cart;
    }
}

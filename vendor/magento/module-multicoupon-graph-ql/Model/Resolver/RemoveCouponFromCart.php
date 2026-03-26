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

namespace Magento\MulticouponGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Multicoupon\Api\CouponManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

class RemoveCouponFromCart implements ResolverInterface
{
    /**
     * @param CouponManagementInterface $couponManagement
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        private CouponManagementInterface $couponManagement,
        private GetCartForUser $getCartForUser
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }
        $maskedCartId = $args['input']['cart_id'];
        $couponCodes = $args['input']['coupon_codes'] ?? [];

        $currentUserId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);

        try {
            $this->couponManagement->remove((int)$cart->getId(), $couponCodes);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (preg_match('/The "\d+" Cart doesn\'t contain products/', $message)) {
                $message = 'Cart does not contain products';
            }
            throw new GraphQlNoSuchEntityException(__($message), $e);
        }

        return [
            'cart' => [
                'model' => $cart
            ]
        ];
    }
}

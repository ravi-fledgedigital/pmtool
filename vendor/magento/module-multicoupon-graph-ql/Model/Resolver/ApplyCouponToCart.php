<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained from
 * Adobe.
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

/**
 * Class to extend the ApplyCouponToCart resolver to accept multiple coupons
 */
class ApplyCouponToCart implements ResolverInterface
{
    /**
     * @param GetCartForUser $getCartForUser
     * @param CouponManagementInterface $couponManagement
     */
    public function __construct(
        private GetCartForUser $getCartForUser,
        private CouponManagementInterface $couponManagement
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
        $cart = $this->getCartForUser->execute(
            $args['input']['cart_id'],
            $context->getUserId(),
            (int) $context->getExtensionAttributes()->getStore()->getId()
        );

        $type = $args['input']['type'] ?? 'REPLACE';
        $couponCodes = $args['input']['coupon_codes'] ?? [];
        try {
            if ($type == 'APPEND') {
                $this->couponManagement->append((int) $cart->getId(), $couponCodes);
            } else {
                $this->couponManagement->replace((int) $cart->getId(), $couponCodes);
            }
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return [
            'cart' => [
                'model' => $cart
            ]
        ];
    }
}

<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2021 Adobe
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
 */

/** @var $product \Magento\Catalog\Model\Product */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);

$product->setTypeId(\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Gift Card')
    ->setSku('gift-card-without-fixed-amount')
    ->setDescription('Gift Card Description')
    ->setMetaTitle('Gift Card Meta Title')
    ->setMetaKeyword('Gift Card Meta Keyword')
    ->setMetaDescription('Gift Card Meta Description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCanSaveCustomOptions(true)
    ->setHasOptions(true)
    ->setAllowOpenAmount(1)
    ->setGiftcardType(0)
    ->setData('open_amount_min', 100)
    ->setData('open_amount_max', 1500)
    ->setUseConfigEmailTemplate(false)
    ->setEmailTemplate('Default')
    ->setUseConfigLifetime(false)
    ->setLifetime(20)
    ->setUseConfigIsRedeemable(true)
    ->setUseConfigAllowMessage(false)
    ->setAllowMessage(false)
    ->save();

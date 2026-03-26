<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento_ProductOverrideDataExporter::Test/_files/permissions_for_two_root_categories_rollback.php'
);

/** @var \Magento\Framework\Registry $registry */
$registry = Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productIds = [155, 156];
foreach ($productIds as $productId) {
    /** @var $product \Magento\Catalog\Model\Product */
    $product = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);
    $product->load($productId);
    if ($product->getId()) {
        $product->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

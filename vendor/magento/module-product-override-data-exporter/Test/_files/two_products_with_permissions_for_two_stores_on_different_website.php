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

use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Catalog\Model\GetCategoryByName;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento_ProductOverrideDataExporter::Test/_files/permissions_for_two_root_categories.php'
);

/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$productRepository = $objectManager->create(
    \Magento\Catalog\Api\ProductRepositoryInterface::class
);

$categoryLinkRepository = $objectManager->create(
    \Magento\Catalog\Api\CategoryLinkRepositoryInterface::class,
    [
        'productRepository' => $productRepository
    ]
);

/** @var Magento\Catalog\Api\CategoryLinkManagementInterface $linkManagement */
$categoryLinkManagement = $objectManager->create(
    \Magento\Catalog\Api\CategoryLinkManagementInterface::class,
    [
        'productRepository' => $productRepository,
        'categoryLinkRepository' => $categoryLinkRepository
    ]
);

$getCategoryByName = $objectManager->create(GetCategoryByName::class);

$defaultRootCategoryId = $getCategoryByName->execute("Default Category")->getId();
$secondRootCategoryId = $getCategoryByName->execute("Second Root Category")->getId();

$installer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Catalog\Setup\CategorySetup::class
);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$secondWebsite = $websiteRepository->get('test');
/** @var $product \Magento\Catalog\Model\Product */
$product = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);
$product->setTypeId(
    \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
)->setId(
    155
)->setAttributeSetId(
    $installer->getAttributeSetId('catalog_product', 'Default')
)->setStoreId(
    1
)->setWebsiteIds(
    [1]
)->setName(
    'First Simple Product Permission Test'
)->setSku(
    '12345-1'
)->setPrice(
    45.67
)->setWeight(
    56
)->setStockData(
    ['use_config_manage_stock' => 0]
)->setCategoryIds(
    [$defaultRootCategoryId]
)->setVisibility(
    \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
)->setStatus(
    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
)->save();
$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [$defaultRootCategoryId]
);

/** @var $product \Magento\Catalog\Model\Product */
$product = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);
$product->setTypeId(
    \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
)->setId(
    156
)->setAttributeSetId(
    $installer->getAttributeSetId('catalog_product', 'Default')
)->setStoreId(
    1
)->setWebsiteIds(
    [$secondWebsite->getId()]
)->setName(
    'Second Simple Product Permission Test'
)->setSku(
    '12345-2'
)->setPrice(
    45.67
)->setWeight(
    56
)->setStockData(
    ['use_config_manage_stock' => 0]
)->setCategoryIds(
    [$secondRootCategoryId]
)->setVisibility(
    \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
)->setStatus(
    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
)->save();
$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [$secondRootCategoryId]
);

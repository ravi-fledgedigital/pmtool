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

use Magento\TestFramework\Catalog\Model\GetCategoryByName;

/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$getCategoryByName = $objectManager->create(GetCategoryByName::class);

$defaultRootCategoryId = $getCategoryByName->execute("Default Category")->getId();
$secondRootCategoryId = $getCategoryByName->execute("Second Root Category")->getId();

// Create category permission for first category
/** @var $firstCategoryPermissionAllow \Magento\CatalogPermissions\Model\Permission */
$firstCategoryPermissionAllow = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\CatalogPermissions\Model\Permission::class
);
$firstCategoryPermissionAllow->setCategoryId(
    $defaultRootCategoryId
)->setCustomerGroupId(
    null
)->setGrantCatalogCategoryView(
    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
)->setGrantCatalogProductPrice(
    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
)->setGrantCheckoutItems(
    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
)->save();

// Create category permission for second category
/** @var $secondCategoryPermissionAllow \Magento\CatalogPermissions\Model\Permission */
$secondCategoryPermissionAllow = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\CatalogPermissions\Model\Permission::class
);
$secondCategoryPermissionAllow->setCategoryId(
    $secondRootCategoryId
)->setCustomerGroupId(
    null
)->setGrantCatalogCategoryView(
    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
)->setGrantCatalogProductPrice(
    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
)->setGrantCheckoutItems(
    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
)->save();

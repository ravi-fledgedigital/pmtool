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

use Magento\CatalogPermissions\Model\Permission;
use Magento\TestFramework\Helper\Bootstrap;

/** @var \Magento\Framework\Registry $registry */
$registry = Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $permission Permission */
$permission = Bootstrap::getObjectManager()->create(
    Permission::class
);
$permission->getCollection()->load()->walk('delete');
$permission->getCollection()->load()->walk('delete');

// Cleanup indexer table
/** @var $index \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index */
$index = Bootstrap::getObjectManager()->create(
    \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index::class
);
$index->getConnection()->delete(
    $index->getMainTable()
);
$index->getConnection()->delete(
    $index->getMainTable() .
    \Magento\CatalogPermissions\Model\Indexer\AbstractAction::PRODUCT_SUFFIX
);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

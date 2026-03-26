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

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\Group;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

$objectManager = Bootstrap::getObjectManager();
/** @var CategoryRepository $categoryRepository */
$categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);
$categoryCollectionFactory = $objectManager->get(CollectionFactory::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Magento\Store\Model\Store $store */
$store = $objectManager->create(\Magento\Store\Model\Store::class);
$store->load('test_store_1');

if ($store->getId()) {
    $storeId = $store->getId();

    $urlRewriteCollectionFactory = $objectManager->get(
        UrlRewriteCollectionFactory::class
    );
    /** @var UrlRewriteCollection $urlRewriteCollection */
    $urlRewriteCollection = $urlRewriteCollectionFactory->create();
    $urlRewriteCollection->addFieldToFilter('store_id', ['eq' => $storeId]);
    $urlRewrites = $urlRewriteCollection->getItems();
    /** @var UrlRewrite $urlRewrite */
    foreach ($urlRewrites as $urlRewrite) {
        try {
            $urlRewrite->delete();
        } catch (\Exception) {
            // already removed
        }
    }

    $store->delete();
}

/** @var Collection $categoryCollection */
$categoryCollection = $categoryCollectionFactory->create();
$category = $categoryCollection
    ->addAttributeToFilter(CategoryInterface::KEY_NAME, 'Second Root Category')
    ->setPageSize(1)
    ->getFirstItem();

if ($category->getId()) {
    /** Delete the second store group **/
    $group = $objectManager->create(Group::class);
    /** @var $group Group */
    $groupId = $group->load('test_store_group_1', 'code')->getId();
    if ($groupId) {
        $group->delete();
    }

    $categoryRepository->delete($category);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

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
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$categoryCollectionFactory = $objectManager->get(CollectionFactory::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$website = $websiteRepository->get('base');

/** @var Collection $categoryCollection */
$categoryCollection = $categoryCollectionFactory->create();
$rootCategory = $categoryCollection
    ->addAttributeToFilter(CategoryInterface::KEY_NAME, 'Second Root Category')
    ->setPageSize(1)
    ->getFirstItem();

$categoryFactory = $objectManager->get(CategoryFactory::class);
$categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);

/** @var Category $rootCategory */
$rootCategory = $categoryFactory->create();
$rootCategory->isObjectNew(true);
$rootCategory->setName('Second Root Category')
    ->setParentId(Category::TREE_ROOT_ID)
    ->setIsActive(true)
    ->setPosition(2);
$rootCategory = $categoryRepository->save($rootCategory);

$groupFactory = $objectManager->get(GroupFactory::class);
/** @var GroupResource $groupResource */
$groupResource = $objectManager->create(GroupResource::class);
/** @var Group $storeGroup */
$storeGroup = $groupFactory->create();
$storeGroup->setCode('test_store_group_1')
    ->setName('Test Store Group 1')
    ->setRootCategoryId($rootCategory->getId())
    ->setWebsite($website);
$groupResource->save($storeGroup);

$storeFactory = $objectManager->get(StoreFactory::class);
/** @var StoreResource $storeResource */
$storeResource = $objectManager->create(StoreResource::class);
/** @var Store $store */
$store = $storeFactory->create();
$store->setCode('test_store_1')
    ->setName('Test Store 1')
    ->setWebsiteId($website->getId())
    ->setGroup($storeGroup)
    ->setSortOrder(10)
    ->setIsActive(1);
$storeResource->save($store);

/* Refresh stores memory cache */
$objectManager->get(StoreManagerInterface::class)->reinitStores();

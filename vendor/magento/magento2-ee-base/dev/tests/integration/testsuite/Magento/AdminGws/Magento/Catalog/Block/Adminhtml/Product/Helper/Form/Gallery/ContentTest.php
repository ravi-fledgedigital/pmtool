<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\Role as AuthorizationRole;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test Catalog Gallery Content Block with AdminGws
 *
 * @magentoAppArea adminhtml
 */
class ContentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/product_with_category.php
     * @magentoDataFixture Magento/AdminGws/_files/two_roles_for_different_websites.php
     */
    public function testAdminWithoutExclusiveAccessNotAllowedEditGallery()
    {
        $objectManager = Bootstrap::getObjectManager();

        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('in-stock-product', true);

        /** @var $website Website */
        $website = $objectManager->get(Website::class);
        $website->load('test_website', 'code');

        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $stockItem->setWebsiteId((int)$website->getId());
        $productRepository->save($product);

        // Requires to be stored in registry as block access the product from it
        $registry = $objectManager->get(\Magento\Framework\Registry::class);
        $registry->register('current_product', $product);

        // Set the Restricted Admin as active
        /** @var AuthorizationRole $adminRole */
        $adminRole = $objectManager->get(AuthorizationRole::class);
        $adminRole->load('role_has_test_website_access_only', 'role_name');

        /** @var AdminGwsRole $adminGwsRole */
        $adminGwsRole = $objectManager->get(AdminGwsRole::class);
        $adminGwsRole->setAdminRole($adminRole);

        /** @var LayoutInterface $layout */
        $layout = $objectManager->get(LayoutInterface::class);

        /** @var Content $contentBlock */
        $contentBlock = $layout->createBlock(Content::class);

        /** @var Gallery $galleryBlock */
        $galleryBlock = $layout->createBlock(Gallery::class);
        // Requires to bind bocks to one another and call some methods to set up inner logic
        $galleryBlock->setChild('content', $contentBlock);
        $galleryBlock->getContentHtml();

        $this->assertFalse(
            $contentBlock->isEditEnabled(),
            'Admin w/o exclusive rights is not allowed to edit images or videos'
        );
    }
}

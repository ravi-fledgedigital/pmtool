<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery;

use Magento\AdminGws\Model\Role;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content;

class ContentPlugin
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(
        Role $role,
    ) {
        $this->role = $role;
    }

    /**
     * Check if gallery content is editable by checking Admin exclusive website access.
     *
     * @param Content $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsEditEnabled(
        Content $subject,
        bool $result
    ): bool {
        /** @var ProductInterface $product */
        $product = $subject->getParentBlock()->getDataObject();
        if (!$product) {
            return $result;
        }

        return $this->role->hasExclusiveAccess($product->getWebsiteIds());
    }
}

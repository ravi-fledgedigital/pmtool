<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Block;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class ProductImpressionJs extends Template
{
    private $urlBuilder;

    private $registry;

    public function __construct(
        Context  $context,
        Registry $registry
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry   = $registry;

        parent::__construct($context);
    }

    public function getImpressionUrl(): string
    {
        return trim($this->urlBuilder->getUrl('sorting/track/productImpression'), '/');
    }

    public function getProductId()
    {
        $product = $this->registry->registry('current_product');

        return $product ? $product->getId() : false;
    }
}

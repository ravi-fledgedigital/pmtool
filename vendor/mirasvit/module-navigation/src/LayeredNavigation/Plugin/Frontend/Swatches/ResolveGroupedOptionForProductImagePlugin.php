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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Swatches;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Swatches\Model\Plugin\ProductImage;
use Mirasvit\LayeredNavigation\Service\GroupedOptionResolver;

/**
 * Resolves grouped option codes to actual option IDs for product image selection.
 *
 * @see \Magento\Swatches\Model\Plugin\ProductImage::beforeGetImage()
 */
class ResolveGroupedOptionForProductImagePlugin
{
    private $request;

    private $resolver;

    /** @var array<int, bool> */
    private $processedProducts = [];

    public function __construct(
        RequestInterface $request,
        GroupedOptionResolver $resolver
    ) {
        $this->request  = $request;
        $this->resolver = $resolver;
    }

    public function beforeBeforeGetImage(
        ProductImage $subject,
        AbstractProduct $block,
        ProductModel $product,
        $location,
        array $attributes = []
    ): array {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return [$block, $product, $location, $attributes];
        }

        $this->applyResolvedOptionsToRequest($product);

        return [$block, $product, $location, $attributes];
    }

    private function applyResolvedOptionsToRequest(ProductModel $product): void
    {
        $productId = (int)$product->getId();

        if (isset($this->processedProducts[$productId])) {
            return;
        }

        $this->processedProducts[$productId] = true;

        $resolved = $this->resolver->resolve($product);

        foreach ($resolved as $attributeCode => $optionId) {
            $this->request->setParam($attributeCode, $optionId);
        }
    }
}

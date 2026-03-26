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

namespace Mirasvit\Brand\Plugin\Frontend\Magento\Catalog\Block\Product\ListProduct;

use Magento\Catalog\Helper\Output;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Service\BrandLogoService;

class AddBrandNamePlugin
{
    private $isProductListBrandNameEnabled;

    private $brandLogoService;

    private $brandAttribute;

    public function __construct(
        Config           $config,
        BrandLogoService $brandLogoService
    ) {
        $this->isProductListBrandNameEnabled = $config->getBrandLogoConfig()->isProductListBrandNameEnabled();
        $this->brandLogoService              = $brandLogoService;
        $this->brandAttribute                = $config->getGeneralConfig()->getBrandAttribute();
    }

    /**
     * @see Output::productAttribute()
     *
     * @param Output $subject
     * @param string $result
     * @param mixed  $product
     * @param mixed  $attributeHtml
     * @param string $attribute
     *
     * @return string
     */
    public function afterProductAttribute(
        Output $subject,
        ?string $result,
        $product,
        $attributeHtml,
        string $attribute
    ): ?string {
        if ($attribute !== 'name' || !$this->isProductListBrandNameEnabled || !$this->brandAttribute) {
            return $result;
        }

        if (!is_object($product)) {
            return $result;
        }

        $optionId = $product->getData($this->brandAttribute);
        $optionId = is_numeric($optionId) ? (int)$optionId : 0;

        if ($optionId === 0) {
            $rawValue = $product->getResource()->getAttributeRawValue(
                $product->getId(),
                $this->brandAttribute,
                $product->getStoreId()
            );

            $optionId = is_numeric($rawValue) ? (int)$rawValue : 0;
        }

        if (!$optionId) {
            return $result;
        }

        $this->brandLogoService->setBrandDataByOptionId($optionId);
        $brandTitle = $this->brandLogoService->getBrandTitle();

        if ($brandTitle === '') {
            return $result;
        }

        return '<span class="mst_brand-name">' . htmlspecialchars($brandTitle, ENT_QUOTES, 'UTF-8') . '</span> ' . (string)$result;
    }
}

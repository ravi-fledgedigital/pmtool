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

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Service\BrandLogoService;
use Mirasvit\Core\Service\CompatibilityService;

class AddBrandLogoPlugin
{
    private $isProductListProduct;

    private $brandLogoService;

    private $brandAttribute;

    public function __construct(
        Config $config,
        BrandLogoService $brandLogoService
    ) {
        $this->isProductListProduct = $config->getBrandLogoConfig()->isProductListBrandLogoEnabled();
        $this->brandLogoService     = $brandLogoService;
        $this->brandAttribute       = $config->getGeneralConfig()->getBrandAttribute();
    }

    /**
     * @param ListProduct $subject
     * @param callable    $proceed
     * @param Product     $product
     *
     * @return string
     */
    public function aroundGetProductDetailsHtml(
        Template $subject,
        callable $proceed,
        Product $product
    ) {
        $html = $proceed($product);

        if (!is_object($product) || !$this->isProductListProduct || !$this->brandAttribute) {
            return $html;
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
            return $html;
        }

        $this->brandLogoService->setBrandDataByOptionId($optionId);
        $logo = $this->brandLogoService->getLogoHtml();

        return $html . $logo;
    }

    public function afterGetData(Template $subject, $result, ?string $key = null)
    {
        if (!$key || $key !== 'viewModel') {
            return $result;
        }

        if ($subject->getRequest()->getFullActionName() !== 'brand_brand_view') {
            return $result;
        }

        $version = CompatibilityService::getVersion();

        list($a, $b, $c) = explode('.', $version);

        if ($a == 2 && $b == 4 && $c >= 3) {
            $optionsViewModel = CompatibilityService::getObjectManager()->get('\Magento\Catalog\ViewModel\Product\OptionsData');

            return $optionsViewModel;
        }

        return $result;
    }
}

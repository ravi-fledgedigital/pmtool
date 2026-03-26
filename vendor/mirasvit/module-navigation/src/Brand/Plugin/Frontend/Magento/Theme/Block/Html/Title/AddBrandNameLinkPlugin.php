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

namespace Mirasvit\Brand\Plugin\Frontend\Magento\Theme\Block\Html\Title;

use Magento\Framework\Registry;
use Magento\Theme\Block\Html\Title;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Service\BrandLogoService;

class AddBrandNameLinkPlugin
{
    private $registry;

    private $brandLogoService;

    private $config;

    private $brandAttribute;

    public function __construct(
        Registry $registry,
        Config $config,
        BrandLogoService $brandLogoService
    ) {
        $this->registry         = $registry;
        $this->config           = $config;
        $this->brandLogoService = $brandLogoService;
        $this->brandAttribute   = $config->getGeneralConfig()->getBrandAttribute();
    }

    public function afterToHtml(Title $subject, string $result): string
    {
        if (!$this->config->getBrandLogoConfig()->isProductPageBrandNameLinkEnabled()) {
            return $result;
        }

        if (!$this->brandAttribute) {
            return $result;
        }

        $product = $this->registry->registry('current_product');

        if (!$product) {
            return $result;
        }

        $optionId = (int)$product->getData($this->brandAttribute);

        if (!$optionId) {
            return $result;
        }

        $this->brandLogoService->setBrandDataByOptionId($optionId);

        if (!$this->brandLogoService->isBrandPageAvailable()) {
            return $result;
        }

        $brandTitle = $this->brandLogoService->getBrandTitle();
        $brandUrl   = $this->brandLogoService->getBrandUrl();

        if (!$brandTitle || !$brandUrl) {
            return $result;
        }

        $escapedBrandUrl   = htmlspecialchars($brandUrl, ENT_QUOTES, 'UTF-8');
        $escapedBrandTitle = htmlspecialchars($brandTitle, ENT_QUOTES, 'UTF-8');
        $ariaLabel         = htmlspecialchars(__('View all %1 products', $brandTitle)->render(), ENT_QUOTES, 'UTF-8');

        $brandLink = '<a href="' . $escapedBrandUrl . '"'
            . ' class="mst_brand-name-link"'
            . ' aria-label="' . $ariaLabel . '"'
            . ' title="' . $ariaLabel . '">'
            . $escapedBrandTitle
            . '</a> ';

        $result = preg_replace(
            '/(<span class="base"[^>]*>)/',
            '$1' . $brandLink,
            $result
        );

        return $result;
    }
}

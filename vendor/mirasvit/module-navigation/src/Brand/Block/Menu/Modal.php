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

namespace Mirasvit\Brand\Block\Menu;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Brand\Service\BrandListService;
use Mirasvit\Brand\Model\Config\GeneralConfig;
use Mirasvit\Brand\Model\Config\Source\MenuModeOptions;
use Mirasvit\Brand\Service\BrandUrlService;

class Modal extends Template
{
    private $config;

    private $brandUrlService;

    private $brandListService;

    public function __construct(
        GeneralConfig    $config,
        BrandUrlService  $brandUrlService,
        BrandListService $brandListService,
        Context          $context
    ) {
        $this->config           = $config;
        $this->brandUrlService  = $brandUrlService;
        $this->brandListService = $brandListService;

        parent::__construct($context);
    }

    public function getBrandsPageUrl(): string
    {
        return $this->brandUrlService->getBaseBrandUrl();
    }

    public function getMenuTitle(): ?string
    {
        return $this->config->getBrandsMenuTitle();
    }

    public function getBrandAlphabet(): array
    {
        return $this->brandListService->getBrandAlphabet();
    }

    public function getAjaxMenuUrl(): string
    {
        return $this->_urlBuilder->getUrl('brand/ajax/menu');
    }

    protected function _toHtml(): string
    {
        if ($this->config->getBrandsMenuMode() === MenuModeOptions::MODE_MODAL) {
            return parent::_toHtml();
        }

        return '';
    }
}

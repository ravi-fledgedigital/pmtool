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


namespace Mirasvit\Brand\Block;


use Magento\Catalog\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Registry;
use Mirasvit\Brand\Service\BrandUrlService;

class Breadcrumbs extends \Magento\Catalog\Block\Breadcrumbs
{
    private $brandUrlService;

    private $config;

    private $registry;

    public function __construct(
        Context $context,
        Data $catalogData,
        BrandUrlService $brandUrlService,
        Config $config,
        Registry $registry,
        array $data = []
    ) {
        $this->brandUrlService = $brandUrlService;
        $this->config          = $config;
        $this->registry        = $registry;

        parent::__construct($context, $catalogData, $data);
    }

    protected function _prepareLayout()
    {
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            );

            $brand = $this->registry->getBrand();

            $breadcrumbsBlock->addCrumb(
                'brand',
                [
                    'label' => $this->config->getGeneralConfig()->getBrandLinkLabel() ? : __('Brands'),
                    'title' => __('Brands'),
                    'link' => $brand ? $this->brandUrlService->getBaseBrandUrl() : ''
                ]
            );

            if ($brand) {
                $breadcrumbsBlock->addCrumb(
                    $brand->getUrlKey(),
                    [
                        'label' => $brand->getLabel(),
                        'title' => $brand->getLabel(),
                        'link' => ''
                    ]
                );
            }
        }
        return parent::_prepareLayout();
    }
}

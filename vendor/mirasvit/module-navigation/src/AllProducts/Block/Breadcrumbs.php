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


namespace Mirasvit\AllProducts\Block;

use Mirasvit\AllProducts\Config\Config;
use Magento\Catalog\Helper\Data;
use Magento\Framework\View\Element\Template\Context;

class Breadcrumbs extends \Magento\Catalog\Block\Breadcrumbs
{
    private $config;

    public function __construct(
        Context $context,
        Data $catalogData,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;

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

            $breadcrumbsBlock->addCrumb(
                'all_products',
                [
                    'label' => $this->config->getTitle() ?: __('All Products'),
                    'title' => $this->config->getTitle() ?: __('All Products'),
                ]
            );
        }
        return parent::_prepareLayout();
    }
}

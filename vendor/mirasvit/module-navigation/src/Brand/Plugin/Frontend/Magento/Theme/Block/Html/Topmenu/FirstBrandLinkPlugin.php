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

namespace Mirasvit\Brand\Plugin\Frontend\Magento\Theme\Block\Html\Topmenu;

use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\UrlInterface;
use Magento\Theme\Block\Html\Topmenu;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Model\Config\Source\BrandsLinkPositionOptions;
use Mirasvit\Brand\Service\BrandUrlService;

class FirstBrandLinkPlugin
{
    private $url;

    private $config;

    private $brandUrlService;

    private $nodeFactory;

    public function __construct(
        UrlInterface    $url,
        Config          $config,
        BrandUrlService $brandUrlService,
        NodeFactory     $nodeFactory
    ) {
        $this->url             = $url;
        $this->config          = $config;
        $this->brandUrlService = $brandUrlService;
        $this->nodeFactory     = $nodeFactory;
    }

    public function beforeGetHtml(
        Topmenu $subject,
        string  $outermostClass,
        string  $childrenWrapClass,
        int     $limit
    ): array {
        if ($this->isBrandLinkEnabled()) {
            $node = $this->nodeFactory->create(
                [
                    'data'    => $this->_getNodeAsArray(),
                    'idField' => 'id',
                    'tree'    => $subject->getMenu()->getTree(),
                    'parent'  => $subject->getMenu(),
                ]
            );
            $subject->getMenu()->addChild($node);
        }

        return [$outermostClass, $childrenWrapClass, $limit];
    }

    protected function _getNodeAsArray(): array
    {
        $url = $this->brandUrlService->getBaseBrandUrl();

        return [
            'name'       => $this->config->getGeneralConfig()->getBrandLinkLabel() ? : __('Brands'),
            'id'         => 'm___all_brands_page_link',
            'class'      => 'mst__menu-item-brands',
            'url'        => $url,
            'has_active' => false,
            'is_active'  => $url === $this->url->getCurrentUrl(),
        ];
    }

    protected function getBrandLinkPosition(): int
    {
        return BrandsLinkPositionOptions::TOP_MENU_FIRST;
    }

    protected function isBrandLinkEnabled(): bool
    {
        return $this->getBrandLinkPosition() === (int)$this->config->getGeneralConfig()->getBrandLinkPosition();
    }
}

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

namespace Mirasvit\Scroll\Plugin\Frontend;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Mirasvit\Core\Service\CompatibilityService;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\Scroll\Model\ConfigProvider;
use Magento\Catalog\Model\Product\ProductList\Toolbar;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;

/**
 * @see \Magento\Framework\App\ActionInterface::execute()
 */
class ScrollResponsePlugin
{
    const PARAM_IS_SCROLL = 'is_scroll';

    private $configProvider;

    private $request;

    private $response;

    private $layout;

    private $toolbarMemorizer;

    public function __construct(
        ConfigProvider $config,
        RequestInterface $request,
        ResponseInterface $response,
        LayoutInterface $layout,
        ToolbarMemorizer $toolbarMemorizer
    ) {
        $this->configProvider   = $config;
        $this->request          = $request;
        $this->response         = $response;
        $this->layout           = $layout;
        $this->toolbarMemorizer = $toolbarMemorizer;
    }

    /**
     * @param object                               $subject
     * @param \Magento\Framework\App\Response\Http $response
     *
     * @return \Magento\Framework\App\Response\Http
     */
    public function afterExecute($subject, $response)
    {
        if (!$this->canProcess()) {
            return $response;
        }

        /** @var \Mirasvit\Scroll\Block\Scroll $scrollBlock */
        $scrollBlock = $this->layout->getBlock('product.list.scroll');

        if (!$scrollBlock) {
            return $response;
        }

        $products = $this->getProductListBlock();

        // Compatibility with Mirasvit_CacheWarmer's "Forbid cache flushing" setting to ensure data consistency
        if (!$this->configProvider->isForbidCacheFlush()) {
            $this->response->setNoCacheHeaders();
        }

        if ($this->isRedirectOnToolbarAction()) {
            $this->response->_resetState();
        }

        return $this->response->representJson(SerializeService::encode([
            'products' => $products ? $products->toHtml() : '',
            'config'   => $scrollBlock->getJsConfig(),
        ]));
    }

    private function canProcess(): bool
    {
        return $this->configProvider->isEnabled()
            && $this->request->isAjax()
            && $this->request->has(self::PARAM_IS_SCROLL);
    }

    private function getProductListBlock(): ?BlockInterface
    {
        if (in_array($this->request->getFullActionName(), ['brand_brand_view', 'all_products_page_index_index'], true)) {
            $block = $this->layout->getBlock('category.products.list');
        } elseif (in_array($this->request->getFullActionName(), ['mpbrand_index_view'])) {
            $block = $this->layout->getBlock('brand.category.products');
        } else {
            $block = $this->layout->getBlock('category.products') ? : $this->layout->getBlock('search.result');
        }

        return $block ? $block : null;
    }

    private function isRedirectOnToolbarAction(): bool
    {
        // apply for magento 2.4.7+
        $is247orHigher = version_compare(CompatibilityService::getVersion(), "2.4.7", ">=");
        
        $params = $this->request->getParams();

        return $is247orHigher && $this->response->isRedirect() && $this->toolbarMemorizer->isMemorizingAllowed() 
            && empty(array_intersect([
                Toolbar::ORDER_PARAM_NAME,
                Toolbar::DIRECTION_PARAM_NAME,
                Toolbar::MODE_PARAM_NAME,
                Toolbar::LIMIT_PARAM_NAME
            ], array_keys($params))) === false;
    }
}

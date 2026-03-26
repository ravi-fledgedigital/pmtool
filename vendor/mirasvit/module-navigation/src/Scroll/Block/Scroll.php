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

namespace Mirasvit\Scroll\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;
use Mirasvit\Scroll\Model\Config\Source\ModeSource;
use Mirasvit\Scroll\Model\ConfigProvider;

class Scroll extends Template
{
    private $configProvider;

    public function __construct(
        ConfigProvider $config,
        Context        $context,
        array          $data = []
    ) {
        $this->configProvider = $config;

        parent::__construct($context, $data);
    }

    public function getPager(): ?Pager
    {
        return $this->getLayout()->getBlock('product_list_toolbar_pager') ? : null;
    }

    public function getJsConfig(): array
    {
        $pager = $this->getPager();
        if (!$pager || !$pager->getCollection()) {
            return [];
        }

        $currentPage = (int)$pager->getCurrentPage();

        $prevText = $this->configProvider->getLoadPrevText();
        $nextText = $this->configProvider->getLoadNextText();

        $pagerHtml = $this->preparePagerHtml($pager);

        return [
            'mode'                => $this->configProvider->getMode(),
            'pageLimit'           => $this->configProvider->getPageLimit(),
            'pageNum'             => $currentPage,
            'initPageNum'         => $currentPage,
            'prevPageNum'         => $currentPage === 1 ? false : $currentPage - 1,
            'nextPageNum'         => $currentPage === (int)$pager->getLastPageNum() ? false : $currentPage + 1,
            'lastPageNum'         => $pager->getLastPageNum(),
            'loadPrevText'        => (string)__($prevText),
            'loadNextText'        => (string)__($nextText),
            'itemsTotal'          => (int)$pager->getCollection()->getSize(),
            'itemsLimit'          => (int)$pager->getLimit(),
            'progressBarEnabled'  => $this->configProvider->isProgressBarEnabled(),
            'progressBarText'     => $this->configProvider->getProgressBarLabel(),
            'productListSelector' => $this->configProvider->getProductListSelector(),
            'pager'               => $pagerHtml
        ];
    }

    public function getInitConfig(): ?array
    {
        $jsConfig = $this->getJsConfig();

        if (empty($jsConfig)) {
            return null;
        }

        return [
            $this->configProvider->getProductListSelector() => [
                'Mirasvit_Scroll/js/scroll' => $jsConfig,
            ],
        ];
    }

    public function isEnabled(): bool
    {
        return $this->configProvider->isEnabled() && $this->configProvider->getProductListSelector();
    }

    public function getMode()
    {
        return $this->configProvider->getMode();
    }

    private function preparePagerHtml(Pager $pager): string
    {
        if ($this->configProvider->getMode() !== ModeSource::MODE_BUTTON_DEFAULT) {
            return '';
        }

        $pagerHtml = $pager->toHtml();

        $pagerHtml = preg_replace('/&amp;is_scroll=1/', '', $pagerHtml);
        $pagerHtml = preg_replace('/\?is_scroll=1(&amp;)?|\?isAjax=1(&amp;)?/', '?', $pagerHtml);

        return $pagerHtml;

    }
}

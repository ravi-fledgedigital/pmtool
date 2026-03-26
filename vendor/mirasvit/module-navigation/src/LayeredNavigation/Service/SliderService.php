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

namespace Mirasvit\LayeredNavigation\Service;

use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\SeoFilter\Model\ConfigProvider as SeoFilterConfig;
use Mirasvit\SeoFilter\Service\RewriteService;
use Mirasvit\SeoFilter\Service\UrlService as SeoUrlService;
use Magento\Framework\Module\Manager;

class SliderService
{
    const MATCH_PREFIX            = 'slider_match_prefix_';
    const SLIDER_DATA             = 'sliderdata';
    const SLIDER_URL_TEMPLATE     = self::SLIDER_REPLACE_VARIABLE . '_from-' . self::SLIDER_REPLACE_VARIABLE . '_to';
    const SLIDER_REPLACE_VARIABLE = '[attr]';

    protected static $sliderOptions;

    private          $configProvider;

    private          $rewriteService;

    private          $request;

    private          $storeId;

    private          $urlHelper;

    private          $urlBuilder;

    private          $moduleManager;

    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        SeoUrlService $urlHelper,
        RewriteService $rewriteService,
        Manager $moduleManager
    ) {
        $this->request        = $request;
        $this->urlBuilder     = $urlBuilder;
        $this->urlHelper      = $urlHelper;
        $this->rewriteService = $rewriteService;
        $this->configProvider = $configProvider;
        $this->moduleManager  = $moduleManager;
        $this->storeId        = (int)$storeManager->getStore()->getStoreId();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getSliderData(array $facetedData, string $requestVar, array $fromToData, string $url, int $sliderStep): array
    {
        // in some cases price ranges might not have from to values
        // so we generating them from keys
        // fixes the compatibility issue with Amasty ElasticSearch
        foreach ($facetedData as $key => $value) {
            $ranges = explode('_', (string)$key);

            if (count($ranges) != 2) {
                continue;
            }

            if (isset($value['from']) || isset($value['to'])) {
                continue;
            }

            $facetedData[$key]['from'] = (int)$ranges[0];
            $facetedData[$key]['to'] = (int)$ranges[1];
        }

        $min = null;
        $max = null;

        foreach ($facetedData as $item) {
            if (isset($item['from'])) {
                $min = $min > $item['from'] ? $item['from'] : $min;
            }

            if (isset($item['to'])) {
                $max = $max < $item['to'] ? $item['to'] : $max;
            }
        }

        if (isset($facetedData['min'])) {
            $min = $facetedData['min']['price'];
            unset($facetedData['min']);
        }

        if (isset($facetedData['max'])) {
            $max = $facetedData['max']['price'];
            unset($facetedData['max']);
        }

        $from = ($fromToData) ? $fromToData['from'] : $min;
        $to   = ($fromToData) ? $fromToData['to'] : $max;

        $sliderData = [
            'min'        => $min,
            'max'        => $max,
            'requestVar' => $requestVar,
            'from'       => $from,
            'to'         => $to,
            'url'        => $url,
            'step'       => $sliderStep
        ];


        return $sliderData;
    }

    public function getSliderUrl(FilterInterface $filter, string $template): string
    {
        $query = [$filter->getRequestVar() => $template];

        return $this->urlBuilder->getUrl('*/*/*', [
            '_current'     => true,
            '_use_rewrite' => true,
            '_query'       => $query,
        ]);
    }

    public function getParamTemplate(FilterInterface $filter): string
    {
        $requestVar = $filter->getRequestVar();

        return str_replace(
            SliderService::SLIDER_REPLACE_VARIABLE,
            $requestVar,
            SliderService::SLIDER_URL_TEMPLATE
        );
    }
}

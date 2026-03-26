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


namespace Mirasvit\LayeredNavigation\Block\Renderer;


use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Theme\Block\Html\Pager;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\HighlightConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\SeoConfigProvider;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;

class SearchRenderer extends AbstractRenderer
{
    protected $_template = 'Mirasvit_LayeredNavigation::renderer/searchRenderer.phtml';

    private   $htmlPagerBlock;

    private   $highlightConfigProvider;

    private   $extraFilterConfigProvider;

    private   $seoFilterEnabled;

    public function __construct(
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        HighlightConfigProvider   $highlightConfigProvider,
        SeoConfigProvider         $seoConfigProvider,
        ConfigProvider            $configProvider,
        Template\Context          $context,
        ?Pager                    $htmlPagerBlock = null,
        array                     $data = []
    ) {
        $this->highlightConfigProvider   = $highlightConfigProvider;
        $this->htmlPagerBlock            = $htmlPagerBlock ?? ObjectManager::getInstance()->get(Pager::class);
        $this->extraFilterConfigProvider = $extraFilterConfigProvider;

        parent::__construct($seoConfigProvider, $configProvider, $context, $data);
    }

    public function getAppliedSearchTerms(): array
    {
        $applied = [];

        $searchTerms = $this->getRequest()->getParam(ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM);

        if (!$searchTerms) {
            return $applied;
        }

        $query       = $this->getRequest()->getParams();
        $searchTerms = explode(',', $searchTerms);

        if (isset($query['id'])) {
            unset($query['id']);
        }

        unset($query[ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM]);

        $query[$this->htmlPagerBlock->getPageVarName()] = null;

        foreach ($searchTerms as $term) {
            $newTerms = $searchTerms;
            $key      = array_search($term, $searchTerms);
            unset($newTerms[$key]);

            $query[ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM] = count($newTerms)
                ? implode(',', $newTerms)
                : null;

            if ($this->checkIfSeoFiltersEnabled()) {
                $url    = $this->_request->getPathInfo();
                $params = $query[ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM] ?
                    '?' . ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM . '=' . $query[ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM] : '';
                $url    = $url . $params;
            } else {
                $url = $this->_urlBuilder->getUrl(
                    '*/*/*',
                    [
                        '_current'     => true,
                        '_use_rewrite' => true,
                        '_query'       => $query,
                    ]
                );
            }

            $applied[] = [
                'label' => $term,
                'url'   => $url,
            ];
        }

        return $applied;
    }

    public function isHighlightEnabled(): bool
    {
        return $this->highlightConfigProvider->isEnabled($this->storeId);
    }

    public function getHighlightColor(): string
    {
        return $this->highlightConfigProvider->getColor($this->storeId);
    }

    public function isUseFulltext(): bool
    {
        return $this->extraFilterConfigProvider->isSearchFilterFulltext();
    }

    public function isFilterOptions(): bool
    {
        return $this->extraFilterConfigProvider->isSearchFilterOptions();
    }

    public function checkIfSeoFiltersEnabled(): bool
    {
        if ($this->seoFilterEnabled != null) {
            return $this->seoFilterEnabled;
        }

        if (!class_exists('Mirasvit\SeoFilter\Service\FriendlyUrlService')) {
            return false;
        }

        $this->seoFilterEnabled = ObjectManager::getInstance()->get('Mirasvit\SeoFilter\Model\ConfigProvider')->isEnabled();

        return $this->seoFilterEnabled;
    }
}

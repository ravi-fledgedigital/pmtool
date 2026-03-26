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

namespace Mirasvit\LayeredNavigation\Model\Layer\Filter;

use Exception;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Price;
use Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Mirasvit\LayeredNavigation\Service\PriceService;
use Mirasvit\LayeredNavigation\Service\SliderService;
use Mirasvit\SeoFilter\Service\RewriteService;

/**
 * @SuppressWarnings(PHPMD)
 */
class DecimalFilter extends AbstractFilter
{
    /** Price delta for filter  */
    public const PRICE_DELTA = 0.001;

    private const ATTRIBUTE_CODE_PRICE = 'price';

    /**
     * @var array
     */
    protected static $isStateAdded = [];

    /**
     * @var bool
     */
    protected static $isAdded;

    /**
     * @var Price
     */
    private $dataProvider;

    private $priceCurrency;

    private $sliderService;

    private $attributeConfigRepository;

    private $rewriteService;

    private $moduleManager;

    private $priceService;

    private $logger;

    /**
     * @var ?array
     */
    private $facetedData = null;

    /**
     * @var ?array
     */
    private $limitsWithTax = null;

    /**
     * @var string
     */
    private $attributeValue = '';

    public function __construct(
        PriceFactory              $dataProviderFactory,
        PriceCurrencyInterface    $priceCurrency,
        SliderService             $sliderService,
        AttributeConfigRepository $attributeConfigRepository,
        RewriteService            $rewriteService,
        Manager                   $moduleManager,
        PriceService              $priceService,
        LoggerInterface           $logger,
        Layer                     $layer,
        Context                   $context,
        array                     $data = []
    ) {
        parent::__construct($layer, $context, $data);

        $this->dataProvider              = $dataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->priceCurrency             = $priceCurrency;
        $this->sliderService             = $sliderService;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->rewriteService            = $rewriteService;
        $this->moduleManager             = $moduleManager;
        $this->priceService              = $priceService;
        $this->logger                    = $logger;
    }

    public function apply(RequestInterface $request): self
    {
        $attributeCode  = $this->getRequestVar();
        $attributeValue = $request->getParam($attributeCode);

        if (!$attributeValue) {
            if ($this->moduleManager->isEnabled('Mirasvit_SeoFilter')) {
                $rewrite        = $this->rewriteService->getAttributeRewrite($attributeCode);
                $attributeAlias = $rewrite ? $rewrite->getRewrite() : $attributeCode;
                $attributeValue = $request->getParam($attributeAlias);
            }

            if ($attributeCode === self::ATTRIBUTE_CODE_PRICE && $this->configProvider->isTaxIncluded()) {
                $this->prepareFacetedData();
            }
        }

        if (!$attributeValue || !is_string($attributeValue)) {
            return $this;
        }

        $this->attributeValue = $attributeValue;
        $this->getFacetedData();
        $limits = $this->getAttributeLimits();
        $this->setFromToData($limits);

        $productCollection = $this->getProductCollection();

        if (
            ($attributeCode === PriceService::KEY_PRICE)
            && $this->configProvider->isTaxIncluded()
            && !is_null($this->limitsWithTax)
        ) {
            $limits = $this->limitsWithTax;
        }

        $productCollection->addFieldToFilter($attributeCode, $limits);

        return $this;
    }

    private function getAttributeLimits(): array
    {
        $maxPrice     = PriceService::MAX_VALUE;
        $fromArray    = [];
        $toArray      = [];
        $filterParams = explode(',', $this->attributeValue);

        foreach ($filterParams as $filterParam) {
            $filterParamArray = preg_split('/[\-:]/', $filterParam);

            $idx = 0;
            while ($idx < count($filterParamArray)) {
                $from = isset($filterParamArray[$idx]) ? (float)$filterParamArray[$idx] : null;
                $to   = !empty($filterParamArray[$idx + 1]) ? (float)$filterParamArray[$idx + 1] : null;

                $fromArray[] = $from ? : 0;
                $toArray[]   = $to ? : $maxPrice;

                if (!empty($from) || !empty($to)) {
                    $this->addStateItem(
                        $this->_createItem(
                            $this->renderRangeLabel($from, $to),
                            implode('-', [$from, $to])
                        )
                    );
                }

                $idx += 2;
            }
        }

        $from = min($fromArray);
        $to   = max($toArray);

        $attributeCode   = $this->getRequestVar();
        $attributeConfig = $this->getAttributeConfig($attributeCode);

        $displayMode = $attributeConfig
            ? $attributeConfig->getDisplayMode()
            : AttributeConfigInterface::DISPLAY_MODE_RANGE;

        if ($displayMode === AttributeConfigInterface::DISPLAY_MODE_RANGE) {
            $to -= self::PRICE_DELTA;
        }

        return [
            PriceService::KEY_FROM => $from,
            PriceService::KEY_TO   => $to,
        ];
    }

    public function getFacetedData(): array
    {
        if (is_null($this->facetedData)) {
            $this->prepareFacetedData();
        }

        return $this->facetedData;
    }

    private function prepareFacetedData()
    {
        $attributeCode = $this->getAttributeModel()->getAttributeCode();

        if (($attributeCode !== PriceService::KEY_PRICE) || !$this->configProvider->isTaxIncluded()) {
            $productCollection = $this->getProductCollection();
            $facets            = $productCollection->getExtendedFacetedData($attributeCode, true);

            // slider compatibility with LiveSearch
            if ($this->isLivesearchSlider($attributeCode)) {
                try {
                    $clone = clone $productCollection;
                    $clone->renderFilters();
                    $facets['min'][PriceService::KEY_PRICE] = $clone->getMinPrice();
                    $facets['max'][PriceService::KEY_PRICE] = $clone->getMaxPrice();
                } catch (Exception $e) {
                }
            }
        } else { // prepare faceted data for price including tax
            $minPriceInclTax  = PriceService::MAX_VALUE;
            $minFilteredPrice = PriceService::MAX_VALUE;
            $maxPriceInclTax  = 0.0;
            $maxFilteredPrice = 0.0;
            $limits           = $this->getAttributeLimits();
            $prices           = [];

            $clone = clone $this->getProductCollection();

            foreach ($clone as $product) {
                $productPrices = $this->priceService->getProductPrices($product);

                if (
                    ($limits[PriceService::KEY_FROM] <= $productPrices[PriceService::KEY_PRICE_INCL_TAX])
                    && ($productPrices[PriceService::KEY_PRICE_INCL_TAX] <= $limits[PriceService::KEY_TO])
                ) {
                    $minFilteredPrice = min($minFilteredPrice, $productPrices[PriceService::KEY_PRICE]);
                    $maxFilteredPrice = max($maxFilteredPrice, $productPrices[PriceService::KEY_PRICE]);
                }

                $prices[]        = $productPrices;
                $minPriceInclTax = min($minPriceInclTax, $productPrices[PriceService::KEY_PRICE_INCL_TAX]);
                $maxPriceInclTax = max($maxPriceInclTax, $productPrices[PriceService::KEY_PRICE_INCL_TAX]);
            }

            $this->limitsWithTax = [
                PriceService::KEY_FROM => (float)$minFilteredPrice - self::PRICE_DELTA,
                PriceService::KEY_TO   => (float)$maxFilteredPrice + self::PRICE_DELTA,
            ];

            if (!count($prices)) {
                $this->facetedData = [];

                return;
            }

            try {
                $facets = $this->priceService->getFacetsForPrices($clone, $prices);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                $facets = [];
            }
        }

        $this->facetedData = $facets;
    }

    public function getSliderData(string $url): array
    {
        return $this->sliderService->getSliderData(
            $this->getFacetedData(),
            $this->getRequestVar(),
            (array)$this->getFromToData(),
            $url,
            $this->getAttributeConfig($this->_requestVar)->getSliderStep()
        );
    }

    public function getCurrencyRate(): float
    {
        $rate = $this->_getData('currency_rate');

        if ($rate === null) {
            $rate = $this->_storeManager->getStore($this->getStoreId())
                ->getCurrentCurrencyRate();
        }

        if (!$rate) {
            $rate = 1;
        }

        return (float)$rate;
    }

    protected function _getItemsData(): array
    {
        $isApplied = $this->configProvider->isPreCalculationEnabled()
            ? false
            : !empty($this->attributeValue);

        if ($isApplied && !$this->configProvider->isMultiselectEnabled($this->getRequestVar())) {
            return [];
        }

        $facets = $this->getFacetedData();

        $data = [];

        if (count($facets) >= 1) {
            foreach ($facets as $key => $aggregation) {
                if (!isset($aggregation[PriceService::KEY_COUNT])) {
                    continue;
                }
                $count = $aggregation[PriceService::KEY_COUNT];
                if (strpos($key, '_') === false) {
                    continue;
                }

                $data[] = $this->prepareData($key, (int)$count);
            }
        }

        return $data;
    }

    protected function prepareData(string $key, int $count): array
    {
        [$from, $to] = explode('_', $key);

        $from = $from == '*' ? $this->getFrom((float)$to) : (float)$from;
        $to   = $to == '*' || $to == '' ? null : (float)$to;

        $label = $this->renderRangeLabel(empty($from) ? 0 : $from, $to);

        $value = $from . '-' . $to . $this->dataProvider->getAdditionalRequestData();

        return [
            PriceService::KEY_LABEL => $label,
            PriceService::KEY_VALUE => $value,
            PriceService::KEY_COUNT => $count,
            PriceService::KEY_FROM  => $from,
            PriceService::KEY_TO    => $to,
        ];
    }

    private function renderRangeLabel(?float $fromPrice, ?float $toPrice): ?string
    {
        if (strpos($fromPrice . $toPrice, ',') !== false) {
            return null;
        }

        $attributeConfig = $this->getAttributeConfig($this->_requestVar);
        $displayMode     = $attributeConfig->getDisplayMode();
        $valueTemplate   = $attributeConfig->getValueTemplate();

        if ($this->_requestVar === self::ATTRIBUTE_CODE_PRICE) {
            $fromPrice = $fromPrice === null ? 0 : $fromPrice * $this->getCurrencyRate();
            $toPrice   = $toPrice === null ? '' : $toPrice * $this->getCurrencyRate();
        } else {
            $fromPrice = $fromPrice === null ? 0 : $fromPrice;
            $toPrice   = $toPrice === null ? '' : $toPrice;
        }

        if ($displayMode === AttributeConfigInterface::DISPLAY_MODE_RANGE && $toPrice !== '') {
            if ($fromPrice != $toPrice) {
                $toPrice -= .01;
            }
        }

        if ($this->_requestVar === self::ATTRIBUTE_CODE_PRICE) {
            $fromPrice = $fromPrice === null ? 0 : round($fromPrice, 2);
            $toPrice   = $toPrice === null || $toPrice === '' ? '' : round((float)$toPrice - 0.01, 2);

            $fromText = $this->priceCurrency->format($fromPrice, false);
            $toText   = $toPrice !== '' ? $this->priceCurrency->format($toPrice, false) : '';
        } else {
            $valueTemplate = $valueTemplate ? : '{value}';

            $fromText = str_replace('{value}', (string)(float)$fromPrice, $valueTemplate);
            $toText   = str_replace('{value}', (string)(float)$toPrice, $valueTemplate);
        }

        if ($toPrice === '') {
            return (string)__('%1 and above', $fromText);
        } elseif ($fromPrice == $toPrice && $this->dataProvider->getOnePriceIntervalValue()) {
            return $fromText;
        } else {
            return (string)__('%1 - %2', $fromText, $toText);
        }
    }


    private function getFrom(float $from): float
    {
        $to       = 0.0;
        $interval = $this->dataProvider->getInterval();
        if ($interval && is_numeric($interval[0]) && $interval[0] < $from) {
            $to = (float)$interval[0];
        }

        return $to;
    }

    public function getAttributeConfig(string $attributeCode): AttributeConfigInterface
    {
        $attributeConfig = $this->attributeConfigRepository->getByAttributeCode($attributeCode);

        return $attributeConfig ? : $this->attributeConfigRepository->create();
    }

    private function isLivesearchSlider($attrCode): bool
    {
        $attrConfig = $this->getAttributeConfig($attrCode);

        return in_array(
                $attrConfig->getDisplayMode(),
                [
                    AttributeConfigInterface::DISPLAY_MODE_SLIDER,
                    AttributeConfigInterface::DISPLAY_MODE_SLIDER_FROM_TO,
                ]
            )
            && $this->moduleManager->isEnabled('Magento_LiveSearch');
    }
}

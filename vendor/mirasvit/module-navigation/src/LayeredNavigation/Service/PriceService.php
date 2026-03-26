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

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Price;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Store\Model\ScopeInterface;
use Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\Collection;

class PriceService
{
    public const MAX_VALUE = 1000000;

    public const KEY_FROM  = 'from';
    public const KEY_TO    = 'to';
    public const KEY_COUNT = 'count';
    public const KEY_VALUE = 'value';
    public const KEY_LABEL = 'label';

    public const KEY_PRICE          = 'price';
    public const KEY_PRICE_INCL_TAX = 'price_incl_tax';

    private const AGGREGATIONS_PRICE_FIELD = 'price_index.min_price';

    private $scopeConfig;

    private $taxHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data                 $taxHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->taxHelper   = $taxHelper;
    }

    public function getProductPrices(Product $product): array
    {
        switch ($product->getTypeId()) {
            case ConfigurableType::TYPE_CODE:
                $childProducts = $product->getTypeInstance()->getUsedProducts($product);
                $finalPrices   = [];
                foreach ($childProducts as $child) {
                    $finalPrices[] = $child->getFinalPrice();
                }

                if (empty($finalPrices)) {
                    $finalPrices[] = $product->getFinalPrice();
                }
                
                $finalPrice = min($finalPrices);
                break;

            case BundleType::TYPE_CODE:
                $minimalPrice    = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice();
                $minimalPriceAdj = $minimalPrice->getAdjustmentAmounts();
                $tax             = 0;

                if ($minimalPriceAdj) {
                    $tax = $minimalPriceAdj['tax'] ?? 0;
                }

                $finalPrice = $minimalPrice->getValue() - $tax;
                break;

            case GroupedType::TYPE_CODE:
                $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
                $finalPrices        = [];
                foreach ($associatedProducts as $assocProduct) {
                    if ($assocProduct->isSaleable()) {
                        $finalPrices[] = $assocProduct->getFinalPrice();
                    }
                }

                if (empty($finalPrices)) {
                    $finalPrices[] = $product->getFinalPrice();
                }

                $finalPrice = min($finalPrices);
                break;

            default:
                $finalPrice = $product->getFinalPrice();
        }

        return [
            self::KEY_PRICE          => $product->getMinimalPrice(),
            self::KEY_PRICE_INCL_TAX => $this->taxHelper->getTaxPrice($product, $finalPrice, true),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getFacetsForPrices(Collection $productCollection, array $prices): array
    {
        if (!count($prices)) {
            return [];
        }

        $minCount     = 0;
        $maxCount     = 0;
        $pricesIncTax = array_column($prices, self::KEY_PRICE_INCL_TAX);
        $priceRange   = $this->getPriceRange($pricesIncTax);
        $maxPrice     = max($pricesIncTax);
        $minPrice     = min($pricesIncTax);

        for ($i = 0; $i <= $maxPrice; $i += $priceRange) {
            $to  = $i + $priceRange;
            $key = $i . '_' . $to;

            $aggregations[$key] = [
                self::KEY_FROM  => $i,
                self::KEY_TO    => $to,
                self::KEY_COUNT => 0,
                self::KEY_VALUE => $key,
            ];
        }

        $key = $to . '_';

        $aggregations[$key] = [
            self::KEY_FROM  => $to,
            self::KEY_TO    => null,
            self::KEY_COUNT => 0,
            self::KEY_VALUE => $key,
        ];


        foreach ($prices as $price) {
            if ($price === $minPrice) {
                $minCount++;
            }

            if ($price === $maxPrice) {
                $maxCount++;
            }
        }

        $productSelect = $productCollection->getSelect();

        foreach ($aggregations as $key => $aggregation) {
            $limits = $this->getLimitsByPriceInclTax(
                $prices,
                $aggregation[self::KEY_FROM],
                $aggregation[self::KEY_TO]
            );

            if (!$limits) {
                continue;
            }

            $select     = clone $productSelect;
            $connection = $productCollection->getConnection();
            $select->where(self::AGGREGATIONS_PRICE_FIELD . ' >= ?', $limits[self::KEY_FROM]);
            $select->where(self::AGGREGATIONS_PRICE_FIELD . ' <= ?', $limits[self::KEY_TO]);
            $aggregations[$key][self::KEY_COUNT] = count($connection->fetchAll($select));
        }

        foreach ($aggregations as $key => $aggregation) {
            if ($aggregation[self::KEY_COUNT] === 0) {
                unset($aggregations[$key]);
            }
        }

        $aggregations['min'] = [
            self::KEY_VALUE => 'min',
            self::KEY_PRICE => $minPrice,
            self::KEY_COUNT => $minCount,
        ];
        $aggregations['max'] = [
            self::KEY_VALUE => 'max',
            self::KEY_PRICE => $maxPrice,
            self::KEY_COUNT => $maxCount,
        ];

        return $aggregations;
    }

    private function getLimitsByPriceInclTax(array $prices, float $from, ?float $to): ?array
    {
        $to = $to ?? self::MAX_VALUE;

        foreach ($prices as $price) {
            if (($from <= $price[self::KEY_PRICE_INCL_TAX]) && ($price[self::KEY_PRICE_INCL_TAX] < $to)) {
                if (isset($minPrice)) {
                    $minPrice = min($minPrice, $price[self::KEY_PRICE]);
                    $maxPrice = max($maxPrice, $price[self::KEY_PRICE]);
                } else {
                    $minPrice = $price[self::KEY_PRICE];
                    $maxPrice = $price[self::KEY_PRICE];
                }
            }
        }

        if (!isset($minPrice)) {
            return null;
        }

        return [
            self::KEY_FROM => $minPrice,
            self::KEY_TO   => $maxPrice,
        ];
    }

    private function getPriceRange(array $prices): int
    {
        $calculation = $this->getRangeCalculationValue();
        if ($calculation == Price::RANGE_CALCULATION_AUTO) {
            $maxPrice = max($prices);
            $index    = 1;
            do {
                $range = pow(10, strlen((string)floor($maxPrice)) - $index);
                $items = $this->getRangeItemCounts($range, $prices);
                $index++;
            } while ($range > Price::MIN_RANGE_POWER && count($items) < 2);
        } else {
            $range = (int)$this->getRangeStepValue();
        }

        return $range;
    }

    private function getRangeItemCounts(int $range, array $prices): array
    {
        $items      = [];
        $rangeCount = ceil(max($prices) / $range);

        for ($i = 1; $i <= $rangeCount; $i++) {
            $items[$i] = 0;
        }

        foreach ($prices as $price) {
            for ($i = 1; $i <= $rangeCount; $i++) {
                if ($price < ($range * $i)) {
                    $items[$i]++;
                }
            }
        }

        foreach ($items as $key => $count) {
            if ($count === 0) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    private function getRangeCalculationValue()
    {
        return $this->scopeConfig->getValue(Price::XML_PATH_RANGE_CALCULATION, ScopeInterface::SCOPE_STORE);
    }

    private function getRangeStepValue()
    {
        return $this->scopeConfig->getValue(Price::XML_PATH_RANGE_STEP, ScopeInterface::SCOPE_STORE);
    }

}

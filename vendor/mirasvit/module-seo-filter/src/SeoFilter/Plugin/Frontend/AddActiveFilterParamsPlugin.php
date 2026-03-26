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
 * @package   mirasvit/module-seo-filter
 * @version   1.3.57
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\SeoFilter\Plugin\Frontend;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable;
use Mirasvit\SeoFilter\Service\FilterParamsService;

/**
 * Adds active filter params to swatch config for proper preselection with SEO-friendly URLs.
 *
 * @see \Magento\Swatches\Block\Product\Renderer\Listing\Configurable::getJsonConfig()
 */
class AddActiveFilterParamsPlugin
{
    private $jsonEncoder;

    private $jsonDecoder;

    private $filterParamsService;

    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        FilterParamsService $filterParamsService
    ) {
        $this->jsonEncoder         = $jsonEncoder;
        $this->jsonDecoder         = $jsonDecoder;
        $this->filterParamsService = $filterParamsService;
    }

    public function afterGetJsonConfig(Configurable $subject, string $result): string
    {
        $product = $subject->getProduct();

        if (!$product || $product->getTypeId() !== ConfigurableType::TYPE_CODE) {
            return $result;
        }

        $config = $this->jsonDecoder->decode($result);

        $activeParams = $this->getActiveFilterParamsForProduct($config);

        if (empty($activeParams)) {
            return $result;
        }

        $config['activeFilterParams'] = $activeParams;

        return $this->jsonEncoder->encode($config);
    }

    private function getActiveFilterParamsForProduct(array $config): array
    {
        $result = [];

        $productAttributeCodes = $this->getProductConfigurableAttributeCodes($config);

        if (empty($productAttributeCodes)) {
            return $result;
        }

        $params = $this->filterParamsService->getFilterParams();

        foreach ($params as $code => $value) {
            if (!in_array($code, $productAttributeCodes, true)) {
                continue;
            }

            $values = is_string($value) ? explode(',', $value) : [$value];

            foreach ($values as $optionValue) {
                $optionValue = trim((string)$optionValue);

                if (is_numeric($optionValue)) {
                    $result[$code] = $optionValue;
                    break;
                }
            }
        }

        return $result;
    }

    private function getProductConfigurableAttributeCodes(array $config): array
    {
        $codes = [];

        if (!isset($config['attributes']) || !is_array($config['attributes'])) {
            return $codes;
        }

        foreach ($config['attributes'] as $attribute) {
            if (isset($attribute['code'])) {
                $codes[] = $attribute['code'];
            }
        }

        return $codes;
    }
}

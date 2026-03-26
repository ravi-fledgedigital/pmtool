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

namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Swatches\Block\Product\Renderer;

use Magento\Swatches\Block\Product\Renderer\Configurable;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Magento\Swatches\Model\Swatch;
use Magento\Swatches\Helper\Media as SwatchHelper;

/**
 * @see \Magento\Swatches\Block\Product\Renderer\Configurable::getJsonSwatchConfig()
 */
class ExtendJsonSwatchConfig
{
    private $jsonEncoder;
    private $jsonDecoder;
    private $attributeConfigRepository;
    private $swatchHelper;

    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        AttributeConfigRepository $attributeConfigRepository,
        SwatchHelper $swatchHelper
    ) {
        $this->jsonEncoder               = $jsonEncoder;
        $this->jsonDecoder               = $jsonDecoder;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->swatchHelper              = $swatchHelper;
    }

    public function afterGetJsonSwatchConfig(Configurable $subject, string $result): string
    {
        $swatchData = $this->jsonDecoder->decode($result);

        foreach ($swatchData as $key => $swatch) {
            $attributeConfig = $this->attributeConfigRepository->getByAttributeId((int) $key);

            if ($attributeConfig) {
                $attributeConfig = $attributeConfig->getConfig();
                $options         = isset($attributeConfig['options']) ? $attributeConfig['options'] : [];

                foreach ($options as $option) {
                    $optionId = $option['option_id'];

                    if (!isset($swatchData[$key][$optionId])) {
                        continue;
                    }

                    if (isset($option['image_path']) && $option['image_path']) {
                        $option['value'] = $this->swatchHelper->getSwatchAttributeImage(Swatch::SWATCH_IMAGE_NAME, $option['image_path']);
                        $option['thumb'] = $this->swatchHelper->getSwatchAttributeImage(Swatch::SWATCH_THUMBNAIL_NAME, $option['image_path']);
                        $option['type']  = Swatch::SWATCH_TYPE_VISUAL_IMAGE;

                        $swatchData[$optionId]['value'] = $option['value'];
                        $swatchData[$optionId]['type']  = $option['type'];
                    }

                    $option = array_filter($option);

                    if (isset($swatchData[$key][$optionId])) {
                        $swatchData[$key][$optionId] = array_merge($swatchData[$key][$optionId], $option);
                    } else {
                        $swatchData[$key][$optionId] = $option;
                    }
                }
            }
        }

        return $this->jsonEncoder->encode($swatchData);
    }
}

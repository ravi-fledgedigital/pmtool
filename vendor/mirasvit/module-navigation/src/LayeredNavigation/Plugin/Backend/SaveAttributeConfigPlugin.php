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

namespace Mirasvit\LayeredNavigation\Plugin\Backend;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\AttributeConfig\OptionConfig;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;

/**
 * Plugin save our extended configuration from attribute edit page (tab Layered Navigation)
 * @see \Magento\Catalog\Model\ResourceModel\Eav\Attribute::save()
 * @SuppressWarnings(PHPMD)
 */
class SaveAttributeConfigPlugin
{
    private $attributeConfigRepository;

    public function __construct(
        AttributeConfigRepository $attributeConfigRepository
    ) {
        $this->attributeConfigRepository = $attributeConfigRepository;
    }

    /**
     * @param Attribute $subject
     * @param \Closure  $proceed
     *
     * @return Attribute
     */
    public function aroundSave($subject, \Closure $proceed)
    {
        $attributeCode = $subject->getAttributeCode();

        if (!$attributeCode) {
            return $proceed();
        }

        $attrConfig = $this->attributeConfigRepository->getByAttributeCode($attributeCode, false);

        if (!$attrConfig) {
            $attrConfig = $this->attributeConfigRepository->create();
        }

        $attrConfig->setAttributeId((int)$subject->getAttributeId())
            ->setAttributeCode((string)$attributeCode);

        $attrConfigData = $subject->getData('attribute_config');

        if (isset($attrConfigData[AttributeConfigInterface::OPTIONS_CONFIG])) {
            $optionsConfig = [];

            foreach ($attrConfigData[AttributeConfigInterface::OPTIONS_CONFIG] as $optionConfigData) {
                $optionId   = (int)$optionConfigData[OptionConfig::OPTION_ID];
                $label      = isset($optionConfigData[OptionConfig::LABEL])
                    ? (string)$optionConfigData[OptionConfig::LABEL]
                    : '';
                $fullImage  = isset($optionConfigData[OptionConfig::IS_FULL_IMAGE_WIDTH]);

                $optionConfig = new OptionConfig();

                $optionConfig->setOptionId($optionId)
                    ->setLabel($label)
                    ->setIsFullImageWidth($fullImage);

                try {
                    $imageData = SerializeService::decode($optionConfigData['image']['file']);

                    $optionConfig->setImagePath(isset($imageData[0]['file']) ? $imageData[0]['file'] : '');
                } catch (\Exception $e) {
                }

                if (isset($optionConfigData['image_path'])) {
                    $optionConfig->setImagePath($optionConfigData['image_path']);
                }

                $optionsConfig[] = $optionConfig;
            }

            $attrConfig->setOptionsConfig($optionsConfig);
        }

        if (isset($attrConfigData[AttributeConfigInterface::SEARCH_VISIBILITY_MODE])) {
            $attrConfig->setSearchVisibilityMode((string)$attrConfigData[AttributeConfigInterface::SEARCH_VISIBILITY_MODE]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::CATEGORY_VISIBILITY_MODE])) {
            $attrConfig->setCategoryVisibilityMode((string)$attrConfigData[AttributeConfigInterface::CATEGORY_VISIBILITY_MODE]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::CATEGORY_VISIBILITY_IDS])) {
            $categoryIds = $attrConfigData[AttributeConfigInterface::CATEGORY_VISIBILITY_IDS];
            if (is_array($categoryIds)) {
                $categoryIds = implode(',', $categoryIds);
            }
            $attrConfig->setConfigData(AttributeConfigInterface::CATEGORY_VISIBILITY_IDS, $categoryIds);
        }

        if (isset($attrConfigData[AttributeConfigInterface::OPTIONS_SORT_BY])) {
            $attrConfig->setOptionsSortBy((string)$attrConfigData[AttributeConfigInterface::OPTIONS_SORT_BY]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::ALPHABETICAL_INDEX])) {
            $attrConfig->setUseAlphabeticalIndex((bool)$attrConfigData[AttributeConfigInterface::ALPHABETICAL_INDEX]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::DISPLAY_MODE])) {
            $attrConfig->setDisplayMode((string)$attrConfigData[AttributeConfigInterface::DISPLAY_MODE]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::VALUE_TEMPLATE])) {
            $attrConfig->setValueTemplate((string)$attrConfigData[AttributeConfigInterface::VALUE_TEMPLATE]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::SLIDER_STEP])) {
            $attrConfig->setSliderStep((int)$attrConfigData[AttributeConfigInterface::SLIDER_STEP]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::IS_SHOW_SEARCH_BOX])) {
            $attrConfig->setIsShowSearchBox((bool)$attrConfigData[AttributeConfigInterface::IS_SHOW_SEARCH_BOX]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::ENABLE_MULTISELECT])) {
            $attrConfig->setIsMultiselectEnabled((int)$attrConfigData[AttributeConfigInterface::ENABLE_MULTISELECT]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::MULTISELECT_LOGIC])) {
            $attrConfig->setMultiselectLogic((int)$attrConfigData[AttributeConfigInterface::MULTISELECT_LOGIC]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::TOOLTIP])) {
            $attrConfig->setTooltip((string)$attrConfigData[AttributeConfigInterface::TOOLTIP]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::POSITION])) {
            $attrConfig->setPosition((string)$attrConfigData[AttributeConfigInterface::POSITION]);
        }

        if (isset($attrConfigData[AttributeConfigInterface::HORIZONTAL_POSITION])) {
            $attrConfig->setHorizontalPosition((int)$attrConfigData[AttributeConfigInterface::HORIZONTAL_POSITION]);
        }

        $this->attributeConfigRepository->save($attrConfig);

        return $proceed();
    }
}

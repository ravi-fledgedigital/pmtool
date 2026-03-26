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

namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Filter;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Magento\Framework\Exception\LocalizedException;

class SaveConfig extends Action
{
    private $resultJsonFactory;
    private $attributeConfigRepository;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        AttributeConfigRepository $attributeConfigRepository
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeConfigRepository = $attributeConfigRepository;
    }

    public function execute()
{
    $result = $this->resultJsonFactory->create();

    $postData = $this->getRequest()->getPostValue('attribute_config');
    $imageData = $this->getRequest()->getPostValue('attribute_images');

    if (!$postData || !isset($postData['attribute_code'])) {
        return $result->setData(['success' => false, 'message' => 'Invalid data']);
    }

    try {
        $config = $this->attributeConfigRepository->getByAttributeCode($postData['attribute_code']);

        if (!$config) {
            throw new LocalizedException(__('Attribute config not found.'));
        }

        foreach ($postData as $key => $value) {
            if ($key === 'category_visibility_ids' && is_array($value)) {
                $value = implode(',', $value);
            }
            $config->setConfigData($key, $value);
        }

        if (is_array($imageData)) {
            $optionsConfig = [];

            foreach ($imageData as $index => $image) {
                $optionConfig = new \Mirasvit\LayeredNavigation\Model\AttributeConfig\OptionConfig();

                $optionConfig
                    ->setOptionId($index)
                    ->setImagePath($image['url'] ?? '')
                    ->setLabel($image['label'] ?? '')
                    ->setPosition((int)($image['position'] ?? 0));

                $optionsConfig[] = $optionConfig;
            }

            $config->setOptionsConfig($optionsConfig);
        }

        $this->attributeConfigRepository->save($config);

        return $result->setData(['success' => true, 'attribute_code' => $postData['attribute_code']]);
    } catch (\Exception $e) {
        return $result->setData([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

}

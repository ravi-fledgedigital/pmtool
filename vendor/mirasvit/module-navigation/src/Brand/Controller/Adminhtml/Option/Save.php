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


namespace Mirasvit\Brand\Controller\Adminhtml\Option;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Brand\Model\Brand\PostData\Processor as PostDataProcessor;
use Mirasvit\Brand\Model\Config\Config;

class Save extends Action
{
    protected $config;

    protected $postDataProcessor;

    private $context;

    private $productAttributeRepository;

    public function __construct(
        Context $context,
        PostDataProcessor $postDataProcessor,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        Config $config
    ) {
        $this->context             = $context;
        $this->postDataProcessor   = $postDataProcessor;
        $this->config              = $config;

        $this->productAttributeRepository = $productAttributeRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $optionData    = $this->getRequest()->getParam('option');
        $attributeCode = $this->config->getGeneralConfig()->getBrandAttribute();
        $attribute     = $this->productAttributeRepository->get($attributeCode);
        $optionsCount  = count($attribute->getOptions());

        $newOptionData = [
            'order' => ['option_' . $optionsCount => $optionsCount],
            'value' => ['option_' . $optionsCount => $optionData]
        ];

        $options = $attribute->setData('option', $newOptionData);

        $attribute->save();

        $lastOptionParams = [];

        foreach ($attribute->getOptions() as $option) {
            if ($option->getLabel() == $optionData[0]) {
                $lastOptionParams = [
                    'label' => $option->getLabel(),
                    'value' => $option->getValue()
                ];

                break;
            }
        }

        $attribute->getOptions();

        return $this->returnResult($lastOptionParams);
    }

    private function returnResult(array $params = [], array $response = [])
    {
        if ($this->isAjax()) {
            $response['option']  = $params;
            $response['success'] = true;

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        }

        $this->messageManager->addSuccessMessage(
            'New Brand (' . $this->config->getGeneralConfig()->getBrandAttribute() . ') option was successfully saved'
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('brand/brand/index', $params);
    }

    private function isAjax(): bool
    {
        return (bool)$this->getRequest()->getParam('isAjax');
    }
}

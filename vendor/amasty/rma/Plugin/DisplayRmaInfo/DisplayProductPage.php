<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Plugin\DisplayRmaInfo;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Resolution\ProductResolutionProcessor;
use Magento\Store\Model\StoreManagerInterface;

class DisplayProductPage
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductResolutionProcessor
     */
    private $productResolutionProcessor;

    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        ProductResolutionProcessor $productResolutionProcessor
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->productResolutionProcessor = $productResolutionProcessor;
    }

    /**
     * @param \Magento\Catalog\Block\Product\View\Attributes $subject
     * @param array $data
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetAdditionalData($subject, $data)
    {
        $product = $subject->getProduct();

        if (!$this->configProvider->isEnabled()
            || !$this->configProvider->isShowRmaInfoProductPage($this->storeManager->getStore()->getId())
            || $subject->getData('display_attributes') == 'pagebuilder_only'
        ) {
            return $data;
        }

        $productResolutions = $this->productResolutionProcessor->getResolutions($product);
        foreach ($productResolutions as $productResolution) {
            $data[$productResolution['code']] = $productResolution;
        }

        return $data;
    }
}

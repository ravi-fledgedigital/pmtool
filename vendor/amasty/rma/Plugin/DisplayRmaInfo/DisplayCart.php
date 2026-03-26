<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Plugin\DisplayRmaInfo;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Resolution\ProductResolutionProcessor;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class DisplayCart
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
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $subject
     * @param array $result
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetOptionList($subject, $result)
    {
        if (!$this->configProvider->isEnabled()
            || !$this->configProvider->isShowRmaInfoCart($this->storeManager->getStore()->getId())
        ) {
            return $result;
        }

        $resolutions = $this->productResolutionProcessor->getResolutions($subject->getProduct());
        foreach ($resolutions as $resolution) {
            $result[] = $resolution;
        }

        return $result;
    }
}

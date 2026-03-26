<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Resolution;

use Amasty\Rma\Model\ReturnRules\ReturnRulesProcessor;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductResolutionProcessor
{
    /**
     * @var ReturnRulesProcessor
     */
    private $returnRulesProcessor;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        ReturnRulesProcessor $returnRulesProcessor,
        ProductRepositoryInterface $productRepository
    ) {
        $this->returnRulesProcessor = $returnRulesProcessor;
        $this->productRepository = $productRepository;
    }

    public function getResolutions(ProductInterface $product): array
    {
        /**
         * Reload product with attributes from repository for rule validation
         * @see ReturnRulesProcessor::getRuleToApply()
         */
        $validationProduct = $this->productRepository->getById($product->getId());
        $resolutions = $resultResolutions = [];
        if (!$product->isVirtual()) {
            $resolutions = $this->returnRulesProcessor->getResolutionsForProduct($validationProduct);
        }

        if ($resolutions) {
            foreach ($resolutions as $resolutionData) {
                $resolution = $resolutionData['resolution'];
                $resultResolutions[] = [
                    'code' => 'resolution_' . $resolution->getResolutionId(),
                    'label' => __('%1 period', $resolution->getLabel()),
                    'value' => __('%1 days', $resolutionData['value'])
                ];
            }
        } else {
            $resultResolutions[] = [
                'code'  => 'resolutions',
                'label' => __('Item Returns'),
                'value' => is_array($resolutions)
                    ? __('Sorry, the item can\'t be returned')
                    : __('This item can be returned')
            ];
        }

        return $resultResolutions;
    }
}

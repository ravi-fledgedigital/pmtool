<?php

namespace OnitsukaTiger\Relation\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Relation\Helper\Data as HelperRelation;
use OnitsukaTiger\Relation\Model\UpdateConfigurableProductRelation;

class UpdateProductRelation
{

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var HelperRelation
     */
    private HelperRelation $helperRelation;

    /**
     * @var UpdateConfigurableProductRelation
     */
    private UpdateConfigurableProductRelation $updateRelation;

    /**
     * @param HelperRelation $helperRelation
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UpdateConfigurableProductRelation $updateRelation
     */
    public function __construct(
        HelperRelation                    $helperRelation,
        ProductRepositoryInterface        $productRepository,
        SearchCriteriaBuilder             $searchCriteriaBuilder,
        UpdateConfigurableProductRelation $updateRelation,
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helperRelation = $helperRelation;
        $this->updateRelation = $updateRelation;
    }


    /**
     * Running Cron Update Product Relations
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if ($this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            $this->updateAllConfigurableProduct();
        }
    }

    /**
     * Update All Product Configurable
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function updateAllConfigurableProduct(): void
    {

        $searchCriteriaBuilder = $this->searchCriteriaBuilder->addFilter(
            'type_id',
            Configurable::TYPE_CODE
        );

        $configurableProducts = $this->productRepository->getList(
            $searchCriteriaBuilder->create()
        )->getItems();

        if (count($configurableProducts) === 0) {
            return;
        }
        foreach ($configurableProducts as $configurableProduct) {
            $this->updateRelation->updateConfigurableProduct($configurableProduct);
        }
    }
}

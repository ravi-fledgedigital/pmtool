<?php

declare(strict_types=1);

namespace OnitsukaTiger\Relation\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\Relation\Helper\Data as HelperRelation;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigurableProductRelation
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
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var HelperRelation
     */
    private HelperRelation $helperRelation;

    /**
     * @var ProgressBarFactory
     */
    private ProgressBarFactory $progressBarFactory;

    /**
     * @var ProductResource
     */
    private ProductResource $productResource;

    /**
     * @var ConfigurableModel
     */
    private ConfigurableModel $configurable;

    public function __construct(
        ConfigurableModel          $configurable,
        ProductResource            $productResource,
        HelperRelation             $helperRelation,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        ProgressBarFactory         $progressBarFactory,
        Logger                     $logger
    ) {
        $this->configurable = $configurable;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->helperRelation = $helperRelation;
        $this->progressBarFactory = $progressBarFactory;
        $this->productResource = $productResource;
    }

    /**
     * Update All Product
     *
     * @param OutputInterface $output
     * @return void
     */
    public function updateAllConfigurableProduct(OutputInterface $output): void
    {
        if (!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            return;
        }

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
        $output->writeln('<info>Process starts.</info>');

        /** @var ProgressBar $progress */
        $progressBar = $this->progressBarFactory->create(
            [
                'output' => $output,
                'max' => ceil(count($configurableProducts)),
            ]
        );

        $progressBar->setFormat(
            '%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );

        $progressBar->start();
        foreach ($configurableProducts as $configurableProduct) {
            $progressBar->advance();
            $this->updateConfigurableProduct($configurableProduct);
        }
        $progressBar->finish();
        $output->write(PHP_EOL);
        $output->writeln('<info>Process finished.</info>');
    }

    /**
     * Update Product By Sku
     *
     * @param string $sku
     * @param OutputInterface $output
     * @return void
     */
    public function updateConfigurableProductBySku(string $sku, OutputInterface $output): void
    {
        if (!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            return;
        }
        try {
            $product = clone $this->productRepository->get($sku, true);
            $output->writeln('<info>Process starts.</info>');

            /** @var ProgressBar $progress */
            $progressBar = $this->progressBarFactory->create(
                [
                    'output' => $output,
                    'max' => ceil(count($product->getWebsiteIds()) / 1000),
                ]
            );

            $progressBar->setFormat(
                '%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
            );

            $progressBar->start();
        } catch (NoSuchEntityException $e) {
            $this->logger->error("The product with the sku: " . $sku . " does not exist");
            return;
        }

        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $progressBar->advance();
            $this->updateConfigurableProduct($product);
        }
        $progressBar->finish();
        $output->write(PHP_EOL);
        $output->writeln('<info>Process finished.</info>');
    }

    /**
     * Update Product Attribute Data
     *
     * @param ProductInterface $product
     * @return void
     * @throws NoSuchEntityException
     */
    public function updateConfigurableProduct(ProductInterface $product): void
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/generating_product_relation.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("---- Generating product relation start --- ");
        $logger->info("SKU - " . $product->getSku());
        $logger->info("Att Code - " . $this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ATTRIBUTES));
        $logger->info("---- Generating product relation stop --- ");
        $this->logger->error("The product with the sku: " . $product->getSku() . " generate finished.");

        // echo "Sku -> $sku ---".'attr -> '.$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ATTRIBUTES).'<br/>';

        // if(!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ATTRIBUTES)){
        //     continue;
        // }

        $attributeRelation = $product->getData(
            $this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ATTRIBUTES)
        );
        if ($attributeRelation) {
            $productConfigurable = $this->helperRelation->getProductsConfigurable(
                $attributeRelation
            );
            $productConfigurable->setOrder('entity_id', 'ASC');
            $listColor = [];
            foreach ($productConfigurable as $productConfig) {
                /*if ($productConfig->getStatus() == 1) {*/
                $allChildrenIds = $this->configurable->getChildrenIds($productConfig->getEntityId());
                $allProductChildren = $this->helperRelation->getAllChildren($allChildrenIds);
                foreach ($allProductChildren as $productSimple) {
                    /*if ($productSimple->getStatus() == 1) {*/
                    if (array_key_exists($productSimple->getData('color'), $listColor)) {
                        continue;
                    }

                    $productImage = clone $this->productRepository->getById(
                        $productSimple->getId()
                    );
                    $color = $productSimple->getResource()->getAttribute('color')->getFrontend()->getValue($productSimple);
                    $imageMedia = explode('?qlt', $productImage->getImage())[0];
                    $imageMedia = $imageMedia . '?qlt=80&wid=105&hei=78&bgc=255,255,255&resMode=bisharp';
                    $listColor[$productSimple->getData('color')] = [
                                'swatches_color' => $color,
                                'swatches_attribute' => $attributeRelation,
                                'swatches_attribute_id' => $productSimple->getColor(),
                                'product_sku' => $productConfig->getSku(),
                                'swatches_image' => $imageMedia
                            ];
                    /*}*/
                }
                /*}*/
            }

            foreach ($productConfigurable as $configurableProduct) {
                $configurableProduct->setData('json_relation', json_encode($listColor));
                $this->productResource->saveAttribute($configurableProduct, 'json_relation');
            }
        }
    }
}

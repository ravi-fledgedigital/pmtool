<?php

namespace Vaimo\OTScene7AsicsIntegration\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Logger\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReAddWebsiteProduct
{
    /**
     * @var ProductCollection
     */
    private $collection;
    /**
     * @var Action
     */
    private $action;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductCollection $collection
     * @param Action $action
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductCollection                           $collection,
        Action                                      $action,
        StoreManagerInterface                       $storeManager,
        ProductRepositoryInterface                  $productRepository
    ) {

        $this->collection = $collection;
        $this->action = $action;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->reAddWebsiteProduct($input, $output);
    }

    /**
     * @param $sku
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addWebsiteProductBySku($sku)
    {
        $websites = $this->getWebsites();
        $product = $this->productRepository->get($sku, true);
        $checkStockDataWebsite = [];
        if ($websites) {
            foreach ($websites as $website) {
                $checkStockDataWebsite[] = $website->getId();
            }
        }
        $this->action->updateWebsites(
            [$product->getId()],
            $checkStockDataWebsite,
            'add'
        );
    }

    /**
     * @return void
     */
    public function reAddWebsiteProduct($input, $output)
    {
        $listSkus = [];
        $showLog =  $input->getOption('log') ?? '';
        $collection = $this->getProductCollection();
        foreach ($collection as $product) {
            if (count($product->getWebsiteIds()) == 0) {
                if ($showLog == '') {
                    $this->addWebsiteProductBySku($product->getSku());
                }
                $listSkus[] = $product->getSku();
            }
        }

        if (count($listSkus) > 0) {
            $output->writeln('=====================> Lists all SKUs added all website back to product when have image: <=========================');
            $output->writeln(implode(PHP_EOL, $listSkus));
        } else {
            $output->writeln('=====================>  No Product has been updated website  <=========================');
        }
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection()
    {
        $collectionFactory = $this->collection->create();
        $collectionFactory->addAttributeToSelect("scene7_available_image_angles")->addAttributeToFilter([
            ['attribute' => 'scene7_available_image_angles','neq' => '']
        ]);
        return $collectionFactory;
    }


    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    private function getWebsites()
    {
        return $this->storeManager->getWebsites();
    }
}

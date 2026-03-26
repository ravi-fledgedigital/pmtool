<?php

namespace OnitsukaTiger\ProductFeed\Model;

use OnitsukaTiger\ProductFeed\Console\Command\AddGoogleImageShop;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Vaimo\OTScene7Integration\Model\Scene7ImageAssetProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class ProductFeed
{
    const DEFAULT_STORE_ID = '0';

    /**
     * @var ProductCollection
     */
    public $collection;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var Scene7ImageAssetProvider
     */
    public $scene7ImageAssetProvider;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @param ProductCollection $collection
     * @param Scene7ImageAssetProvider $scene7ImageAssetProvider
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        ProductCollection $collection,
        Scene7ImageAssetProvider $scene7ImageAssetProvider,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager = null
    ) {
        $this->collection               = $collection;
        $this->scene7ImageAssetProvider = $scene7ImageAssetProvider;
        $this->productRepository        = $productRepository;
        $this->scopeConfig              = $scopeConfig;
        $this->logger                   = $logger;
        $this->storeManager             = $storeManager ?: ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption(AddGoogleImageShop::STORE_ID) ? $input->getOption(AddGoogleImageShop::STORE_ID) : self::DEFAULT_STORE_ID;
        $store   = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store->getCode());

        $listSku      = $this->getSkuOption($input);
        $listProducts = $this->getProductCollection($listSku);
        if ($listProducts->getSize() == 0) {
            $output->writeln("No Product has been updated.");
        }

        $output->writeln("<info>=====================> Start update <=========================</info>");
        $n = 0;
        foreach ($listProducts as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $imageScene7 = strtok($this->scene7ImageAssetProvider->getAsset($product, 'image')->getUrl(), '?');
                if ($imageScene7) {
                    $product->setData('google_shop_image_link', $imageScene7);
                    $this->productRepository->save($product);
                    $n++;
                }
            } catch (\Exception $exception) {
                $this->logger->error(__('Update Image Google for %1 error: %2', $item->getSku(), $exception->getMessage()));
                continue;
            }
        }

        $output->writeln("<info>Successfully updated " . $n . " out of " . $listProducts->getSize() . " products</info>");
        $output->writeln("<info>=====================> End update <=========================</info>");
    }

    /**
     * @param $input
     * @return array
     */
    public function getSkuOption($input)
    {
        $skuString = $input->getOption(AddGoogleImageShop::SKU);
        if (!$skuString || !isset($skuString[0])) {
            return [];
        }

        $listSku = explode("\n", $skuString[0]);
        return array_unique($listSku);
    }

    /**
     * @param $skus
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection($skus)
    {
        $collectionFactory = $this->collection->create();
        $collectionFactory->addAttributeToSelect("scene7_available_image_angles")->addAttributeToFilter([
            ['attribute' => 'scene7_available_image_angles', 'neq' => '']
        ]);
        if (count($skus) > 0) {
            $collectionFactory->addFieldToFilter('sku', ['in' => $skus]);
        }

        return $collectionFactory;
    }
}

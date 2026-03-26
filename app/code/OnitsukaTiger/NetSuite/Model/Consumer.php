<?php

namespace OnitsukaTiger\NetSuite\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Netsuite\Api\Data\PriceUpdateItemInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Tax\Model\ResourceModel\Calculation\Rate\CollectionFactory;

class Consumer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    protected $scopeConfig;

    protected $rateCollection;

    /**
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $rateCollection
     */
    public function __construct(
        LoggerInterface            $logger,
        StoreManagerInterface      $storeManager,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface       $scopeConfig,
        CollectionFactory          $rateCollection
    )
    {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->rateCollection = $rateCollection;
    }

    /**
     * Process price updates from the queue.
     *
     * @param PriceUpdateItemInterface[] $items
     */
    public function process($items)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/price_update_message_queue.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("==========Start Price Update Queue==========");
        $logger->info("Items Array" . print_r($items, true));
        if ($items) {
            $rates = [];
            foreach ($items as $item) {

                try {
                    if (!isset($item['sku'], $item['websitecode'], $item['price'])) {
                        $logger->info("Missing data for item: " . print_r($item, true));
                        continue;
                    }
                    if (isset($rates[$item['websitecode']])) {
                        $rate = $rates[$item['websitecode']]['rate'];
                        $storeId = $rates[$item['websitecode']]['store_id'];
                    } else {
                        $data = $this->getRate($item['websitecode']);
                        $rates[$item['websitecode']] = $data;
                        $rate = $data['rate'];
                        $storeId = $data['store_id'];
                    }
                    $percentageRate = ($item['price'] * $rate) / 100;
                    $price = $this->applyCountryPriceRule((float)$item['price'] + $percentageRate, $item['websitecode']);

                    $product = $this->productRepository->get($item['sku'], false, $storeId);
                    $product->setPrice($price);
                    $product->setStoreId($storeId);
                    $this->productRepository->save($product);
                    $logger->info("Updated SKU: " . $item['sku']);
                } catch (\Exception $e) {
                    $logger->info("Error SKU: " . $item['sku']);
                    $logger->info("Error Message: " . $e->getMessage());
                }
            }
        }
        $logger->info("==========End Price Update Queue==========");
    }

    /**
     * @param float $price
     * @param string $countryCode
     * @return float
     */
    private function applyCountryPriceRule(float $price, string $countryCode): float
    {
        $decimal = $price - floor($price);

        switch ($countryCode) {
            case 'web_sg':
            case 'web_th':
            case 'web_vn':
                return ($decimal >= 0.5) ? ceil($price) : floor($price);
            case 'web_my':
                return round($price, 2);
            default:
                return $price;
        }
    }

    /**
     * @param $websiteCode
     * @param $itemPrice
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRate($websiteCode)
    {
        $rate = (float)0.0000;
        $website = $this->storeManager->getWebsite($websiteCode);
        $storeId = $website->getDefaultStore()->getId();
        $countryCode = $this->scopeConfig->getValue(
            'general/country/default',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $collection = $this->rateCollection->create()->joinCountryTable();
        foreach ($collection as $countryRate) {
            if ($countryRate->getCountryName() == $countryCode) {
                $rate = $countryRate->getRate();
                break;
            }
        }
        return [
            'store_id' => $storeId,
            'rate' => $rate
        ];
    }
}
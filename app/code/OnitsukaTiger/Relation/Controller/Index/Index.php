<?php

declare(strict_types=1);

namespace OnitsukaTiger\Relation\Controller\Index;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;

class Index implements HttpGetActionInterface
{
    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Scene7ImageAssetProviderInterface
     */
    private Scene7ImageAssetProviderInterface $assetProvider;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;
    /**
     * @var Data
     */
    protected $pricingHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param RequestInterface $request
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param Scene7ImageAssetProviderInterface $assetProvider
     * @param ProductRepository $productRepository
     * @param Data $pricingHelper
     */
    public function __construct(
        RequestInterface                                 $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        Scene7ImageAssetProviderInterface                $assetProvider,
        ProductRepository                                $productRepository,
        Data                                             $pricingHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->assetProvider = $assetProvider;
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->pricingHelper = $pricingHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $sku = $this->request->getParam('sku');
        try {
            $product = $this->productRepository->get($sku);
            $urlParentProduct = $product->getUrlModel()->getUrl($product);
            $baseSecondImageUrl = $this->assetProvider->getAsset($product, 'category_page_grid_base_second')->getUrl();
            if (strpos($baseSecondImageUrl, 'images.asics.com') === false) {
                $baseSecondImageUrl = $this->assetProvider->getAsset($product, 'product_page_image_medium')->getUrl();
            }

            $data = [
                'url_product' => $urlParentProduct,
                'product_options' => $this->request->getParam('product_options'),
                'old_price' => $product->getPriceInfo()->getPrice('regular_price')->getValue(),
                'final_price' => $product->getPriceInfo()->getPrice('final_price')->getValue(),
                'base_image' => $this->assetProvider->getAsset(
                    $product,
                    'product_page_image_medium'
                )->getUrl(),
                'base_image_second' => $this->assetProvider->getAsset(
                    $product,
                    'category_page_grid_custom_second'
                )->getUrl(),
                'base_mouse_over' => $baseSecondImageUrl,
                'old_price_with_currency' => $this->pricingHelper->currency($product->getPriceInfo()
                    ->getPrice('regular_price')->getValue(), true, false),
                'final_price_with_currency' => $this->pricingHelper->currency($product->getPriceInfo()
                    ->getPrice('final_price')->getValue(), true, false)
            ];
            $currentTime = date('Y-m-d H:i:s');
            $launchDate = $product->getLaunchDate();
            if (!empty($launchDate) && strtotime($launchDate) > strtotime($currentTime)) {
                $data['coming_soon'] = '1';
                $data['coming_soon_label'] = $this->getConfig('coming_soon/general/coming_soon_label');

            }
            $result = $this->jsonResultFactory->create();
            $result->setData($data);
            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error("The product with the sku: " . $sku . " does not exist");
            return;
        }
    }
    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
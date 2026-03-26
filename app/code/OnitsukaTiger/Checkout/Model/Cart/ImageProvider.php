<?php
namespace OnitsukaTiger\Checkout\Model\Cart;

use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Checkout\CustomerData\ItemPoolInterface;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
/**
 * @api
 * @since 100.0.2
 */
class ImageProvider extends \Magento\Checkout\Model\Cart\ImageProvider
{
    private Scene7ImageAssetProviderInterface $assetProvider;
    private ProductRepositoryInterface $productRepository;

    protected $imageHelper;

    protected $itemResolver;

    /**
     * @param CartItemRepositoryInterface $itemRepository
     * @param ItemPoolInterface $itemPool
     * @param Scene7ImageAssetProviderInterface $assetProvider
     * @param ProductRepositoryInterface $productRepository
     * @param DefaultItem|null $customerDataItem
     * @param Image|null $imageHelper
     * @param ItemResolverInterface|null $itemResolver
     */
    public function __construct(
        CartItemRepositoryInterface $itemRepository,
        ItemPoolInterface $itemPool,
        Scene7ImageAssetProviderInterface $assetProvider,
        ProductRepositoryInterface $productRepository,
        DefaultItem $customerDataItem = null,
        Image $imageHelper = null,
        ItemResolverInterface $itemResolver = null
    ) {
        parent::__construct(
            $itemRepository,
            $itemPool,
            $customerDataItem,
            $imageHelper,
            $itemResolver
        );
        $this->itemRepository = $itemRepository;
        $this->itemPool = $itemPool;
        $this->assetProvider = $assetProvider;
        $this->productRepository = $productRepository;
        $this->customerDataItem = $customerDataItem;
        $this->customerDataItem = $customerDataItem ?: ObjectManager::getInstance()->get(DefaultItem::class);
        $this->imageHelper = $imageHelper ?: ObjectManager::getInstance()->get(\Magento\Catalog\Helper\Image::class);
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(
            \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($cartId)
    {
        $itemData = [];

        /** @see code/Magento/Catalog/Helper/Product.php */
        $items = $this->itemRepository->getList($cartId);
        /** @var \Magento\Quote\Model\Quote\Item $cartItem */
        foreach ($items as $cartItem) {
            $itemData[$cartItem->getItemId()] = $this->getProductImageData($cartItem);
        }
        return $itemData;
    }

    /**
     * Get product image data
     *
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     *
     * @return array
     */
    private function getProductImageData($cartItem)
    {
        $imageHelper = $this->imageHelper->init(
            $this->itemResolver->getFinalProduct($cartItem),
            'mini_cart_product_thumbnail'
        );
        $product = $this->productRepository->get($cartItem->getSku());
        $asset = $this->assetProvider->getAsset($product, 'mini_cart_product_thumbnail');
        $imageUrl = $asset->getUrl()
            ?? $imageHelper->getUrl();
        $imageData = [
            'src' => $imageUrl,
            'alt' => $imageHelper->getLabel(),
            'width' => $imageHelper->getWidth(),
            'height' => $imageHelper->getHeight(),
        ];
        return $imageData;
    }
}

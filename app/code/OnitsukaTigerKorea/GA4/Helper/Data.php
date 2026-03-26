<?php

namespace OnitsukaTigerKorea\GA4\Helper;


use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STORE_VIEW_KR = 'web_kr_ko';

    /**
     * @var ImageBuilder
     */
    protected ImageBuilder $imageBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ImageBuilder $imageBuilder
     * @param \Magento\Framework\View\Element\Template\Context $contextHtml
     */
    public function __construct(
        Context $context,
        ImageBuilder $imageBuilder,
        \Magento\Framework\View\Element\Template\Context $contextHtml
    )
    {
        $this->imageBuilder = $imageBuilder;
        $this->storeManager = $contextHtml->getStoreManager();
        parent::__construct($context);
    }

    /**
     * @param $product
     * @param $imageId
     * @param array $attributes
     * @return Image
     */
    public function getImage($product, $imageId, array $attributes = []): Image
    {
        return $this->imageBuilder->create($product, $imageId, $attributes);
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getCurrentStore(): StoreInterface
    {
        return $this->storeManager->getStore();
    }

    /**
     * @param $price
     * @return string
     */
    public static function formatPrice($price): string
    {
        return number_format($price, 2, '.', '');
    }

    /**
     * @throws NoSuchEntityException
     */
    public function isKoreaWebsite(): bool
    {
        return $this->getCurrentStore()->getCode() == self::STORE_VIEW_KR;
    }
}

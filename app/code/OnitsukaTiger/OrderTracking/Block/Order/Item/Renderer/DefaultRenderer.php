<?php
namespace OnitsukaTiger\OrderTracking\Block\Order\Item\Renderer;

use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer as DefaultRendererParent;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class DefaultRenderer extends DefaultRendererParent {
    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var \Magento\Catalog\Model\Product\OptionFactory
     */
    protected $_productOptionFactory;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $image;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $imageUrl;

    /**
     * DefaultRenderer constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Helper\Image $image,
        \Magento\Catalog\Helper\Product $imageUrl,
        array $data = []
    )
    {
        $this->image = $image;
        $this->imageUrl = $imageUrl;
        parent::__construct($context, $string, $productOptionFactory, $data);
    }

    /**
     * @param $item
     * @return bool|string
     */
    public function getImageUrl($item){
        return $this->image->init($item,'cart_page_product_thumbnail') ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)->setImageFile($item->getImage())->getUrl();
    }

    /**
     * @param $item
     * @return bool|string
     */
    public function getUrlImage($item) {
        return $this->imageUrl->getImageUrl($item);
    }

    /**
     * @return float|string|null
     */
    public function getStatus(){
        return $this->getOrder()->getStatus();
    }

    public function getLogistic(){
        return $this->getOrder()->getShippingMethod();
    }
}

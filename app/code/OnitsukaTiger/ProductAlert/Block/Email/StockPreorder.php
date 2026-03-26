<?php
namespace OnitsukaTiger\ProductAlert\Block\Email;

use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class StockPreorder extends \Magento\ProductAlert\Block\Email\AbstractEmail
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Filter\Input\MaliciousCode $maliciousCode,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        ScopeConfigInterface $scopeConfig,
        ProductFactory $product,
        Configurable $configurable
    ) {
        parent::__construct($context, $maliciousCode, $priceCurrency, $imageBuilder);
        $this->scopeConfig = $scopeConfig;
        $this->_product = $product;
        $this->_configurable = $configurable;
    }

    /**
     * @var string
     */
    protected $_template = 'OnitsukaTiger_ProductAlert::email/stockpreorder.phtml';

    /**
     * Retrieve unsubscribe url for product
     *
     * @param int $productId
     * @return string
     */
    public function getProductUnsubscribeUrl($productId)
    {
        $params = $this->_getUrlParams();
        $params['product'] = $productId;
        return $this->getUrl('productalert/unsubscribe/email', $params);
    }

    /**
     * Retrieve unsubscribe url for all products
     *
     * @return string
     */
    public function getUnsubscribeUrl()
    {
        return $this->getUrl('productalert/unsubscribe/stockAll', $this->_getUrlParams());
    }

    /*public function getParentUrl($productId)
    {
        $url = $this->getLayout()->createBlock('OnitsukaTiger\ProductAlert\Block\Product\View')->getParentProductUrl($productId);
        return $url;
    }*/

    public function getParentProductUrl($productId)
    {
        $parentId = $this->_configurable->getParentIdsByChild($productId);
        if (isset($parentId[0])) {
            $parentId = $parentId[0];
            $parentProduct = $this->_product->create()->load($parentId);

            return ($parentProduct->getStatus() == 1) ? $parentProduct->getProductUrl() : '#';
        }
        return '#';
    }

    public function getReservationPeriod()
    {
        $reservationFrom = $this->scopeConfig->getValue('onitsuka_pre_order_feature/pre_order_feature_reservation_period/settlement_from', ScopeInterface::SCOPE_STORE);
        $reservationTo = $this->scopeConfig->getValue('onitsuka_pre_order_feature/pre_order_feature_reservation_period/settlement_to', ScopeInterface::SCOPE_STORE);

        return [
            "reservation_from" => $reservationFrom,
            "reservation_to" => $reservationTo
        ];
    }
}

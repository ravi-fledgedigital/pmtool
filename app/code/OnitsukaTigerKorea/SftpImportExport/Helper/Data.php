<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const SFTP_KOREA_CONFIG = 'sftp_korea/';
    const ALLOWED_RMA_STATUS_CONFIG = 'sftp_korea/sftp_export_sales_data/rma_status';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

   public function __construct(Context $context, ProductRepositoryInterface $productRepository)
   {
       $this->productRepository = $productRepository;
       parent::__construct($context);
   }

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param $code
     * @param null $storeId
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {

        return $this->getConfigValue(self::SFTP_KOREA_CONFIG .'sftp_korea_config/'. $code, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function enableOrderSyncWithMultiShipmentSFTP($storeId = null) {
        return $this->getConfigValue(self::SFTP_KOREA_CONFIG .'order_sync_multishipment/enable', $storeId);
    }

    public function getSkuWmsBySku($sku)
    {
        $product = $this->productRepository->get($sku);
        return $product->getSkuWms();
    }

    public function formatSkuWms($skuWms)
    {
        if (!$skuWms) {
            return false;
        }

        $leftStr = substr($skuWms, 0, 9);
        $rightStr = substr($skuWms, -3);
        $midStr = substr($skuWms, 9, -3);

        if (strlen($skuWms) < 12) {
            return $skuWms;
        }

        if (strlen($skuWms) == 12) {
            return $leftStr . ".". $rightStr;
        }

        if (strlen($skuWms) > 12) {
            return $leftStr . "." . $midStr. "." . $rightStr;
        }
    }
    public function formatOrderUserName($orderUserName)
    {
        $orderUserName = preg_replace('/[^A-Z a-z\x{3131}-\x{D79D}\-]/u', '', $orderUserName);
        return mb_substr($orderUserName, 0, 32);
    }

    public function getAllowedRmaStatusExportData()
    {
        return $this->scopeConfig->getValue(self::ALLOWED_RMA_STATUS_CONFIG);
    }
}



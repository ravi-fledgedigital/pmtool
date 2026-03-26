<?php
namespace OnitsukaTiger\Checkout\Controller\Override;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use OnitsukaTiger\Fixture\Helper\Data;

class Index extends \Magento\Checkout\Controller\Index\Index
{
    private ProductInterfaceFactory $productFactory;

    private Data $helperData;

    /**
     * @var OnitsukaTiger\PreOrders\Helper\PreOrder
     */
    protected $preOrderHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        Data            $helperData,
        ProductInterfaceFactory    $productFactory,
        \OnitsukaTiger\PreOrders\Helper\PreOrder $preOrderHelper
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );
        $this->productFactory = $productFactory;
        $this->helperData = $helperData;
        $this->preOrderHelper = $preOrderHelper;
    }
    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->helperData->getConfig('catalog/them_customize/qty_validate_sales')) {
            $quote = $this->getOnepage()->getQuote();
            $result = $this->validateQuote($quote);
            if ($result) {
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }
        }
        return parent::execute();
    }
    public function validateQuote($quote)
    {

        $isValid = false;
        $isPreOrderCount = 0;
        $isNonPreOrderCount = 0;

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/checkout_init.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        foreach ($quote->getAllItems() as $item) {
            $qtyQuoteItems[$item->getItemId()] = $item->getQty();
            if ($item->getParentItemId() != null) {
                $product = $this->productFactory->create()->load($item->getProductId());
                // check pre order cart items, is pre order
                $isPreOrder = $this->preOrderHelper->isProductPreOrder($product->getId());
                if (!$isPreOrder) {
                    $isNonPreOrderCount++;
                }
                if ($isPreOrder) {
                    $isPreOrderCount++;
                }
                if ($product->getMaxSaleQty()) {
                    $maxQtySales = (int)$product->getMaxSaleQty();
                    $qty = $maxQtySales - (int)$qtyQuoteItems[$item->getParentItemId()];
                    if ($qty < 0) {
                        $isValid = true;
                        break;
                    }
                }
            }
        }
        $logger->info("isNonPreOrderCount -" . $isNonPreOrderCount);
        $logger->info("isPreOrderCount -" . $isPreOrderCount);

        if ($isNonPreOrderCount > 0 && $isPreOrderCount > 0) {
            $this->messageManager->addErrorMessage(__('Please note: Mix of regular and pre-order items is not allowed.'));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        return $isValid;
    }
}

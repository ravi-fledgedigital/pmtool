<?php
/** phpcs:ignoreFile */
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\ProductAlert\Model\Stock;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Restock\Model\GridRestockFactory;

class Index extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    protected $scopeConfig;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Stock
     */
    protected $_stockFactory;

    /**
     * @var GridRestockFactory
     */
    protected $_gridRestockFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Stock $stockFactory
     * @param GridRestockFactory $gridRestockFactory
     * @param ManagerInterface $messageManager
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param UrlInterface $urlInterface
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Stock $stockFactory,
        GridRestockFactory $gridRestockFactory,
        ManagerInterface $messageManager,
        Session $customerSession,
        PageFactory $resultPageFactory,
        UrlInterface $urlInterface,
        StoreManagerInterface $storeManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_stockFactory = $stockFactory;
        $this->_gridRestockFactory=$gridRestockFactory;
        $this->_customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->urlInterface = $urlInterface;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Execution method
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->_customerSession->isLoggedIn()) {
            $url = $this->_storeManager->getStore()->getBaseUrl() . 'restock/customer/';
            $login_url = $this->urlInterface
                ->getUrl(
                    'customer/account/login',
                    ['referer' => base64_encode($url), "actionfullname" => base64_encode($this->getRequest()->getFullActionName())]
                );
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($login_url);
            return $resultRedirect;
        }
        $productId = $this->getRequest()->getParam('product_id');

        if ($productId) {
            $restockCollection = $this->checkRestockProduct($productId);

            if ($restockCollection->getSize() > 0) {
                $this->cancelStockNotification($productId);
                $message = __('This product has been unsubscribed.');
                $this->messageManager->addSuccessMessage($message);
            } else {
                $message = __('Something went to wrong requested product is not available for your notifications.');
                $this->messageManager->addErrorMessage($message);
            }
            return $this->_redirect('restock/customer/');
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__("Onitsuka Tiger Official Online Store | Restock Request List"));
        $resultPage->getLayout()->getBlock('page.main.title')->setPageTitle(__("My Notifications"));
        return $resultPage;
    }

    /**
     * Check Restock Product
     *
     * @param $productId
     * @return AbstractDb|AbstractCollection|void|null
     */
    public function checkRestockProduct($productId)
    {
        try {
            $productCol = $this->_stockFactory->getCollection()
                ->addFieldToFilter('customer_id', $this->_customerSession->getCustomerId())
                ->addFieldToFilter('status', 0)
                ->addFieldToFilter('product_id', $productId);
            return $productCol;
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }

    /**
     * Cancel Stock Notification
     *
     * @param $productId
     * @return ResponseInterface
     */
    public function cancelStockNotification($productId)
    {
        try {
            $productCol = $this->_stockFactory->getCollection()
                ->addFieldToFilter('customer_id', $this->_customerSession->getCustomerId())
                ->addFieldToFilter('status', 0)
                ->addFieldToFilter('product_id', $productId);
            $productGridCol = $this->_gridRestockFactory->create()->getCollection();
            $productGridCol->addFieldToFilter('customer_id', $this->_customerSession->getCustomerId())
                ->addFieldToFilter('status', 0)
                ->addFieldToFilter('product_id', $productId);

            foreach ($productCol as $product) {
                $product->delete();
                break;
            }

            foreach ($productGridCol as $productGrid) {
                $productGrid->delete();
                break;
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $this->_redirect('restock/customer/');
    }
}

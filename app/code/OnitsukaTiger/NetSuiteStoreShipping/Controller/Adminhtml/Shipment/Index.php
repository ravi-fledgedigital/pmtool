<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

/**
 * Class Index
 */
class Index extends Action implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param StoreShipping $storeShipping
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreShipping $storeShipping
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->storeShipping = $storeShipping;
    }


    /**
     * Determine if action is allowed for shipping shop module
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $sourceCode = $this->getRequest()->getParam('source_code');
        $resource = 'OnitsukaTiger_NetSuiteStoreShipping::' . $sourceCode;
        return $this->_authorization->isAllowed($resource);
    }

    /**
     * Load the page defined in view/adminhtml/layout/exampleadminnewpage_helloworld_index.xml
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OnitsukaTiger_NetSuiteStoreShipping::manage');
        $sourceInfo = $this->storeShipping->getSourcesDetails($this->getRequest()->getParam('source_code'));
        $resultPage->getConfig()->getTitle()->prepend(__('Shipment [%1]', $sourceInfo ? $sourceInfo->getName() : ''));

        return $resultPage;

    }
}

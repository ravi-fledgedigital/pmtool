<?php
namespace OnitsukaTiger\ClearFilter\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Ui\Model\BookmarkFactory;

class Clearall extends Action
{
    protected $adminSession;
    protected $bookmarkFactory;
    protected $resultRedirectFactory;

    public function __construct(
        Context $context,
        AdminSession $adminSession,
        BookmarkFactory $bookmarkFactory,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->adminSession          = $adminSession;
        $this->bookmarkFactory       = $bookmarkFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    public function execute()
    {
        $adminUser = $this->adminSession->getUser();
        $userId    = $adminUser->getId();
        $gridNamespace = $this->getRequest()->getParam('namespace', 'sales_order_invoice_grid');

        try {
            $bookmarkCollection = $this->bookmarkFactory->create()->getCollection()
                ->addFieldToFilter('namespace', $gridNamespace)
                ->addFieldToFilter('user_id', $userId);
            foreach ($bookmarkCollection as $bookmark) {
                $bookmark->delete();
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __("Something went wrong while clearing filters: %1", $e->getMessage())
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/invoice/index');
    }
}

<?php

namespace OnitsukaTigerCpss\PaymentList\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('payment_id');
        $model = $this->_objectManager->create(\OnitsukaTigerCpss\PaymentList\Model\PaymentMethod::class);

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This payment method no longer exits. '));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('payment_method', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Payment Method') : __('New Payment Method'),
            $id ? __('Edit Payment Method') : __('New Payment Method')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Payment Method'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getname() : __('New Payment Method'));

        return $resultPage;
    }

    /**
     * Init actions
     *
     * @return Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OnitsukaTigerCpss_PaymentList::RealStorePaymentMethod')
            ->addBreadcrumb(__('Payment Method'), __('Payment Method'))
            ->addBreadcrumb(__('Manage Payment Method'), __('Manage Payment Method'));
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTigerCpss_PaymentList::paymentmethod_edit');
    }
}

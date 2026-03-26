<?php

namespace OnitsukaTigerIndo\SizeConverter\Controller\Adminhtml\Index;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry)
    {
        $this->_coreRegistry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute() {
        $id = $this->getRequest()->getParam('size_id');
        $model = $this->_objectManager->create('OnitsukaTigerIndo\SizeConverter\Model\IndoSize');

        if($id){
            $model->load($id);
            if(!$model->getId()){
                $this->messageManager->addError(__('This size no longer exits. '));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('indo_size', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Size') : __('New Size'),
            $id ? __('Edit Size') : __('New Size')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Size'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getname() : __('New Size'));

        return $resultPage;
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OnitsukaTigerIndo_SizeConverter::indosize')
            ->addBreadcrumb(__('Size'), __('Size'))
            ->addBreadcrumb(__('Manage Size'), __('Manage Size'));
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTigerIndo_SizeConverter::indosize_edit');
    }
}

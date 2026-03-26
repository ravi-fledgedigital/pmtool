<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Controller\Adminhtml\Gthk;

class Edit extends \OnitsukaTiger\Gthk\Controller\Adminhtml\Gthk
{

    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('gthk_id');
        $model = $this->_objectManager->create(\OnitsukaTiger\Gthk\Model\Gthk::class);

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This GHTK no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('onitsukatiger_gthk_gthk', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit GHTK') : __('New GHTK'),
            $id ? __('Edit GHTK') : __('New GHTK')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Gthks'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit GHTK %1', $model->getId()) : __('New GHTK'));
        return $resultPage;
    }
}


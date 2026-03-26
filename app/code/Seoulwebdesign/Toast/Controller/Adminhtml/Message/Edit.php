<?php
namespace Seoulwebdesign\Toast\Controller\Adminhtml\Message;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * The constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Check is allow
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Seoulwebdesign_Toast::message');
    }

    /**
     * The initial
     *
     * @return Page
     */
    protected function _initAction()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Seoulwebdesign_Toast::toast');
        $resultPage->addBreadcrumb(__('Seoulwebdesign Toast'), __('Seoulwebdesign Toast'));
        $resultPage->addBreadcrumb(__('Manage Toast Messages'), __('Manage Toast Messages'));
        return $resultPage;
    }

    /**
     * Main exectute
     *
     * @return Page|ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('message_id');
        $model = $this->_objectManager->create(\Seoulwebdesign\Toast\Model\Message::class);

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This message no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get(Session::class)->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('toast_message', $model);

        /** @var Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Message') : __('New Message'),
            $id ? __('Edit Message') : __('New Message')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Message'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getTitle() : __('New Message'));

        return $resultPage;
    }
}

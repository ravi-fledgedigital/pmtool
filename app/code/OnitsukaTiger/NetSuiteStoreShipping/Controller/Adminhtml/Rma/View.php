<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Rma;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class View extends \Magento\Backend\App\Action
{

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var \OnitsukaTiger\Logger\StoreShipping\Logger
     */
    protected $logger;

    public function __construct(
        RequestRepositoryInterface $requestRepository,
        Action\Context $context,
        \OnitsukaTiger\Logger\StoreShipping\Logger $logger
    ) {
        parent::__construct($context);
        $this->requestRepository = $requestRepository;
        $this->logger = $logger;
    }


    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('OnitsukaTiger_NetSuiteStoreShipping::manage');
        if ($requestId = (int) $this->getRequest()->getParam(RegistryConstants::REQUEST_ID)) {
            try {
                $this->requestRepository->getById($requestId);
                $resultPage->getConfig()->getTitle()->prepend(__('View Return Request'));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This request is no longer exists.'));
                $this->logger->error(sprintf('Request Id %s. Error: %s', $requestId, $exception->getMessage()));
                return $this->_redirect('*/*');
            }
        } else {
            $this->_redirect('*/*');
        }

        return $resultPage;
    }
}

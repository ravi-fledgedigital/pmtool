<?php

namespace OnitsukaTiger\RestockReports\Controller\Adminhtml\Restock;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Result\PageFactory;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \OnitsukaTiger\RestcokReports\Model\RestockReportFactory
     */
    public $restockReportFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface;
     */
    public $_sessionManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
     */
    protected $timezoneInterface;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \OnitsukaTiger\RestcokReports\Model\RestockReportFactory $restockReportFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \OnitsukaTiger\RestockReports\Model\RestockReportFactory $restockReportFactory,
        PageFactory $resultPageFactory,
        SessionManagerInterface $sessionManager,
        TimezoneInterface $timezoneInterface,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        parent::__construct($context);
        $this->restockReportFactory = $restockReportFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_sessionManager = $sessionManager;
        $this->timezoneInterface = $timezoneInterface;
        $this->publisher = $publisher;
    }

    /**
     * Save Data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rowData = $this->restockReportFactory->create();
        $data = $this->getRequest()->getPostValue();

        try {
            if ($data["from_date"] &&
                !empty(
                    $data["from_date"] &&
                        $data["to_date"] &&
                        !empty($data["to_date"])
                )
            ) {
                $date = $this->timezoneInterface->date()->format("y-m-d");
                $rowData->setData("from_date", $data["from_date"]);
                $rowData->setData("to_date", $data["to_date"]);
                $queue = "restockdata-" . $data["from_date"] . "-to-" . $data["to_date"];
                $rowData->setData("created_at", $date);
                $rowData->setData("name", $queue);

                $rowData->save();
                if ($rowData->getId()) {
                    $restockData = $this->_objectManager->create(
                        \OnitsukaTiger\RestockReports\Model\RestockData::class
                    );
                    $restockData->process($rowData->getId());
                }
                $this->messageManager->addSuccess(
                    __("Queue has been successfully added.")
                );
            } else {
                $this->messageManager->addError(
                    __("From-Date and To-Date are required fields.")
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect("report/restock/index");
    }
}

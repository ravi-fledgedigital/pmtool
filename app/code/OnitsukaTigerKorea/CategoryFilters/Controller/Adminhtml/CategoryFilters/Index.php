<?php
namespace OnitsukaTigerKorea\CategoryFilters\Controller\Adminhtml\CategoryFilters;

use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = "OnitsukaTigerKorea_CategoryFilters::OnitsukaTigerKorea\CategoryFilters";
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            "OnitsukaTigerKorea_CategoryFilters::CategoryFilters"
        );
        $resultPage->addBreadcrumb(
            __("Category Filters Data"),
            __("Category Filters Data")
        );
        $resultPage
            ->getConfig()
            ->getTitle()
            ->prepend(__("Category Filters Data"));
        return $resultPage;
    }
}

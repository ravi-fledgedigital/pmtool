<?php
namespace OnitsukaTigerKorea\CategoryFilters\Block\Adminhtml\CategoryFilters;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Edit record
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage
            ->getConfig()
            ->getTitle()
            ->prepend(__("Edit Record"));
        return $resultPage;
    }
}

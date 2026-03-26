<?php
namespace OnitsukaTigerKorea\CategoryFilters\Controller\Adminhtml\CategoryFilters;

use Magento\Framework\Controller\ResultFactory;

class AddRow extends \Magento\Backend\App\Action
{
    /**
     * @var \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
     */
    protected $categoryFiltersFactory;

    /**
     * @var \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersFactory
     */
    protected $categoryFiltersRelationFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
     * @param \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersRelationFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory,
        \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersRelationFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->categoryFiltersFactory = $categoryFiltersFactory;
        $this->categoryFiltersRelationFactory = $categoryFiltersRelationFactory;
    }
    /**
     * Add New Row Form page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $rowId = (int) $this->getRequest()->getParam("filter_id");

        $rowDataCategoryFiltersModel = $this->categoryFiltersFactory->create();
        $categoryFiltersRelationFactoryModel = $this->categoryFiltersRelationFactory->create();

        if ($rowId) {
            $rowDataCategoryFiltersModel = $rowDataCategoryFiltersModel->load(
                $rowId
            );
            $categoryFiltersRelationFactoryModel = $categoryFiltersRelationFactoryModel->load(
                $rowDataCategoryFiltersModel->getFilterId()
            );
            $rowName = $rowDataCategoryFiltersModel->getCategoryName();
            if (!$rowDataCategoryFiltersModel->getFilterId()) {
                $this->messageManager->addError(
                    __("Category Filters row data no longer exist.")
                );
                $this->_redirect("categoryfilters/categoryfilters/edit");
                return;
            }
        }

        $this->_coreRegistry->register(
            "row_data",
            $rowDataCategoryFiltersModel
        );
        $this->_coreRegistry->register(
            "dynamic_row",
            $categoryFiltersRelationFactoryModel
        );
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $name = $rowId
            ? __("Edit Category Filters - ") . $rowName
            : __("Add Category Filters");
        $resultPage
            ->getConfig()
            ->getTitle()
            ->prepend($name);
        return $resultPage;
    }

    /**
     * Authorization is allowed
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            "OnitsukaTigerKorea_CategoryFilters::add_row"
        );
    }
}

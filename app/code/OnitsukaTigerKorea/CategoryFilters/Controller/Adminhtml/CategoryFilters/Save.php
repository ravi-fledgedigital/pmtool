<?php
namespace OnitsukaTigerKorea\CategoryFilters\Controller\Adminhtml\CategoryFilters;

use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
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
     * @var \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    protected $_categoryFactory;

    /**
     * This is construct
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
     * @param \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersRelationFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory,
        \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersRelationFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->categoryFiltersFactory = $categoryFiltersFactory;
        $this->categoryFiltersRelationFactory = $categoryFiltersRelationFactory;
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context);
    }

    /**
     * Save Data
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $PostValue = $this->getRequest()->getPostValue();

        if (!$PostValue) {
            $this->_redirect("categoryfilters/categoryfilters/addrow");
            return;
        }
        try {
            $rowDataCategoryFiltersModel = $this->categoryFiltersFactory->create();
            if (isset($PostValue["filter_id"])) {
                $rowDataCategoryFiltersModel->load($PostValue["filter_id"]);
                $isFilteridExist = $this->isCategoryFiltersAlreadyExist(
                    $PostValue["category_id"],
                    $PostValue["category_id"]
                );
                $this->ifAlreadyExistDynamicData($PostValue["filter_id"]);
            } else {
                $isFilteridExist = $this->isCategoryFiltersAlreadyExist(
                    $PostValue["category_id"]
                );
            }

            if ($isFilteridExist) {
                $this->messageManager->addError(
                    __(
                        "Selected Category already exist. Please select new category."
                    )
                );
                return $resultRedirect->setPath("*/*/addrow", [
                    "filter_id" => $this->getRequest()->getParam("filter_id"),
                ]);
            }

            if (!isset($PostValue["dynamic_row"])) {
                $this->messageManager->addError(
                    __("Please add filter category.")
                );
                return $resultRedirect->setPath("*/*/addrow", [
                    "filter_id" => $this->getRequest()->getParam("filter_id"),
                ]);
            }

            $categoryName = $this->getCategoryNameByID(
                $PostValue["category_id"]
            );
            $rowDataCategoryFiltersModel->setStatus($PostValue["status"]);
            $rowDataCategoryFiltersModel->setCategoryName($categoryName);
            $rowDataCategoryFiltersModel->setCategoryId(
                $PostValue["category_id"]
            );
            $rowDataCategoryFiltersModel->save();

            if ($rowDataCategoryFiltersModel->getId()) {
                if (isset($PostValue["dynamic_row"])) {
                    foreach ($PostValue["dynamic_row"] as $dynamicRow) {
                        $rowDataCategoryFiltersRelationModel = $this->categoryFiltersRelationFactory->create();
                        $rowDataCategoryFiltersRelationModel->setFilterId(
                            $rowDataCategoryFiltersModel->getId()
                        );
                        $rowDataCategoryFiltersRelationModel->setCategoryName(
                            $dynamicRow["category_name"]
                        );
                        $rowDataCategoryFiltersRelationModel->setCategoryId(
                            $dynamicRow["category_id"]
                        );
                        $rowDataCategoryFiltersRelationModel->setParentCategoryId(
                            $PostValue["category_id"]
                        );
                        $rowDataCategoryFiltersRelationModel->save();
                    }
                }
            }

            $this->messageManager->addSuccess(
                __("Category filters has been successfully saved.")
            );
            return $resultRedirect->setPath("*/*/");
        } catch (Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $resultRedirect->setPath("*/*/edit", [
                "filter_id" => $this->getRequest()->getParam("filter_id"),
            ]);
        }
        return $resultRedirect->setPath("*/*/");
    }

    /**
     * Check Category Map permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            "OnitsukaTigerKorea_CategoryFilters::add_action"
        );
    }

    /**
     * Check if Already Exist Dynamic Data and delete data
     *
     * @param int $id
     * @return bool
     */
    private function ifAlreadyExistDynamicData($id)
    {
        $collection = $this->categoryFiltersRelationFactory
            ->create()
            ->getCollection();
        $collection->addFieldToFilter("filter_id", ["eq" => $id]);
        if ($collection && $collection->getSize() > 0) {
            foreach ($collection as $itemsCollection) {
                $itemsCollection->load($id);
                $itemsCollection->delete();
            }
        }
    }

    /**
     * Check if is Category Filters id Already Exist
     *
     * @param int $categoryId
     * @param int $id
     * @return bool
     */
    private function isCategoryFiltersAlreadyExist($categoryId, $id = false)
    {
        $collection = $this->_objectManager
            ->create(
                \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFilters::class
            )
            ->getCollection();

        $collection->addFieldToFilter("category_id", ["eq" => $categoryId]);
        if ($id) {
            $collection->addFieldToFilter("category_id", ["neq" => $id]);
        }

        if ($collection && $collection->getSize() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get Category Name By ID
     *
     * @param int $categoryId
     * @return bool
     */
    public function getCategoryNameByID($categoryId)
    {
        $categoryCollection = $this->_categoryFactory
            ->create()
            ->load($categoryId);

        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */

        $explodeCategory = explode("/", $categoryCollection->getPath());
        $categoryName = [];
        foreach ($explodeCategory as $category) {
            $category = $this->_categoryFactory->create()->load($category);
            if ($category && $category->getId()) {
                $categoryName[] = $category->getName();
            }
        }
        
        $catName = "";
        if (!empty($categoryName)) {
            $catName = implode("|", $categoryName);
        }
        return $catName;
    }
}

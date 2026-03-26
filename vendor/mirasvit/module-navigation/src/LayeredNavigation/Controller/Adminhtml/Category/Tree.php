<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\CategoryFactory;

class Tree extends Action
{
    private $jsonFactory;
    private $categoryFactory;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        CategoryFactory $categoryFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->categoryFactory = $categoryFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $categoryId = (int)($this->getRequest()->getParam('id') ?: 1);
        $selectedCategories = $this->getRequest()->getParam('selectedCategories', []);

        if ($categoryId === 0) {
            $categoryId = 1;
        }

        $category = $this->categoryFactory->create()->load($categoryId);
        $categoryIds = explode(',', (string)$category->getChildren());

        $parentIds = $this->getParentIdsForSelected($selectedCategories);

        $tree = [];
        foreach ($categoryIds as $childId) {
            $childCategory = $this->categoryFactory->create()->load($childId);

            $isSelected = in_array($childCategory->getId(), $selectedCategories);
            $isOpened   = in_array($childCategory->getId(), $parentIds) || $isSelected;

            $tree[] = [
                'id'       => $childCategory->getId(),
                'text'     => $childCategory->getName(),
                'children' => $childCategory->hasChildren(),
                'state'    => [
                    'opened'   => $isOpened,
                    'selected' => $isSelected
                ]
            ];
        }

        return $this->jsonFactory->create()->setData($tree);
    }

    private function getParentIdsForSelected(array $selectedIds): array
    {
        if (empty($selectedIds)) {
            return [];
        }

        $connection = $this->categoryFactory->create()->getResource()->getConnection();
        $tableName = $this->categoryFactory->create()->getResource()->getTable('catalog_category_entity');

        $select = $connection->select()
            ->from($tableName, ['path'])
            ->where('entity_id IN (?)', $selectedIds);

        $paths = $connection->fetchCol($select);

        $parentIds = [];
        foreach ($paths as $path) {
            $pathIds = explode('/', $path);
            foreach ($pathIds as $pid) {
                if ($pid && $pid != 1) {
                    $parentIds[] = (int)$pid;
                }
            }
        }

        return array_unique($parentIds);
    }
}

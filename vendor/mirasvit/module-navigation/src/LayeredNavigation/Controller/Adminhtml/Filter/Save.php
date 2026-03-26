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


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Filter;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\ResourceConnection;

class Save extends Action
{
    private $resultJsonFactory;
    private $attributeFactory;
    private $attributeConfigRepository;
    private $resource;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        AttributeFactory $attributeFactory,
        AttributeConfigRepository $attributeConfigRepository,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $horizontal = $this->getRequest()->getParam('horizontal');
        $sidebar    = $this->getRequest()->getParam('sidebar');

        try {
            $horizontal = is_array($horizontal) ? $horizontal : [];
            $sidebar = is_array($sidebar) ? $sidebar : [];

            $allAttributeCodes = array_unique(array_merge($horizontal, $sidebar));

            $positionsToUpdate = [];

            foreach ($allAttributeCodes as $attributeCode) {
                $attribute = $this->attributeFactory->create()->loadByCode('catalog_product', $attributeCode);
                if (!$attribute || !$attribute->getId()) {
                    continue;
                }

                $attributeId = (int)$attribute->getId();

                $config = $this->attributeConfigRepository->getByAttributeCode($attributeCode, false);
                if (!$config || !$config->getId()) {
                    $config = $this->attributeConfigRepository->create();
                    $config->setAttributeId($attributeId);
                    $config->setAttributeCode($attribute->getAttributeCode());
                }

                $inSidebar = in_array($attributeCode, $sidebar, true);
                $inHorizontal = in_array($attributeCode, $horizontal, true);

                $location = AttributeConfigInterface::POSITION_SIDEBAR;

                if ($inSidebar && $inHorizontal) {
                    $location = AttributeConfigInterface::POSITION_BOTH;
                } elseif ($inHorizontal) {
                    $location = AttributeConfigInterface::POSITION_HORIZONTAL;
                }

                $config->setPosition($location);

                if ($inHorizontal) {
                    $position = array_search($attributeCode, $horizontal, true);
                    $config->setHorizontalPosition((int)$position);
                }

                $this->attributeConfigRepository->save($config);

                if ($inSidebar) {
                    $position = array_search($attributeCode, $sidebar, true);
                    $positionsToUpdate[$attributeId] = (int)$position;
                }
            }

            if (!empty($positionsToUpdate)) {
                $this->updateAttributePositions($positionsToUpdate);
            }

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function updateAttributePositions(array $positions): void
    {
        $connection = $this->resource->getConnection();
        $attributeTable = $this->resource->getTableName('catalog_eav_attribute');

        foreach ($positions as $attributeId => $position) {
            $connection->update(
                $attributeTable,
                ['position' => $position],
                ['attribute_id = ?' => $attributeId]
            );
        }
    }
}

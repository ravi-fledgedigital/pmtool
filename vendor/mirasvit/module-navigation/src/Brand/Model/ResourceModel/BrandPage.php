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

namespace Mirasvit\Brand\Model\ResourceModel;


use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Api\Data\BrandPageStoreInterface;
use Magento\Store\Model\Store;

class BrandPage extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BrandPageInterface::TABLE_NAME, BrandPageInterface::ID);
    }

    /**
     * @return array
     */
    public function getAppliedOptionIds()
    {
        $connection = $this->getConnection();
        $select     = $connection->select()
            ->from(
                $this->_resources->getTableName(BrandPageInterface::TABLE_NAME),
                BrandPageInterface::ATTRIBUTE_OPTION_ID
            );

        return $connection->fetchCol($select);
    }

    /**
     * @param AbstractModel $object
     * @param array         $storeData
     *
     * @return bool
     */
    protected function insertStoreTableData($object, $storeData)
    {
        $storeData[BrandPageInterface::ID] = $object->getId();

        $this->getConnection()->insert(
            $this->getTable(BrandPageStoreInterface::TABLE_NAME),
            $storeData
        );
    }

    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterLoad($object);

        $storeValues = [];

        $select = $this->getConnection()->select()
            ->from($this->getTable(BrandPageStoreInterface::TABLE_NAME))
            ->where(BrandPageInterface::ID . ' = ?', $object->getId());

        foreach ($this->getConnection()->fetchAll($select) as $data) {
            $storeId = (int)$data[BrandPageStoreInterface::STORE_ID];

            foreach ($data as $field => $value) {
                if ($field === BrandPageInterface::ID || $field === BrandPageStoreInterface::STORE_ID) {
                    continue;
                }
                $storeValues[$storeId][$field] = $value;
            }
        }

        $object->setData(BrandPageStoreInterface::STORES, $storeValues);

        return $object;
    }

    public function loadByStore(BrandPageInterface $object, int $brandPageId, int $storeId): self
    {
        $connection = $this->getConnection();
        if (!$connection) {
            return $this;
        }

        $select = $connection->select()
            ->from($this->getMainTable())
            ->where(BrandPageInterface::ID . ' = ?', $brandPageId);

        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }

        $storeTable = $this->getTable(BrandPageStoreInterface::TABLE_NAME);
        $targetStoreId = $storeId !== 0 ? $storeId : Store::DEFAULT_STORE_ID;

        $storeSelect = $connection->select()
            ->from($storeTable)
            ->where('brand_page_id = ?', $brandPageId)
            ->where('store_id = ?', $targetStoreId);

        $storeData = $connection->fetchRow($storeSelect);
        $useDefault = [];

        foreach ($object->getStoreFields() as $field) {
            if (isset($storeData[$field]) && $storeData[$field]) {
                $object->setData($field, $storeData[$field]);
            } elseif ($storeId !== 0) {
                $useDefault[$field] = true;
            }
        }

        if (!empty($useDefault)) {
            $object->setUseDefault($useDefault);
        }

        $object->setStoreId($storeId);

        return $this;
    }
}
<?php
declare(strict_types=1);

namespace OnitsukaTiger\ImportExport\Model\Import;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Class Customer
 * @package OnitsukaTiger\ImportExport\Model\Import
 */
class Customer extends \Firebear\ImportExport\Model\Import\Customer
{
    /**
     * Update and insert data in entity table
     *
     * @param array $entitiesToCreate Rows for insert
     * @param array $entitiesToUpdate Rows for update
     * @return \Magento\CustomerImportExport\Model\Import\Customer
     */
    protected function _saveCustomerEntities(array $entitiesToCreate, array $entitiesToUpdate)
    {
        if ($entitiesToCreate) {
            $this->_connection->insertMultiple($this->_entityTable, $entitiesToCreate);
        }

        if ($entitiesToUpdate) {
            $this->_connection->insertOnDuplicate(
                $this->_entityTable,
                $entitiesToUpdate,
                $this->getCustomerEntityFieldsToUpdate($entitiesToUpdate)
            );
        }

        return $this;
    }

    /**
     * Filter the entity that are being updated so we only change fields found in the importer file
     *
     * @param array $entitiesToUpdate
     * @return array
     */
    private function getCustomerEntityFieldsToUpdate(array $entitiesToUpdate): array
    {
        $firstCustomer = reset($entitiesToUpdate);
        $columnsToUpdate = array_keys($firstCustomer);
        $customerFieldsToUpdate = array_filter(
            $this->customerFields,
            function ($field) use ($columnsToUpdate) {
                return in_array($field, $columnsToUpdate);
            }
        );
        return $customerFieldsToUpdate;
    }

    /**
     * Prepare customer data for update
     *
     * @param array $rowData
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _prepareDataForUpdate(array $rowData)
    {
        $multiSeparator = $this->getMultipleValueSeparator();
        $entitiesToCreate = [];
        $entitiesToUpdate = [];
        $attributesToSave = [];

        // entity table data
        $now = new \DateTime();
        if (empty($rowData['created_at'])) {
            $createdAt = $now;
        } else {
            $createdAt = (new \DateTime())->setTimestamp(strtotime($rowData['created_at']));
        }

        $emailInLowercase = strtolower($rowData[self::COLUMN_EMAIL]);
        $newCustomer = false;
        $entityId = $this->_getCustomerId($emailInLowercase, $rowData[self::COLUMN_WEBSITE]);
        if (!$entityId) {
            // create
            $newCustomer = true;
            $entityId = $this->_getNextEntityId();
            $this->_newCustomers[$emailInLowercase][$rowData[self::COLUMN_WEBSITE]] = $entityId;
        }

        // password change/set
        if (isset($rowData['password']) && strlen($rowData['password'])) {
            $rowData['password_hash'] = $this->_customerModel->hashPassword($rowData['password']);
        }
        $entityRow = ['entity_id' => $entityId];
        // attribute values
        foreach (array_intersect_key($rowData, $this->_attributes) as $attributeCode => $value) {

            $attributeParameters = $this->_attributes[$attributeCode];
            if (in_array($attributeParameters['type'], ['select', 'boolean'])) {
                $value = $this->getSelectAttrIdByValue($attributeParameters, $value);
                if ($attributeCode === CustomerInterface::GENDER && $value === 0) {
                    $value = null;
                }
            } elseif ('multiselect' == $attributeParameters['type']) {
                if (!empty($value)) {
                    $ids = [];
                    foreach (explode($multiSeparator, mb_strtolower($value)) as $subValue) {
                        $ids[] = $this->getSelectAttrIdByValue($attributeParameters, $subValue);
                    }
                    $value = implode(',', $ids);
                }
            } elseif ('datetime' == $attributeParameters['type'] && !empty($value)) {
                $value = (new \DateTime())->setTimestamp(strtotime($value));
                $value = $value->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            }

            if (!$this->_attributes[$attributeCode]['is_static']) {
                /** @var $attribute \Magento\Customer\Model\Attribute */
                $attribute = $this->_customerModel->getAttribute($attributeCode);
                $backendModel = $attribute->getBackendModel();
                if ($backendModel
                    && $attribute->getFrontendInput() != 'select'
                    && $attribute->getFrontendInput() != 'datetime') {
                    $attribute->getBackend()->beforeSave($this->_customerModel->setData($attributeCode, $value));
                    $value = $this->_customerModel->getData($attributeCode);
                }
                $attributesToSave[$attribute->getBackend()
                    ->getTable()][$entityId][$attributeParameters['id']] = $value;

                // restore 'backend_model' to avoid default setting
                $attribute->setBackendModel($backendModel);
            } else {
                $entityRow[$attributeCode] = $value;
            }
        }

        if ($newCustomer) {
            // create
            $entityRow['group_id'] = empty($rowData['group_id']) ? self::DEFAULT_GROUP_ID : $rowData['group_id'];
            $entityRow['store_id'] = empty($rowData[self::COLUMN_STORE])
                ? \Magento\Store\Model\Store::DEFAULT_STORE_ID : $this->_storeCodeToId[$rowData[self::COLUMN_STORE]];
            $entityRow['created_at'] = $createdAt->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $entityRow['updated_at'] = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $entityRow['website_id'] = $this->_websiteCodeToId[$rowData[self::COLUMN_WEBSITE]];
            $entityRow['email'] = $emailInLowercase;
            $entityRow['is_active'] = 1;
            $entitiesToCreate[] = $entityRow;
        } else {
            // edit
            $entityRow['updated_at'] = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            if (!empty($rowData[self::COLUMN_STORE])) {
                $entityRow['store_id'] = $this->_storeCodeToId[$rowData[self::COLUMN_STORE]];
            }
            $entitiesToUpdate[] = $entityRow;
        }

        return [
            self::ENTITIES_TO_CREATE_KEY => $entitiesToCreate,
            self::ENTITIES_TO_UPDATE_KEY => $entitiesToUpdate,
            self::ATTRIBUTES_TO_SAVE_KEY => $attributesToSave
        ];
    }

    /**
     * @return bool
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entitiesCreate = [];
            $entitiesUpdate = [];
            $entitiesDelete = [];
            $attributesToSave = [];

            foreach ($bunch as $rowNumber => $rowData) {
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $email = $rowData['email'];
                $rowData = $this->joinIdenticalyData($rowData);
                $website = $rowData[self::COLUMN_WEBSITE];
                if (isset($this->_newCustomers[strtolower($rowData[self::COLUMN_EMAIL])][$website])) {
                    continue;
                }
                $rowData = $this->customChangeData($rowData);
                if (!$this->validateRow($rowData, $rowNumber)) {
                    $this->addLogWriteln(__('customer with email: %1 is not valided', $email), $this->output, 'info');
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                if (\Magento\ImportExport\Model\Import::BEHAVIOR_DELETE == $this->getBehavior($rowData)) {
                    $entitiesDelete[] = $this->_getCustomerId(
                        $rowData[self::COLUMN_EMAIL],
                        $rowData[self::COLUMN_WEBSITE]
                    );
                }
                if (\Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior($rowData)
                    || \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $this->getBehavior($rowData)
                ) {
                    $processedData = $this->_prepareDataForUpdate($rowData);
                    if (\Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior($rowData)) {
                        $entitiesCreate = array_merge($entitiesCreate, $processedData[self::ENTITIES_TO_CREATE_KEY]);
                    }
                    $entitiesUpdate = array_merge($entitiesUpdate, $processedData[self::ENTITIES_TO_UPDATE_KEY]);
                    foreach ($processedData[self::ATTRIBUTES_TO_SAVE_KEY] as $tableName => $customerAttributes) {
                        if (!isset($attributesToSave[$tableName])) {
                            $attributesToSave[$tableName] = [];
                        }
                        $attributesToSave[$tableName] =
                            array_diff_key(
                                $attributesToSave[$tableName],
                                $customerAttributes
                            ) + $customerAttributes;
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);

                $this->addLogWriteln(
                    __('customer with email: %1 .... %2s', $email, $totalTime),
                    $this->output,
                    'info'
                );
            }
            $this->updateItemsCounterStats($entitiesCreate, $entitiesUpdate, $entitiesDelete);
            /**
             * Save prepared data
             */
            if ($entitiesCreate || $entitiesUpdate) {
                $this->_saveCustomerEntities($entitiesCreate, $entitiesUpdate);
            }
            if ($attributesToSave) {
                $this->_saveCustomerAttributes($attributesToSave);
            }
            if ($entitiesDelete) {
                $this->_deleteCustomerEntities($entitiesDelete);
            }
        }

        return true;
    }
}

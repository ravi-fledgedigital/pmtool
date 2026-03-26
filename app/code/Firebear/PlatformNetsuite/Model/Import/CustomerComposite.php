<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Import;

use \Magento\ImportExport\Model\Import\AbstractEntity;

/**
 * Class CustomerComposite
 * @package Firebear\PlatformNetsuite\Model\Import
 */
class CustomerComposite extends \Firebear\ImportExport\Model\Import\CustomerComposite
{
    /**
     * @var bool
     */
    protected $needColumnCheck = false;

    /**
     * @var Increment Ids
     */
    private $incrementIds;

    /**
     * @return $this|\Firebear\ImportExport\Model\Import\CustomerComposite
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
        $bunchRows = [];
        $startNewBunch = false;

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $masterAttributeCode = $this->getMasterAttributeCode();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }
        $prevData = [];
        while ($source->valid() || count($bunchRows) || isset($entityGroup)) {
            if ($startNewBunch || !$source->valid()) {
                /* If the end approached add last validated entity group to the bunch */
                if (!$source->valid() && isset($entityGroup)) {
                    foreach ($entityGroup as $key => $value) {
                        $bunchRows[$key] = $value;
                    }
                    unset($entityGroup);
                }
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );

                $bunchRows = [];
                $startNewBunch = false;
            }
            if ($source->valid()) {
                $valid = true;
                try {
                    $rowData = $source->current();

                    if (!empty($rowData['is_default_shipping'])) {
                        $rowData['_address_default_shipping_'] = true;
                    } else {
                        $rowData['_address_default_shipping_'] = false;
                    }

                    if (!empty($rowData['is_default_billing'])) {
                        $rowData['_address_default_billing_'] = true;
                    } else {
                        $rowData['_address_default_billing_'] = false;
                    }

                    if (!empty($rowData['_address_increment_id'])
                        && isset($this->incrementIds[$rowData['_address_increment_id']])) {
                        $source->next();
                        continue;
                    } elseif (!empty($rowData['_address_increment_id'])) {
                        $this->incrementIds[$rowData['_address_increment_id']] = true;
                    }

                    if (empty($rowData['email'])) {
                        $source->next();
                        continue;
                    }

                    foreach ($rowData as $attrName => $element) {
                        if (is_string($element)
                            && !mb_check_encoding($element, 'UTF-8')
                        ) {
                            $valid = false;
                            $this->addRowError(
                                AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                                $this->_processedRowsCount,
                                $attrName
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $valid = false;
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                }

                if (!empty($prevData) && (!isset($rowData['email']) || empty($rowData['email']))) {
                    $rowData = array_merge($prevData, $this->deleteEmpty($rowData));
                }

                $prevData = $rowData;

                if (!$valid) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                if (isset($rowData[$masterAttributeCode]) && trim($rowData[$masterAttributeCode])) {
                    /* Add entity group that passed validation to bunch */
                    if (isset($entityGroup)) {
                        foreach ($entityGroup as $key => $value) {
                            $bunchRows[$key] = $value;
                        }
                        $productDataSize = strlen(\Zend\Serializer\Serializer::serialize($bunchRows));

                        /* Check if the new bunch should be started */
                        $isBunchSizeExceeded = ($this->_bunchSize > 0 && count($bunchRows) >= $this->_bunchSize);
                        $startNewBunch = $productDataSize >= $this->_maxDataSize || $isBunchSizeExceeded;
                    }

                    /* And start a new one */
                    $entityGroup = [];
                }

                $isValid = $this->validateRow($rowData, $source->key());

                if (!$isValid) {
                    $errors = $this->getErrorAggregator()->getErrorByRowNumber($source->key());
                    foreach ($errors as $error) {
                        $this->addLogWriteln(
                            __('error from customer with email: %1. %2', $rowData['email'], $error->getErrorMessage()),
                            $this->output
                        );
                    }
                }
                if (isset($entityGroup) && $isValid) {
                    /* Add row to entity group */
                    $entityGroup[$source->key()] = $this->_prepareRowForDb($rowData);
                } elseif (isset($entityGroup)) {
                    /* In case validation of one line of the group fails kill the entire group */
                    unset($entityGroup);
                }

                $this->_processedRowsCount++;
                $source->next();
            }
        }
        return $this;
    }
}

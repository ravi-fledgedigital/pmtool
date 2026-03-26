<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

use Magento\Framework\Exception\InputException;
use Magento\Store\Model\ScopeInterface;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\StringCustomFieldRef;
use NetSuite\Classes\UpdateRequest;
use OnitsukaTiger\NetSuite\Model\SuiteTalk;

class CancelOrder extends SuiteTalk
{
    /**
     * @param $externalId
     * @param $storeId
     * @param $sourceCode
     * @throws InputException
     */
    public function cancel($externalId, $storeId, $sourceCode)
    {
        if ($this->isShippingFromWareHouse($sourceCode)) {
            $this->logger->debug('Begin Canceled');

            if (!$this->scopeConfig->getValue('netsuite/suitetalk/cancel', ScopeInterface::SCOPE_STORE, $storeId)) {
                $this->logger->info('Cancel data sync is disable');
                return;
            }

            // TODO need to update shipping base
            $service = $this->getService();
            $this->logger->debug('Service ok');

            $field = new StringCustomFieldRef();
            $field->scriptId = self::SCRIPT_ID_CUSTBODY_ORDER_STATUS;
            $field->value = 'Order Cancelled';

            $fields = new CustomFieldList();
            $fields->customField = [$field];

            $salesOrder = new SalesOrder();
            $salesOrder->externalId = $externalId;
            $salesOrder->customForm = $this->getRecordRef(
                $this->getNetsuiteInternalIdConfig('custom_form_id', $storeId),
                null,
                $this->getNetsuiteInternalIdConfig('custom_form_type', $storeId)
            );
            $salesOrder->entity = $this->getRecordRef(
                $this->getNetsuiteInternalIdConfig('netsuite_entity_id', $storeId),
                null
            );

            $salesOrder->location = $this->getRecordRef(null, $this->sourceMapping->getNetSuiteLocation($sourceCode));
            $salesOrder->customFieldList = $fields;
            $this->logger->debug('Service done');

            $updateRequest = new UpdateRequest();
            $updateRequest->record = $salesOrder;
            $this->logger->debug('Call it.....');
            $updateResponse = $service->update($updateRequest);
            if ($updateResponse->writeResponse->status->isSuccess) {
                $msg = __(
                    'Cancel data sync to NetSuite. external ID = [%1]',
                    $salesOrder->externalId
                );
                $this->logger->info($msg);
            } else {
                $msg = __(
                    'Failed : cancel data sync to NetSuite ' .
                    'Message: %1',
                    [
                        $updateResponse->writeResponse->status->statusDetail[0]->message
                    ]
                );
                $this->logger->error($msg);
                throw new InputException($msg);
            }
        }
    }
    /**
     * Check source code is from warehouse
     *
     * @param mixed $sourceCode
     * @return bool
     */
    public function isShippingFromWareHouse($sourceCode)
    {
        return strpos($sourceCode, '_wh_');
    }
}

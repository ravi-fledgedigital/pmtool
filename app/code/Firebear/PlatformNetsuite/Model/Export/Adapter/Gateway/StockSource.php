<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\Address;
use NetSuite\Classes\Location;
use NetSuite\Classes\UpdateRequest;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class StockSource
 * @package Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway
 */
class StockSource extends AbstractGateway
{
    use GeneralTrait;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var array
     */
    private $behaviorData = [];

    /**
     * Product constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ConsoleOutput $output
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ConsoleOutput $output,
        SourceRepositoryInterface $sourceRepository
    ) {
        parent::__construct($scopeConfig);
        $this->_logger = $logger;
        $this->output = $output;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @param $data
     * @return bool|mixed
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws ValidationException
     */
    public function exportStockSource($data)
    {
        $this->initService();
        $location = $this->getLocation($data);
        $source = $this->sourceRepository->get($data['source_code']);
        $locationInternalId = $source['netsuite_internal_id'];
        if ($location) {
            if ($locationInternalId) {
                $updateRequest = new UpdateRequest();
                $location->internalId = $locationInternalId;
                $updateRequest->record = $location;
                $response = $this->service->update($updateRequest);
            } else {
                $addRequest = new AddRequest();
                $addRequest->record = $location;
                $response = $this->service->add($addRequest);
            }

            if ($response->writeResponse->status->isSuccess) {
                if (!$locationInternalId) {
                    $locationInternalId = $response->writeResponse->baseRef->internalId;
                    $source->setData('netsuite_internal_id', $locationInternalId);
                    $this->sourceRepository->save($source);
                }
                $successMessage = __(
                    'The Source %1 was successfully exported to Netsuite. NetSuite internal id: %2',
                    $data['source_code'],
                    $response->writeResponse->baseRef->internalId
                );
                $this->addLogWriteln($successMessage, $this->output);
            } else {
                $errorMessage = __(
                    'Source with source code %1 was not exported to NetSuite' . ' Message: %2',
                    [
                        $data['source_code'],
                        $response->writeResponse->status->statusDetail[0]->message
                    ]
                );
                $this->addLogWriteln($errorMessage, $this->output, 'error');
            }
        }
        return false;
    }

    /**
     * @param $data
     * @return bool|Location
     */
    private function getLocation($data)
    {
        $location = new Location();
        $location->allowStorePickup = $data['is_pickup_location_active'];
        $location->latitude = $data['latitude'];
        $location->longitude = $data['longitude'];
        $address = new Address();
        if (!isset($this->countryMapping[$data['country_id']])) {
            $errorMessage = __(
                'Unknown country code. ' .
                ' Country code: %1. Source code: %2',
                [
                    $data['country_id'],
                    $data['source_code']
                ]
            );
            $this->addLogWriteln($errorMessage, $this->output, 'error');
            return false;
        }
        $address->country = $this->countryMapping[$data['country_id']];
        $address->city = $data['city'];
        $address->addrPhone = $data['phone'];
        $address->state = $data['region'];
        $address->zip = $data['postcode'];
        $address->addr1 = $data['street'];
        $location->mainAddress = $address;
        $location->name = $data['name'];
        $location->isInactive = !$data['enabled'];
        return $location;
    }
}

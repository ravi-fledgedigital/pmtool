<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\PlatformNetsuite\Plugin\Model\Import;

/**
 * Class AddressPlugin
 * @package Firebear\PlatformNetsuite\Plugin\Model\Import
 */
class AddressPlugin
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\AddressRepository
     */
    protected $addressRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * AddressPlugin constructor.
     * @param \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder
    ) {
        $this->addressRepository = $addressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param \Firebear\ImportExport\Model\Import\Address $model
     * @param $result
     * @return mixed
     */
    public function afterCustomChangeData(
        \Firebear\ImportExport\Model\Import\Address $model,
        $result
    ) {
        if (!empty($result['netsuite_internal_id'])) {
            $filter =  $this->filterBuilder->setField('netsuite_internal_id')
                ->setValue($result['netsuite_internal_id'])
                ->setConditionType('eq')
                ->create();
            $addresses = (array)($this->addressRepository->getList(
                $this->searchCriteriaBuilder->addFilters([$filter])->create()
            )->getItems());
            $address = array_shift($addresses);
            if (!empty($address)) {
                $result[\Firebear\ImportExport\Model\Import\Address::COLUMN_ADDRESS_ID] = $address->getId();
            }
        }

        if (!empty($result['_address_firstname'])) {
            $addressFirstname = explode(' ', $result['_address_firstname']);
            $result['_address_firstname'] = array_shift($addressFirstname);
        }

        if (!empty($result['_address_lastname'])) {
            $addressLastname = explode(' ', $result['_address_lastname']);
            $result['_address_lastname'] = end($addressLastname);
        }

        return $result;
    }
}

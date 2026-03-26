<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Export\Adapter;

use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Export\Adapter\Csv as AbstractAdapter;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Csv Export Adapter
 */
class Order extends AbstractAdapter
{
    /**
     * Adapter Data
     *
     * @var []
     */
    protected $_data;

    /**
     * @var \Firebear\ImportExport\Model\Export\Adapter\Gateway\Order
     */
    protected $gateway;

    /**
     * Order data
     *
     * @var []
     */
    protected $orderData;

    /**
     * Order data
     *
     * @var []
     */
    private $orderItemData;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    private $countryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * Order constructor.
     * @param Filesystem $filesystem
     * @param Gateway\Order $gateway
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param CustomerRepository $customerRepository
     * @param null $destination
     * @param array $data
     */
    public function __construct(
        Filesystem $filesystem,
        \Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway\Order $gateway,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        CustomerRepository $customerRepository,
        $destination = null,
        array $data = []
    ) {
        $this->gateway = $gateway;
        $this->countryFactory = $countryFactory;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->_data = $data;
        if (isset($data['behavior_data'])) {
            $data = $data['behavior_data'];
            $this->gateway->setBehaviorData($data);
            $this->_delimiter = $data['separator'] ?? $this->_delimiter;
            $this->_enclosure = $data['enclosure'] ?? $this->_enclosure;
        }

        parent::__construct(
            $filesystem,
            $destination
        );
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @throws \Exception
     * @return $this
     */
    public function writeRow(array $rowData)
    {
        $this->prepareOrderData($rowData);
        if (!empty($this->orderData)) {
            reset($this->orderData);
            $incrementId = key($this->orderData);
            $itemsCount = count($this->orderData[$incrementId]['items']);

            if ((($itemsCount == $this->orderData[$incrementId]['product_total']))
                && isset($this->orderData[$incrementId]['billing_address'])
                && isset($this->orderData[$incrementId]['shipping_address'])
                && (isset($rowData['increment_id'])
                    || isset($rowData['item:product_type'])
                    || isset($rowData['address:entity_id']))
                && !isset($this->orderData[$incrementId]['exported'])
            ) {
                $this->gateway->exportSource($this->orderData[$incrementId]);
                $this->orderData[$incrementId]['exported'] = true;
            }
        }

        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }
        if (null === $this->_headerCols) {
            $this->_headerCols = [];
        }
        $this->_fileHandler->writeCsv(
            array_merge(
                $this->_headerCols,
                array_intersect_key($rowData, $this->_headerCols)
            ),
            $this->_delimiter,
            $this->_enclosure
        );
        return $this;
    }


    /**
     * @param array $rowData
     */
    protected function prepareOrderData(array $rowData)
    {
        if (isset($rowData['increment_id'])
            && ((!isset($this->orderData[$rowData['increment_id']])
                    && !empty($this->orderData))
                || (empty($this->orderData)))
        ) {
            $this->orderData = null;
            $addressFirstname = !empty($rowData['address:firstname'])
                ? $rowData['address:firstname'] : 'Test';
            $addressLastname = !empty($rowData['address:lastname'])
                ? $rowData['address:lastname'] : 'Test';

            if (isset($rowData['customer_id'])) {
                $customer = $this->customerRepository
                    ->getById($rowData['customer_id']);
                $customerNetsuiteInternalId = $customer
                    ->getCustomAttribute('netsuite_internal_id');
            }

            $items = $this->prepareOrderItemData($rowData, $rowData['increment_id']);
            $this->orderData[$rowData['increment_id']] = [
                'entity_id' => $rowData['entity_id'],
                'increment_id' => $rowData['increment_id'],
                'items' => !empty($items)?
                    [$items] : [],
                'product_total' => $rowData['total_item_count'],
                'email' => $rowData['customer_email'],
                'firstname' => !empty($rowData['customer_firstname'])?
                    $rowData['customer_firstname'] : $addressFirstname,
                'lastname' => !empty($rowData['customer_lastname'])?
                    $rowData['customer_lastname'] : $addressLastname,
                'phone' => !empty($rowData['address:telephone'])?
                    $rowData['address:telephone'] : '',
                'discount_amount' => !empty($rowData['base_discount_amount'])?
                    $rowData['base_discount_amount'] : '',
                'shipping_amount' => !empty($rowData['base_shipping_amount'])?
                    $rowData['base_shipping_amount'] : '',
                'payment:po_number' => !empty($rowData['payment:po_number'])?
                    $rowData['payment:po_number'] : '',
                'netsuite_internal_id' =>
                    isset($rowData['netsuite_internal_id'])?
                        $rowData['netsuite_internal_id']
                        : '',
                'customer_id' =>
                    (isset($rowData['customer_id']))?
                        $rowData['customer_id'] : null,
                'customer_netsuite_internal_id' =>
                    (!empty($customerNetsuiteInternalId)) ?
                        $customerNetsuiteInternalId->getValue() : null,
                'shipping_address' => $this->prepareAddressData($rowData)
            ];
        } elseif (!isset($rowData['increment_id']) && !empty($this->orderData)) {
            reset($this->orderData);
            $incrementId = key($this->orderData);
            $orderItemData = $this->prepareOrderItemData($rowData, $incrementId);
            if (!empty($orderItemData)) {
                $this->orderData[$incrementId]['items'][] = $orderItemData;
            }
            $this->orderData[$incrementId]['billing_address'] = $this->prepareAddressData($rowData);
        }
    }

    /**
     * @param array $rowData
     * @param $incrementId
     * @return array
     */
    protected function prepareOrderItemData(array $rowData, $incrementId)
    {
        $data = [];
        if (isset($rowData['item:product_type'])
            && !isset($this->orderItemData[$incrementId][$rowData['item:sku']])
            && !empty($rowData['item:sku'])
        ) {
            try {
                $product = $this->productRepository->get($rowData['item:sku'], true);
                $netsuiteInternalId = $product->getData('netsuite_internal_id');
            } catch (NoSuchEntityException $e) {
                $netsuiteInternalId = null;
            }

            $data = [
                'sku' => $rowData['item:sku'],
                'internalId' => $netsuiteInternalId,
                'product_id' => $rowData['item:product_id'],
                'quantity' => $rowData['item:qty_ordered'],
                'amount' => $rowData['item:base_row_total'],
                'tax_percent' => $rowData['item:tax_percent']
            ];
            $this->orderItemData[$incrementId][$rowData['item:sku']] = true;
        }
        return $data;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function prepareAddressData(array $rowData)
    {
        $data = [
            'street' => !empty($rowData['address:street']) ?
                $rowData['address:street'] : '',
            'phone' =>  !empty($rowData['address:telephone']) ?
                $rowData['address:telephone'] : '',
            'country' => !empty($rowData['address:country_id']) ?
                $rowData['address:country_id'] : '',
            'city' => !empty($rowData['address:city']) ?
                $rowData['address:city'] : '',
            'state' => !empty($rowData['address:region']) ?
                $rowData['address:region'] : '',
            'zip' => !empty($rowData['address:postcode']) ?
                $rowData['address:postcode'] : '',
            'addressee' => !empty($rowData['address:firstname'])
            && !empty($rowData['address:lastname']) ?
                $rowData['address:firstname'] . ' ' . $rowData['address:lastname'] : '',
        ];

        return $data;
    }
}

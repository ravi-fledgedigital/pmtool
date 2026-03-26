<?php
/**
* phpcs:ignoreFile
*/
namespace OnitsukaTigerIndo\Biteship\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class SyncOrderToBiteship extends Command
{
    public const STORE_CODE = 'storeCode';

    protected $orderRepository;

    protected $_orderCollectionFactory;

    protected $data;

    /**
     * Constructs a new instance.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \OnitsukaTigerIndo\Biteship\Helper\Data $data
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        protected \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        protected \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \OnitsukaTigerIndo\Biteship\Helper\Data $data
    ) {
        $this->orderRepository = $orderRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->data = $data;
        parent::__construct();
    }

    /**
     * Configure function
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::STORE_CODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Store Code'
            )
        ];

        $this->setName('biteship:syncorders');
        $this->setDescription('Sync Order To Biteship');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * Order Sync
     *
     * @param      \Symfony\Component\Console\Input\InputInterface    $input   The input
     * @param      \Symfony\Component\Console\Output\OutputInterface  $output  The output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$logger = $objectManager->get(\Psr\Log\LoggerInterface::class);
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/BiteshipOrderSync.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $moduleStatus = $this->data->isEnableModule();
        $storeCodeStatic = 'id';
        $storeCodeStaticArray = ["web_id_id", "web_id_en"];

        $output->writeln("=========start order sync =========");
        $logger->info("=========start order sync =========");
        if (($storeCodeStatic == $input->getOption(self::STORE_CODE)) && ($moduleStatus == 1)) {
            foreach ($storeCodeStaticArray as $key) {
                $storeId[] = $this->storeRepository->get($key)->getId();
            }

            $days = $this->data->getBiteshipDaysConfigured();
            $fromTime = strtotime('-7 day', time());
            if (!empty($days)) {
                $fromTime = strtotime("-$days day", time());
            }
            $logger->info("Days: " . $days);
            $logger->info("From time: " . date('Y-m-d H:i:s', $fromTime));
            $orderApiUrl = $this->data->getBiteshipOrderApi();
            $orderData = $this->_orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('store_id', ['in' => $storeId])
                ->addFieldToFilter('biteship_order_id', ['null' => true])
                ->addFieldToFilter('created_at', [
                    'from'     => $fromTime,
                    'to'       => time(),
                    'datetime' => true
                ]);

            foreach ($orderData as $key) {
                if (!$key->getData('biteship_order_id') && $key->hasShipments()) {
                    $biteshipOrderData = $items = [];
                    foreach ($key->getAllVisibleItems() as $product) {
                        $options = $product->getProductOptionByCode('attributes_info');
                        $optionsData = [];
                        foreach ($options as $option) {
                            $optionsData[] = $option['label'] . ":" . $option['value'];
                        }
                        $productOptions = implode(', ', $optionsData);

                        $logger->info('Product Option: ' . print_r($productOptions, true));
                        $items[] = [
                            'name' => $product->getName(),
                            'value' => $product->getPrice(),
                            'sku' => $product->getSku(),
                            'quantity' => $product->getQtyOrdered(),
                            'description' => $productOptions . ", Sku:" . $product->getSku(),
                            'weight' => $product->getWeight()
                        ];
                    }
                    $billingStreet = $key->getBillingAddress()->getStreet();
                    $shippingStreet = $key->getShippingAddress()->getStreet();
                    $biteshipOrderData = [
                        'shipper_organization' => $key->getShippingMethod(),
                        'origin_contact_name' =>  $this->data->getBiteshipOriginContactName(),
                        'origin_contact_phone' => $this->data->getBiteshipOriginContactPhone(),
                        'origin_address' => $this->data->getBiteshipOriginAddress(),
                        'origin_postal_code' => $this->data->getBiteshipOriginPostalCode(),
                        'destination_contact_name' => $key->getShippingAddress()->getName(),
                        'destination_contact_phone' => $key->getShippingAddress()->getTelephone(),
                        'destination_address' => (is_array($shippingStreet)) ? implode(',', $shippingStreet) : $shippingStreet,
                        'destination_postal_code' => $key->getShippingAddress()->getPostcode(),
                        'courier_company' => 'sap',
                        'courier_type' => 'reg',
                        'delivery_type' => 'now',
                        'items' => $items
                    ];
                    $output->writeln(
                        $key->getId() . " - Order number."
                    );
                    $logger->info('Biteship Order Data: ' . print_r($biteshipOrderData, true));
                    try {
                        $orderSync = json_decode(
                            $this->data->getCurlCall(
                                $orderApiUrl,
                                json_encode($biteshipOrderData, true)
                            ),
                            true
                        );

                        if (!is_array($orderSync)) {
                            $logger->error("Invalid API response: " . print_r($orderSync, true));
                            $output->writeln("Error: Invalid API response");
                        }

                        $logger->info('Order Sync Data: ' . print_r($orderSync, true));
                        if (isset($orderSync['success']) && $orderSync['success'] == "1") {
                            $order = $this->orderRepository->get($key->getId());
                            $order->setData('biteship_order_id', $orderSync['id']);
                            $this->orderRepository->save($order);
                            $logger->info($key->getId() . " - Successfully sync this Order number.");
                            $output->writeln(
                                $key->getId() . " - Successfully sync this Order number."
                            );
                        } elseif (isset($orderSync['success']) && $orderSync['success'] == '0') {
                            $errorMessage = (isset($orderSync['error'])) ? $orderSync['error'] : 'Not getting error message';
                            $errorCode = (isset($orderSync['errorCode'])) ? $orderSync['errorCode'] : 'Not getting error code';
                            $logger->info(
                                $key->getId() . " - " . $errorMessage . " Error Code - " . $errorCode
                            );
                            $output->writeln(
                                $key->getId() . " - " . $errorMessage . " Error Code - " . $errorCode
                            );
                        } else {
                            $output->writeln(
                                $key->getId() . " - Shipment is not generated for this Order number."
                            );
                        }
                    } catch (\Exception $e) {
                        $output->writeln("Error: Invalid API response: " . $e->getMessage());
                        $output->writeln(
                            $key->getId() . " - Shipment is not generated for this Order number."
                        );
                    }
                } else {
                    if (!$key->hasShipments()) {
                        $logger->info($key->getId() . " - Shipment is not generated for this Order number.");
                        $output->writeln(
                            $key->getId() . " - Shipment is not generated for this Order number."
                        );
                    } else {
                        $logger->info($key->getId() . " - Already sync this Order number.");
                        $output->writeln(
                            $key->getId() . " - Already sync this Order number."
                        );
                    }
                }
            }
        } else {
            $output->writeln(
                "Invalid Store code or Store code is a required parameter or Please check the module enable/disabled."
            );
        }
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}

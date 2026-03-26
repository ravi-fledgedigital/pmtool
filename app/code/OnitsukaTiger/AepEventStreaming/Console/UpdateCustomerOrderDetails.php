<?php

namespace OnitsukaTiger\AepEventStreaming\Console;

use mysql_xdevapi\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCustomerOrderDetails extends Command
{
    const WEBSITE_IDS = 'website-ids';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * Update customer order information constructor
     *
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param string|null $name
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        private \Magento\Customer\Model\CustomerFactory $customerFactory,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private \Magento\Sales\Model\OrderFactory $orderFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
    }

    /**
     * Method configure
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::WEBSITE_IDS,
                null,
                InputOption::VALUE_REQUIRED,
                'Store Code'
            )
        ];

        $this->setName('OT:aep-update-customer-order-details');
        $this->setDescription('Update Customer Order Details');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * Update customer order information execute method
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($websiteIds = $input->getOption(self::WEBSITE_IDS)) {
            $this->output = $output;
            $this->appState->setAreaCode('crontab');

            $this->output->writeln("------- Customer Order Details Update Started -------");
            $websiteIds = explode(',', $websiteIds);
            $customerIds = $this->customerFactory->create()->getCollection()
                ->addAttributeToFilter('website_id', ['in' => $websiteIds])->getColumnValues('entity_id');

            if (!empty($customerIds)) {
                foreach ($customerIds as $customerId) {
                    $customer = $this->customerRepository->getById($customerId);
                    $extensionAttributes = $customer->getExtensionAttributes();

                    try {
                        $isUpdateCustomerOrderDate = false;
                        $isUpdateDob = false;
                        $isUpdateGender = false;
                        $orderData = [];
                        if ($extensionAttributes) {
                            $aepFirstOrderDate = $extensionAttributes->getAepFirstOrderDate();
                            $aepLastOrderDate = $extensionAttributes->getAepLastOrderDate();
                            $aepTotalOrderAmount = $extensionAttributes->getAepLifetimeValueAmt();

                            if (empty($aepFirstOrderDate)) {
                                $orderData = $this->getOrderDataByCustomerId($customerId);
                                if (!empty($orderData) && isset($orderData['first_order_date'])) {
                                    $isUpdateCustomerOrderDate = true;
                                    $extensionAttributes->setAepFirstOrderDate($orderData['first_order_date']);
                                }
                            }
                            if (empty($aepLastOrderDate)) {
                                $orderData = (!empty($orderData)) ? $orderData : $this->getOrderDataByCustomerId($customerId);
                                if (!empty($orderData) && isset($orderData['last_order_date'])) {
                                    $isUpdateCustomerOrderDate = true;
                                    $extensionAttributes->setAepLastOrderDate($orderData['last_order_date']);
                                }
                            }

                            $customerEmail = $customer->getEmail();
                            $storeId = $customer->getStoreId();
                            if (empty($aepTotalOrderAmount)) {
                                $totalOrderAmount = $this->getTotalOrderAmount($customerEmail, $storeId);
                                if ($totalOrderAmount > 0) {
                                    $isUpdateCustomerOrderDate = true;
                                    $extensionAttributes->setAepLifetimeValueAmt($totalOrderAmount);
                                }
                            }

                        }

                        // Update DOB of customer if not exist.
                        if (empty($customer->getDob())) {
                            $date = "1990-01-01";
                            $customer->setDob($date);
                            $isUpdateDob = true;
                        }

                        // Update gender of customer if not exist.
                        if (empty($customer->getGender())) {
                            $customer->setGender(5308);
                            $isUpdateGender = true;
                        }

                        // Update first and last order date if not exist.
                        if ($isUpdateCustomerOrderDate || $isUpdateDob || $isUpdateGender) {
                            if ($isUpdateCustomerOrderDate) {
                                $customer->setExtensionAttributes($extensionAttributes);
                            }
                            $this->customerRepository->save($customer);
                        }
                    } catch (\Exception $e) {
                        $this->output->writeln("Customer ID: " . $customerId . ", Error Message: " . $e->getMessage());
                    }
                }
            }
            $this->output->writeln("------- Customer Order Details Update Ended -------");
        } else {
            $output->writeln("Website id is a required params");
        }

        return Command::SUCCESS;
    }

    /**
     * Get order date data by customer id
     *
     * @param $customerId
     * @return array
     */
    private function getOrderDataByCustomerId($customerId)
    {
        $orderCollection = $this->orderFactory->create()->getCollection();
        $orderCollection->addFieldToFilter('customer_id', ['eq' => $customerId]);
        $orderDate = [];
        if ($orderCollection->getSize() > 0) {
            $orderDate = [
                'first_order_date' => $orderCollection->getFirstItem()->getCreatedAt(),
                'last_order_date' => $orderCollection->getLastItem()->getCreatedAt(),
            ];
        }

        return $orderDate;
    }

    /**
     * Get order total amount.
     *
     * @param $customerId
     * @return mixed
     */
    private function getTotalOrderAmount($email, $storeId)
    {
        $orderCollection = $this->orderFactory->create()->getCollection();
        $orderCollection->addFieldToFilter('customer_email', ['eq' => $email]);
        $orderCollection->addFieldToFilter('store_id', ['eq' => $storeId]);
        $orderTotal = 0;
        if ($orderCollection->getSize() > 0) {
            foreach ($orderCollection as $order) {

                $orderTotal = $orderTotal + $order->getBaseGrandTotal();
            }
        }

        return $orderTotal;
    }
}

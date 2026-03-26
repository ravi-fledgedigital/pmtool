<?php

namespace OnitsukaTiger\Shipment\Console;

use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Logger\Ninja\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailToWarehouse extends Command
{
    const SHIPMENT_IDS = 'shipmentIds';

    /**
     * @var State
     */
    protected $appState;

    /**
     * @param \OnitsukaTiger\Shipment\Model\CoreEventHandle $coreEventHandle
     * @param Logger $logger
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        private \OnitsukaTiger\Shipment\Model\CoreEventHandle $coreEventHandle,
        private Logger $logger,
        State $appState,
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
                self::SHIPMENT_IDS,
                null,
                InputOption::VALUE_REQUIRED,
                'Store Code'
            )
        ];

        $this->setName('OT:send-email-to-warehouse');
        $this->setDescription('Send Email To Warehouse');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($sIds = $input->getOption(self::SHIPMENT_IDS)) {
            $this->appState->setAreaCode('crontab');

            $output->writeln("------- Send Email To Warehouse Start -------");
            $shipmentIds = explode(' ', $sIds);
            if (!empty($shipmentIds)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                foreach ($shipmentIds as $shipmentId) {
                    try {
                        $shipment = $objectManager->create(\Magento\Sales\Model\Order\Shipment::class)->loadByIncrementId($shipmentId);
                        $this->coreEventHandle->eventHandleForShipmentPacked($shipment, $this->logger);
                    } catch (NoSuchEntityException $e) {
                        $output->writeln("Shipment not found: " . $shipmentId);
                        $output->writeln("Error: " . $e->getMessage());
                    }
                }
            }
            $output->writeln("------- Send Email To Warehouse End -------");
            return Command::SUCCESS;
        } else {
            $output->writeln("Shipment ids are missing");
            return Command::FAILURE;
        }
    }

}

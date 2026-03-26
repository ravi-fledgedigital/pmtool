<?php

namespace OnitsukaTiger\NetSuite\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncShipmentStatusToNetsuite extends Command
{

    public const SHIPMENT_ID = 'shipmentId';

    /**
     * @var State
     */
    private State $state;

    /**
     * @param State $state
     * @param Magento\Sales\Model\Order\Shipment $shipment
     * @param string|null $name
     */
    public function __construct(
        State $state,
        private \Magento\Sales\Model\Order\Shipment $shipment,
        private \OnitsukaTiger\NetSuite\Model\SuiteTalk\UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite,
        string $name = null
    ) {
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * Config Command Name
     *
     * @return void
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::SHIPMENT_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Shipment ID'
            )
        ];

        $this->setName('NS:update-shipment-status-to-netsuite');
        $this->setDescription('NS update shipment status to netsuite');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * Running Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        if ($shipmentID = $input->getOption(self::SHIPMENT_ID)) {
            $shipment = $this->shipment->loadByIncrementId($shipmentID);
            if ($shipment && $shipment->getId()) {
                $this->updateShipmentStatusToNetsuite->execute($shipment, "Delivered");
                $output->writeln('<info>Shipment sync to NS. Shipment ID: ' . $shipmentID . '</info>');
            } else {
                $output->writeln('<info>Requested Shipment Not found.</info>');
            }
        } else {
            $output->writeln('<info>Please use shipment increment id to sync order status</info>');
        }

        return 0;
    }
}

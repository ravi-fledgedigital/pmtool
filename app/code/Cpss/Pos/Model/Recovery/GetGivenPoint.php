<?php
namespace Cpss\Pos\Model\Recovery;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

class GetGivenPoint extends Command
{
    const STORE_CODE = 'store-code';

    public function __construct(
        string $name = null
    ) {
        parent::__construct($name);
    }

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

        $this->setName('execute:getgivenpoint_recovery');
        $this->setDescription('Get Given Point Data Recovery');
        $this->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($storeCode = $input->getOption(self::STORE_CODE)) {
            // sample command
            // php bin/magento execute:getgivenpoint_recovery
            $output->writeln("Start Get Given Point Recovery");
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $getGivenPoint = $objectManager->create(\Cpss\Pos\Cron\GetGivenPoint::class);
            $getGivenPoint->getGivenPointAndSave($storeCode);
            $output->writeln("End Get Given Point Recovery");

            return Command::SUCCESS;
        } else {
            $output->writeln("Store Code is a required params");
            return Command::FAILURE;
        }
    }
}

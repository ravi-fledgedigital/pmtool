<?php

namespace OnitsukaTigerCpss\Crm\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PosSftpEmailNotification extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    public function __construct(
        \Magento\Framework\App\State $appState,
        protected \OnitsukaTigerCpss\Crm\Helper\HelperData $helperData,
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
        $this->setName('OT:pos-sftp-email-notification');
        $this->setDescription('Send POS SFTP Email Notification');
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('crontab');

        $output->writeln("------- Send POS Email Notification Start -------");
        $this->helperData->sendFileExistEmailNotification('pos');
        $output->writeln("------- Send POS Email Notification Ended -------");

        return Command::SUCCESS;
    }
}

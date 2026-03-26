<?php

namespace OnitsukaTigerCpss\Crm\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CpssPendingFilesSftpEmailNotification extends Command
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
        $this->setName('OT:cpss-pending-file-sftp-email-notification');
        $this->setDescription('Send Cpss Pending File SFTP Email Notification');
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('crontab');

        $output->writeln("------- Send CPSS Email Notification Start -------");
        $this->helperData->sendCpssPendingFileSftpEmailNotification();
        $output->writeln("------- Send CPSS Email Notification Ended -------");

        return Command::SUCCESS;
    }
}

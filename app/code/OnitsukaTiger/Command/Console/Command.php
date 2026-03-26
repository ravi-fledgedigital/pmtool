<?php
namespace OnitsukaTiger\Command\Console;

/**
 * Class Command
 */
class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * exit code
     */
    const SUCCESS = 0;
    const FAILURE = 1;
    /**
     * @var LoggerInterface|\OnitsukaTiger\Logger\Logger
     */
    protected $logger;
    /**
     * @var float|string
     */
    protected $startTime;

    /**
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param string|null $name
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        string $name = null
    ) {
        $this->logger = $logger;
        parent::__construct($name);
    }

    protected function initialize(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->startTime = microtime(true);
        $this->logger->info($this->getName() . ' Start');
    }

    public function __destruct()
    {
        if($this->startTime) {
            $time = microtime(true) - $this->startTime;
            $this->logger->info($this->getName() . ' End ' . $time . 'sec');
        }
    }
}

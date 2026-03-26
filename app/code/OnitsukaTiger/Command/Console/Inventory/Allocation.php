<?php
namespace OnitsukaTiger\Command\Console\Inventory;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\WebsiteRepositoryInterface;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\Logger\SourceAlgorithm\Logger as AlgorithmLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Allocation extends \OnitsukaTiger\Command\Console\Command
{

    const NAME = 'ids';

    /**
     * @var \OnitsukaTiger\InventorySourceAlgorithm\Model\Allocation
     */
    protected $allocation;

    /**
     * @var AlgorithmLogger
     */
    protected $logger;

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var State
     */
    protected $state;

    /**
     * ReAllocation constructor.
     *
     * @param Logger $logger
     * @param \OnitsukaTiger\InventorySourceAlgorithm\Model\Allocation $allocation
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param AlgorithmLogger $algorithmLogger
     * @param State $state
     */
    public function __construct(
        Logger $logger,
        \OnitsukaTiger\InventorySourceAlgorithm\Model\Allocation $allocation,
        WebsiteRepositoryInterface $websiteRepository,
        AlgorithmLogger $algorithmLogger,
        State $state
    ) {
        $this->logger = $algorithmLogger;
        $this->allocation = $allocation;
        $this->websiteRepository = $websiteRepository;
        $this->state = $state;
        parent::__construct($logger);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('inventory:allocation');
        $this->setDescription('Allocation and Generate Shipment of all Order Status is Processing');
        $this->addArgument(
            self::NAME,
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Space-separated list of Web ID or omit to apply to all Web.'
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_GLOBAL);
        $output->writeln('<info>Allocation is running...</info>');
        if ($input->getArgument(self::NAME)) {
            $requestedTypes = $input->getArgument(self::NAME);
            $webIds        = array_filter(array_map('trim', $requestedTypes), 'strlen');
            foreach ($webIds as $webId) {
                try {
                    $web = $this->websiteRepository->get($webId);
                } catch (\Exception $exception) {
                    $output->writeln("<error>{$exception->getMessage()}</error>");
                    $this->logger->error($exception->getMessage());
                    continue;
                }
                $this->allocation->setOutput($output);
                $output->writeln(sprintf('<info>Web %s will allocate</info>', $web->getName()));
                $this->logger->info(sprintf('Web %s will allocate', $web->getName()));
                $this->allocation->execute($webId);
            }
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } else {
            $this->allocation->setOutput($output);
            $output->writeln('<info>All Web will allocate</info>');
            $this->logger->info('All Web will allocate');
            $this->allocation->execute(null);
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }
        $output->writeln('<info>Allocation run done</info>');
        $this->logger->info('Allocation run done');
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}

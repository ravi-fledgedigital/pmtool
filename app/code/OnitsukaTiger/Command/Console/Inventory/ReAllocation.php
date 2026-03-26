<?php
namespace OnitsukaTiger\Command\Console\Inventory;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Store\Api\WebsiteRepositoryInterface;
use OnitsukaTiger\Logger\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OnitsukaTiger\Logger\SourceAlgorithm\Logger as AlgorithmLogger;
use Symfony\Component\Console\Command\Command;
class ReAllocation extends \OnitsukaTiger\Command\Console\Command {

    const NAME = 'ids';

    /**
     * @var \OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation
     */
    protected $reallocation;

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var AlgorithmLogger
     */
    protected $logger;

    /**
     * @var State
     */
    protected $state;

    /**
     * ReAllocation constructor.
     *
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param \OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation $reallocation
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param AlgorithmLogger $algorithmLogger
     * @param State $state
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        \OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation $reallocation,
        WebsiteRepositoryInterface $websiteRepository,
        AlgorithmLogger $algorithmLogger,
        State $state
    )
    {
        $this->logger = $algorithmLogger;
        $this->reallocation = $reallocation;
        $this->websiteRepository = $websiteRepository;
        $this->state = $state;
        parent::__construct($logger);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('inventory:reallocation');
        $this->setDescription('ReAllocation and ReGenerate Shipment of all Order Status is Stock Pending');
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_GLOBAL);
        $output->writeln('<info>ReAllocation is running...</info>');
        if($input->getArgument(self::NAME)) {
            $requestedTypes = $input->getArgument(self::NAME);
            $webIds        = array_filter(array_map('trim', $requestedTypes), 'strlen');
            foreach ($webIds as $webId) {
                try {
                    $web = $this->websiteRepository->get($webId);
                }catch (\Exception $exception) {
                    $output->writeln("<error>{$exception->getMessage()}</error>");
                    $this->logger->error($exception->getMessage());
                    continue;
                }
                $output->writeln(sprintf('<info>Web %s will allocate</info>', $web->getName()));
                $this->logger->info(sprintf('Web %s will allocate', $web->getName()));
                $this->reallocation->execute($webId);
            }
        }else {
                $this->reallocation->setOutput($output);
                $this->reallocation->execute(null);
                $output->writeln('<info>All Web was reallocated successfully!</info>');
                $this->logger->info('All Web was reallocated successfully!');
        }
        $output->writeln('<info>Reallocation run done</info>');
        $this->logger->info('Reallocation run done');

        return Command::SUCCESS;
    }

}

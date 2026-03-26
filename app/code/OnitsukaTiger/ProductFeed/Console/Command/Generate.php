<?php
namespace OnitsukaTiger\ProductFeed\Console\Command;

use Exception;
use Magento\Framework\App\Config\Storage\Writer;
use Mageplaza\ProductFeed\Helper\Data;
use Mageplaza\ProductFeed\Model\FeedFactory;
use Mageplaza\ProductFeed\Model\ResourceModel\Feed\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Mageplaza\ProductFeed\Model\Config\Source\ExecutionMode;

/**
 * Class Generate
 * @package OnitsukaTiger\ProductFeed\Console\Command
 */
class Generate extends \Mageplaza\ProductFeed\Console\Command\Generate {

    /** @var State **/
    private $state;

    /**
     * @param State $state
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param FeedFactory $feedFactory
     * @param CollectionFactory $collectionFactory
     * @param $name
     */
    public function __construct(
        State $state,
        Data $helper,
        LoggerInterface $logger,
        FeedFactory $feedFactory,
        CollectionFactory $collectionFactory,
        $name = null
    ) {
        $this->state = $state;
        parent::__construct($helper, $logger, $feedFactory, $collectionFactory, $name);
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $output->writeln('<info>Running...</info>');
        if ($input->getArgument('ids')) {
            $requestedTypes = $input->getArgument('ids');
            $feedIDs        = array_filter(array_map('trim', $requestedTypes), 'strlen');
            foreach ($feedIDs as $feedID) {
                try {
                    $feed = $this->feedFactory->create()->load($feedID);
                    if (!$feed->getId()) {
                        $output->writeln('<error>The feed does not exist</error>');

                        return false;
                    }
                    $type = $feed->getData('execution_mode') === ExecutionMode::CRON ? 1 : 0;
                    $this->helper->generateAndDeliveryFeed($feed, 0, $type);
                    if (empty($this->helper->getEmailConfig('send_to'))) {
                        $output->writeln('<error>Please enter the email before send.</error>');
                    }
                    $output->writeln('<info>The feed ID ' . $feedID . ' generated Successfully!</info>');
                } catch (Exception $exception) {
                    $output->writeln("<error>{$exception->getMessage()}</error>");
                }
            }
        } else {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('status', 1);
            $collection->walk([$this, 'generate']);
            $output->writeln('<info>All feed generated Successfully!</info>');
        }

        return true;
    }
}

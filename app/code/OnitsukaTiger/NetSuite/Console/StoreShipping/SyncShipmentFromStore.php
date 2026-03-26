<?php
namespace OnitsukaTiger\NetSuite\Console\StoreShipping;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\InputException;
use OnitsukaTiger\Command\Console\Command;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping\Sync;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncShipmentFromStore extends Command {

    /**
     * @var Sync
     */
    protected $storeShipping;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State $state
     * @param Sync $storeShipping
     * @param Logger $logger
     */
    public function __construct(
        State $state,
        Sync $storeShipping,
        Logger $logger
    )
    {
        $this->state = $state;
        $this->storeShipping = $storeShipping;
        parent::__construct($logger);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('store-shipping:sync');
        $this->setDescription('Sync Order From Store into NetSuite');

        parent::configure();
    }


    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_WEBAPI_SOAP);
        $this->storeShipping->execute($input,$output);
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

}

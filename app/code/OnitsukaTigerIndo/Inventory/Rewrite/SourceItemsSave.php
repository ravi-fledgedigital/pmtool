<?php
/**
 * SourceItemsSave
 */

namespace OnitsukaTigerIndo\Inventory\Rewrite;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use OnitsukaTiger\Logger\Api\Logger;

class SourceItemsSave extends \Magento\Inventory\Model\SourceItem\Command\SourceItemsSave
{

    /**
     * @var Logger
     */
    protected $logger;

    protected $json;

    /**
     * @param SourceItemsSaveHandler $sourceItemsSaveHandler
     */
    public function __construct(
        SourceItemsSaveHandler $sourceItemsSaveHandler,
        Logger                 $logger,
        Json                   $json
    ) {
        $this->logger = $logger;
        $this->json = $json;
        parent::__construct($sourceItemsSaveHandler);
    }

    /**
     * @param array $sourceItems
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    public function execute(array $sourceItems): void
    {
        $array = [];
        foreach ($sourceItems as $sourceItemsval) {
            $array[] = $sourceItemsval->getData();
        }
        $this->logger->info('----- Magento Inventory JSON() ----- data : ' . print_r(json_encode($array), true));
        parent::execute($sourceItems);
    }
}
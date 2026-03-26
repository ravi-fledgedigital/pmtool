<?php
namespace OnitsukaTiger\Cegid\Model;

use Exception;
use OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction\CollectionFactory;
use OnitsukaTiger\Cegid\Model\Service\CegidApiService;
use OnitsukaTiger\Cegid\Model\ReturnAction;
use OnitsukaTiger\Cegid\Logger\Logger;

class ReturnStatusProcessor
{
    private CegidApiService $apiService;
    private CollectionFactory $collectionFactory;
    private \OnitsukaTiger\Cegid\Model\Config $config;
    private Logger $logger;

    /**
     * @param CegidApiService $apiService
     * @param CollectionFactory $collectionFactory
     * @param \OnitsukaTiger\Cegid\Model\Config $config
     * @param Logger $logger
     */
    public function __construct(
        CegidApiService     $apiService,
        CollectionFactory   $collectionFactory,
        Config              $config,
        Logger              $logger
    ) {
        $this->apiService = $apiService;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $this->logger->info('----- Start get Return Status----- ' );
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter("status", ReturnAction::STATUS_UNSENT_CEGID)
            ->addFieldToFilter("number", ['neq' => ""])
            ->addFieldToFilter("stub", ['neq' => ""])
            ->addFieldToFilter("type", ['neq' => ""]);

        $databaseId = $this->config->getReturnDatabaseId();

        foreach ($collection as $item) {
            $xmlSentToApi =
                '<soap:Envelope
                    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                    <soap:Body>
                        <GetDetail
                            xmlns="http://www.cegid.fr/Retail/1.0">
                            <Request>
                                <Key>
                                    <Number>' . $item->getNumber() . '</Number>
                                    <Stub>' . $item->getStub() . '</Stub>
                                    <Type>' . $item->getType() . '</Type>
                                </Key>
                            </Request>
                            <Context>
                                <DatabaseId>' . $databaseId . '</DatabaseId>
                            </Context>
                        </GetDetail>
                    </soap:Body>
                </soap:Envelope>';
            $this->logger->info('-----  Create xml data Return Status success----- ' );
            $this->logger->info('----- Body data ----- ' . $xmlSentToApi);
            $this->apiService->getReturnStatus($xmlSentToApi, $item->getReturnActionId());
        }
    }
}
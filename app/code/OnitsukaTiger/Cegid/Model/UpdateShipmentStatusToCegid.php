<?php

namespace OnitsukaTiger\Cegid\Model;

use Exception;
use Magento\Framework\HTTP\Client\CurlFactory;
use OnitsukaTiger\Cegid\Logger\Logger;
use OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction\CollectionFactory;
use OnitsukaTiger\Cegid\Model\Service\CegidApiService;

class UpdateShipmentStatusToCegid
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
        Logger              $logger,
        private CurlFactory $curlFactory
    ) {
        $this->apiService = $apiService;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function execute($shipment, $status)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/updateShipmentStatusToCegid.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('----- Shipment Status Update Start----- ');
        $username = $this->config->getReturnUserName();
        $password = $this->config->getReturnPassword();
        $auth = $username . ":" . $password;
        $token = base64_encode($auth);
        $curl = curl_init();
        $url = $this->config->getUpdateStatusUrl($shipment->getStoreId());
        $logger->info("Usernmae: " . $username);
        $logger->info("Password: " . $password);
        $logger->info("Token: " . $token);
        $logger->info("Url: " . $url);
        $logger->info("Store ID: " . $shipment->getStoreId());
        $logger->info("Shipment ID: " . $shipment->getIncrementId());
        $logger->info("Source Code: " . $shipment->getExtensionAttributes()->getSourceCode());
        $logger->info("Status: " . $status);
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'[
                {
                    "storeId": ' . $shipment->getStoreId() . ',
                    "incrementId": "' . $shipment->getIncrementId() . '",
                    "sourceCode": "' . $shipment->getExtensionAttributes()->getSourceCode() . '",
                    "status": "' . $status . '"
                }
            ]',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . $token
            ],
        ]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $logger->info("Curl error: " . curl_error($curl));
        } else {
            $logger->info("Response Body: " . print_r(json_decode($response, true), true));
        }
        curl_close($curl);
        $logger->info('----- Shipment Status Update End----- ');
    }
}

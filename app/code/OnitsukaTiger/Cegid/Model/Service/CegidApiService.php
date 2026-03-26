<?php

namespace OnitsukaTiger\Cegid\Model\Service;

use Amasty\Rma\Model\Request\Repository;
use Exception;
use OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface;
use OnitsukaTiger\Cegid\Logger\Logger;
use OnitsukaTiger\Cegid\Model\Config;
use OnitsukaTiger\Cegid\Model\ReturnActionFactory;
use OnitsukaTiger\Cegid\Model\ReturnActionRepository;
use OnitsukaTiger\Cegid\Model\ReturnAction;
use Magento\Framework\App\ResourceConnection;
use Laminas\Http\Response as HttpResponse;
/**
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 */
class CegidApiService
{
    const URL_SOAP_ACTION_CREATE_API = 'http://www.cegid.fr/Retail/1.0/IItemsDeliveryManagementWebService/Create';
    const URL_SOAP_ACTION_GET_DETAIL_API = 'http://www.cegid.fr/Retail/1.0/IItemsDeliveryManagementWebService/GetDetail';
    const RECEIVED = 'RA';
    const REJECT = 'RR';

    private \Laminas\Http\ClientFactory $httpClientFactory;
    private ReturnActionFactory $returnActionFactory;
    private ReturnActionRepository $returnActionRepository;
    private Logger $logger;
    private Config $config;
    private Repository $returnRequestRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Laminas\Http\ClientFactory $httpClientFactory
     * @param ReturnActionFactory $returnActionFactory
     * @param ReturnActionRepository $returnActionRepository
     * @param Logger $logger
     * @param Config $config
     * @param Repository $returnRequestRepository
     */
    public function __construct(
        \Laminas\Http\ClientFactory $httpClientFactory,
        ReturnActionFactory    $returnActionFactory,
        ReturnActionRepository  $returnActionRepository,
        Logger  $logger,
        Config  $config,
        Repository  $returnRequestRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->returnActionFactory = $returnActionFactory;
        $this->returnActionRepository = $returnActionRepository;
        $this->logger = $logger;
        $this->config = $config;
        $this->returnRequestRepository = $returnRequestRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $url
     * @param $method
     * @param $data
     * @param $SOAPAction
     * @return HttpResponse
     * @throws Exception
     */
    public function execute($url, $method, $data, $SOAPAction): HttpResponse
    {
        $user = $this->config->getReturnUserName();
        $password = $this->config->getReturnPassword();

        $client = $this->httpClientFactory->create();
        $client->setUri($url);

        // Parse URL to get host
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];

        $client->setHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'Content-Length' => strlen($data),
            'Host' => $host,
            'Connection' => 'keep-alive',
            'SOAPAction' => '"' . $SOAPAction . '"', // Add quotes around SOAPAction
            'Authorization' => 'Basic ' . base64_encode($user . ':' . $password)
        ]);

        // Set client options
        $client->setOptions([
            'adapter' => 'Laminas\Http\Client\Adapter\Curl',
            'curloptions' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
                CURLOPT_HTTPHEADER => ['Expect:'] // Disable Expect header
            ],
            'timeout' => 30
        ]);

        if ($data) {
            if ($method == 'POST') {
                $client->setRawBody($data);
            } elseif ($method == 'GET') {
                $client->setParameterGet($data);
            }
        }

        try {
            $client->setMethod($method);

            // Add debug logging before sending
            $this->logger->debug('----- Send request to Cegid Start ----- ');
            $this->logger->debug('Request URL: ' . $url);
            $this->logger->debug('Username: ' . $user);
            $this->logger->debug('Password: ' . $password);
            $this->logger->debug('Request Headers: ' . print_r($client->getRequest()->getHeaders()->toArray(), true));
            $this->logger->debug('Request Body: ' . $data);

            $response = $client->send();

            // Log response for debugging
            $this->logger->debug('Response Status: ' . $response->getStatusCode());
            $this->logger->debug('Response Body: ' . $response->getBody());
            $this->logger->debug('----- Send request to Cegid End ----- ');
            file_put_contents(BP . '/var/log/cegid_response.xml', $response->getBody());
            return $response;
        } catch (Exception $exception) {
            $this->logger->error('Call API to Cegid failed: ' . $exception->getMessage());
            $this->logger->error('Request headers: ' . print_r($client->getRequest()->getHeaders()->toArray(), true));
            $this->logger->error('Request body: ' . $client->getRequest()->getContent());
            throw new Exception($exception->getMessage());
        }

    }

    /**
     * @param $body
     * @param $requestId
     * @return ReturnActionInterface|void
     */
    public function getReturnInformation($body, $requestId)
    {
        try {
            $this->logger->info('----- Set data Return information  ----- ' );
            $url = $this->config->getReturnEndpoint();
            $SOAPAction = self::URL_SOAP_ACTION_CREATE_API;
            $result = $this->execute($url, 'POST', $body, $SOAPAction);
            file_put_contents(BP . '/var/log/cegid_response.xml', $result->getBody());
            $data = simplexml_load_string($result->getBody());
            $data->registerXPathNamespace('envoy', 'http://www.cegid.fr/Retail/1.0');
            $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result->getBody());
            $xml = new \SimpleXMLElement($response);
            $xmlArray = json_decode(json_encode((array)$xml), true);
            $this->logger->info('Data response: ' . json_encode($data));
            $errorMessage = '';
            if (isset($xmlArray['sBody']['sFault']['detail']['CbpExceptionDetail']['InnerException']['InnerException']['InnerException']['InnerException']['Message'])) {
                $errorMessage = $xmlArray['sBody']['sFault']['detail']['CbpExceptionDetail']['InnerException']['InnerException']['InnerException']['InnerException']['Message'];
                if(empty($errorMessage)) {
                    $errorMessage = __('');
                }
                $this->insertCegidSyncedErrorMessage($requestId, $errorMessage);
            }
            $item = $data->xpath('//envoy:Documents')[0]->Document;
            $number = (array)$item->Key->Number;
            $stub = (array)$item->Key->Stub;
            $type = (array)$item->Key->Type;
            $returnAction = $this->returnActionFactory->create();
            $returnAction->setNumber($number[0]);
            $returnAction->setStub($stub[0]);
            $returnAction->setType($type[0]);
            $returnAction->setRequestId($requestId);
            $returnAction->setStatus(ReturnAction::STATUS_UNSENT_CEGID);
            $this->logger->info('----- Set data Return success  ----- ');
            $this->logger->info('----- Body reponse' . $result->getBody() . ' ----- ');
            return $this->returnActionRepository->save($returnAction);
        }
        catch (Exception $exception) {
            $this->logger->error('Call API to Cegid Create with request_id ' . $requestId . ' failed: ' . $exception->getMessage());
        }
    }


    /**
     * Insert cegid_synced_error_message for a given request_id
     *
     * @param int $requestId
     * @param string $errorMessage
     * @return void
     */
    public function insertCegidSyncedErrorMessage($requestId, $errorMessage)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('amasty_rma_request');
            // Check if record exists
            $select = $connection->select()
                ->from($tableName, 'request_id')
                ->where('request_id = :request_id');
            $bind = ['request_id' => (int) $requestId];
            $result = $connection->fetchOne($select, $bind);

            if ($result) {
                // Update the existing record
                $connection->update(
                    $tableName,
                    ['cegid_synced_error_message' => $errorMessage],
                    ['request_id = ?' => $requestId]
                );
            }
            $this->logger->info('Inserted cegid_synced_error_message for request_id: ' . $requestId);
        } catch (Exception $exception) {
            $this->logger->error('Failed to insert cegid_synced_error_message: ' . $exception->getMessage());
        }
    }

    /**
     * @param $body
     * @param $returnId
     * @return mixed|string|void
     */
    public function getReturnStatus($body, $returnId)
    {
        try {
            $returnCegid = $this->returnActionRepository->get($returnId);
            $url = $this->config->getReturnEndpoint();
            $SOAPAction = self::URL_SOAP_ACTION_GET_DETAIL_API;
            $result = $this->execute($url, 'POST', $body, $SOAPAction);


            $data = simplexml_load_string($result->getBody());
            $data->registerXPathNamespace('envoy', 'http://www.cegid.fr/Retail/1.0');
            $resultData = $data->xpath('//envoy:Lines')[0];
            $packageReference = $resultData->Line->PackageReference;

            if ($this->isCegitUnprocess($packageReference)) {
                return;
            }

            if ($this->isCegitReceived($packageReference)) {
                $status = $this->config->getReturnStatusReceived();
            } else { // Rejected
                $status = $this->config->getReturnStatusRejected();
            }
            $returnRequest = $this->returnRequestRepository->getById($returnCegid->getRequestId());
            $returnRequest->setStatus($status);
            $this->returnRequestRepository->save($returnRequest);

            $returnCegid->setStatus(ReturnAction::STATUS_SENT_CEGID);
            $this->returnActionRepository->save($returnCegid);
            $this->logger->info('----- Save data return status success----- ' );

        } catch (Exception $exception) {
            $this->logger->error('Call API to Cegid get return' . $returnId . ' status failed: ' . $exception->getMessage());
        }
    }

    /**
     * @param $body
     * @param $shipment
     * @return mixed|string|void
     */
    public function updateOrderStatus($body, $shipment)
    {
        try {
            $url = $this->config->getUpdateStatusUrl($shipment->getStoreId());
            $SOAPAction = self::URL_SOAP_ACTION_GET_DETAIL_API;
            $result = $this->execute($url, 'POST', $body, $SOAPAction);


            $data = simplexml_load_string($result->getBody());
            $data->registerXPathNamespace('envoy', 'http://www.cegid.fr/Retail/1.0');
            $resultData = $data->xpath('//envoy:Lines')[0];
            $packageReference = $resultData->Line->PackageReference;

            if ($this->isCegitUnprocess($packageReference)) {
                return;
            }

            if ($this->isCegitReceived($packageReference)) {
                $status = $this->config->getReturnStatusReceived();
            } else { // Rejected
                $status = $this->config->getReturnStatusRejected();
            }
            $returnRequest = $this->returnRequestRepository->getById($returnCegid->getRequestId());
            $returnRequest->setStatus($status);
            $this->returnRequestRepository->save($returnRequest);

            $returnCegid->setStatus(ReturnAction::STATUS_SENT_CEGID);
            $this->returnActionRepository->save($returnCegid);
            $this->logger->info('----- Save data return status success----- ' );

        } catch (Exception $exception) {
            $this->logger->error('Call API to Cegid get return' . $returnId . ' status failed: ' . $exception->getMessage());
        }
    }

    /**
     * @param $packageReference
     * @return bool|void
     */
    private function isCegitUnprocess($packageReference)
    {
        if ($packageReference != self::RECEIVED &&  $packageReference != self::REJECT) {
            return true;
        }
        return false;
    }

    /**
     * @param $remainingQuantity
     * @return bool
     */
    private function isCegitReceived($packageReference): bool
    {
        return $packageReference == self::RECEIVED;
    }
}
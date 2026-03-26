<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Model\Request;

use Magento\Framework\Serialize\SerializerInterface;
use Vaimo\AepEventStreaming\Api\AepMapperInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterfaceFactory as RequestFactory;
use Vaimo\AepEventStreaming\Model\AuthToken;
use Vaimo\AepEventStreaming\Model\Request\AbstractSendRequest;
use Vaimo\AepEventStreaming\Service\CustomerId;
use Vaimo\AEPRmaSync\Api\ConfigInterface;
use Vaimo\AEPRmaSync\Service\Rma\AepMapper;
use Amasty\Rma\Api\Data\RequestInterface;

class SendRma extends AbstractSendRequest
{
    private const REQUEST_NAME = 'aep.rma.sync';
    private const CONTENT_TYPE = 'application/vnd.adobe.xed-full+json;version=1.0';

    private RequestInterface $rma;
    private AepMapper $dataMapper;
    private ConfigInterface $config;
    private CustomerId $customerId;

    public function __construct(
        RequestFactory $requestFactory,
        AuthToken $authToken,
        SerializerInterface $serializer,
        RequestInterface $rma,
        AepMapper $dataMapper,
        CustomerId $customerId,
        ConfigInterface $config
    ) {
        parent::__construct($requestFactory, $authToken, $serializer);

        $this->rma = $rma;
        $this->dataMapper = $dataMapper;
        $this->customerId = $customerId;
        $this->config = $config;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getBody(): array
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/rma_header_data.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("------- Rma header Dataaa---------");

        $schemaRefId = $this->config->getSchemaRefId($this->config->getRmaSchemaId());
        $currentDatetime = $this->getCurrentDateTime();

        $returnData  = [
            'header' => [
                'schemaRef' => [
                    'id' => $schemaRefId,
                    'contentType' => self::CONTENT_TYPE,
                ],
                'imsOrgId' => $this->config->getOrganisationId(),
                'datasetId' => $this->config->getRmaDatasetId(),
                'flowId' => $this->config->getRmaFlowId(),
                'source' => [
                    'name' => 'Return Order DataFlow',
                ],
            ],
            'body' => [
                'xdmMeta' => [
                    'schemaRef' => [
                        'id' => $schemaRefId,
                        'contentType' => self::CONTENT_TYPE,
                    ],
                ],
                'xdmEntity' => [
                    '_id' => $this->getUuIdV4(),
                    '_onitsukatiger' => [
                        'returnOrders' => $this->dataMapper->map($this->rma),
                        'identity' => [
                            'customerId' => $this->customerId->getById($this->rma->getCustomerId()),
                        ],
                    ],
                    'extSourceSystemAudit' => [
                        'lastUpdatedDate' => $currentDatetime,
                    ],
                    'timestamp' => $currentDatetime,
                ],
            ],
        ];

        $logger->info(print_r($returnData, true));

        return $returnData;
    }

    private function getCurrentDateTime(): string
    {
        $date = new \DateTime();

        return $date->format(AepMapperInterface::AEP_DATETIME_FORMAT);
    }

    protected function getUri(): string
    {
        return $this->config->getRmaEndpoint();
    }

    protected function getRequestName(): string
    {
        return self::REQUEST_NAME;
    }

    public function getUuIdV4()
    {
        $uuId = sprintf('%05d-%05d-%05d-%05d',
            mt_rand( 0, 99999),
            mt_rand( 0, 99999),
            mt_rand( 0, 99999),
            mt_rand( 0, 99999)
        );

        return $uuId;
    }
}
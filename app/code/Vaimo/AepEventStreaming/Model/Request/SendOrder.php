<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Request;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Vaimo\AepEventStreaming\Api\AepMapperInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterfaceFactory as RequestFactory;
use Vaimo\AepEventStreaming\Model\AuthToken;
use Vaimo\AepEventStreaming\Service\CustomerId;
use Vaimo\AepEventStreaming\Service\Order\AepMapper;

class SendOrder extends AbstractSendRequest
{
    private const REQUEST_NAME = 'aep.order.sync';
    private const CONTENT_TYPE = 'application/vnd.adobe.xed-full+json;version=1.0';

    private OrderInterface $order;
    private AepMapper $dataMapper;
    private ConfigInterface $config;
    private CustomerId $customerId;

    public function __construct(
        RequestFactory $requestFactory,
        AuthToken $authToken,
        SerializerInterface $serializer,
        OrderInterface $order,
        AepMapper $dataMapper,
        CustomerId $customerId,
        ConfigInterface $config
    ) {
        parent::__construct($requestFactory, $authToken, $serializer);

        $this->order = $order;
        $this->dataMapper = $dataMapper;
        $this->customerId = $customerId;
        $this->config = $config;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getBody(): array
    {
        $schemaRefId = $this->config->getSchemaRefId($this->config->getOrderSchemaId());
        $currentDatetime = $this->getCurrentDateTime();

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/order_aep_header_data.log");
        $logger = new \Zend_Log();

        $logger->addWriter($writer);
        $logger->info("---send order header---");

        $headerOrderData  = [
            'header' => [
                'schemaRef' => [
                    'id' => $schemaRefId,
                    'contentType' => self::CONTENT_TYPE,
                ],
                'imsOrgId' => $this->config->getOrganisationId(),
                'datasetId' => $this->config->getOrderDatasetId(),
                'flowId' => $this->config->getOrderFlowId(),
                'source' => [
                    'name' => 'Sales Order DataFlow',
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
                        'orders' => $this->dataMapper->map($this->order),
                        'identity' => [
                            'customerId' => $this->customerId->getById($this->order->getCustomerId()),
                            //'hasedEmailAddress' => $this->order->getCustomerEmail(),
                        ],
                    ],
                    'extSourceSystemAudit' => [
                        'lastUpdatedDate' => $currentDatetime,
                    ],
                    'timestamp' => $currentDatetime,
                ],
            ],
        ];

        $logger->info(print_r($headerOrderData, true));
        
        return $headerOrderData;
    }

    private function getCurrentDateTime(): string
    {
        $date = new \DateTime();

        return $date->format(AepMapperInterface::AEP_DATETIME_FORMAT);
    }

    protected function getUri(): string
    {
        return $this->config->getOrderEndpoint();
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

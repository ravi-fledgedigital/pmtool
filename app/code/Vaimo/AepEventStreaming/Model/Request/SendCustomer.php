<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

/**
 * @codeCoverageIgnore
 * @codingStandardsIgnoreFile
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Request;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterfaceFactory as RequestFactory;
use Vaimo\AepEventStreaming\Model\AuthToken;
use Vaimo\AepEventStreaming\Service\Customer\AepMapper;
use Vaimo\AepEventStreaming\Service\CustomerId;

class SendCustomer extends AbstractSendRequest
{
    private const REQUEST_NAME = 'aep.customer.sync';

    private const CONTENT_TYPE = 'application/vnd.adobe.xed-full+json;version=1.0';

    private CustomerInterface $customer;
    private AepMapper $dataMapper;
    private ConfigInterface $config;
    private CustomerId $customerId;

    public function __construct(
        RequestFactory $requestFactory,
        AuthToken $authToken,
        SerializerInterface $serializer,
        CustomerInterface $customer,
        AepMapper $dataMapper,
        CustomerId $customerId,
        ConfigInterface $config
    ) {
        parent::__construct($requestFactory, $authToken, $serializer);

        $this->customer = $customer;
        $this->dataMapper = $dataMapper;
        $this->customerId = $customerId;
        $this->config = $config;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getBody(): array
    {
        $schemaRefId = $this->config->getSchemaRefId($this->config->getCustomerSchemaId());

        $headerData  = [
            'header' => [
                'schemaRef' => [
                    'id' => $schemaRefId,
                    'contentType' => self::CONTENT_TYPE,
                ],
                'imsOrgId' => $this->config->getOrganisationId(),
                'datasetId' => $this->config->getCustomerDatasetId(),
                'flowId' => $this->config->getCustomerFlowId(),
                'source' => [
                    'name' => 'Customer Data Flow',
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
                    '_onitsukatiger' => [
                        'customer' => $this->dataMapper->map($this->customer),
                        'identity' => [
                            'customerId' => $this->customerId->get($this->customer),
                            'hasedEmailAddress' => $this->customer->getEmail(),
                            //'emailAddress' => $this->customer->getEmail(),
                        ],
                    ],
                ],
            ],
        ];

        return $headerData;
    }

    protected function getUri(): string
    {
        return $this->config->getCustomerEndpoint();
    }

    protected function getRequestName(): string
    {
        return self::REQUEST_NAME;
    }
}
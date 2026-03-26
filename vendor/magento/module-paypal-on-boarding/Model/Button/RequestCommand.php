<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model\Button;

use Laminas\Http\Response;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Place request for getting urls from Middleman application
 */
class RequestCommand
{
    /**
     * @var CurlFactory
     */
    private CurlFactory $clientFactory;

    /**
     * @var ResponseValidator
     */
    private ResponseValidator $responseButtonValidator;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CurlFactory $clientFactory
     * @param ResponseValidator $responseButtonValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        CurlFactory $clientFactory,
        ResponseValidator $responseButtonValidator,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseButtonValidator = $responseButtonValidator;
        $this->logger = $logger;
    }

    /**
     * Place http request
     *
     * @param string $host
     * @param array $requestParams
     * @param array $responseFields fields should be present in response
     * @return string
     * @throws ValidatorException
     */
    public function execute(string $host, array $requestParams, array $responseFields): string
    {
        /** @var Curl $client */
        $client = $this->clientFactory->create();
        $queryString = http_build_query($requestParams);
        $url = $queryString !== '' ? $host . '?' . $queryString : $host;

        $result = '';
        try {
            $client->get($url);
            $response = new Response();
            $response->setStatusCode($client->getStatus());
            $response->setContent($client->getBody());
            $this->responseButtonValidator->validate(
                $response,
                $responseFields
            );
            $result = $response->getBody();
        } catch (ValidatorException|\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}

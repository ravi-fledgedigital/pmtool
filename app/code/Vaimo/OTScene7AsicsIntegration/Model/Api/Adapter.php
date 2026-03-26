<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model\Api;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Vaimo\OTScene7AsicsIntegration\Model\ConfigProvider;

class Adapter
{
    private ClientFactory $clientFactory;
    private ConfigProvider $configProvider;
    private SerializerInterface $serializer;

    private ?string $token = null;

    public function __construct(
        ClientFactory $clientFactory,
        ConfigProvider $configProvider,
        SerializerInterface $serializer
    ) {
        $this->clientFactory = $clientFactory;
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
    }

    /**
     * @param string $resource
     * @param string[] $params
     * @return mixed[]
     * @throws AsicsApiException
     * @throws AuthenticationException
     */
    public function requestGet(string $resource, array $params = []): array
    {
        $client = $this->clientFactory->create();
        $client->addHeader('authorization', $this->getToken());
        $url = $this->configProvider->getBaseUrl() . $resource;

        if (!empty($params)) {
            $url .= '?' . \http_build_query($params);
        }

        $client->get($url);

        if ($client->getStatus() === 403) {
            $this->token = null;
            $client->addHeader('authorization', $this->getToken());
            $client->get($url);
        }

        if ($client->getStatus() !== 200) {
            throw new AsicsApiException(\__('ASICS Api returned %1 response', $client->getStatus()));
        }

        return $this->serializer->unserialize($client->getBody());
    }

    /**
     * @return string|null
     * @throws AuthenticationException
     */
    private function getToken(): ?string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $client = $this->clientFactory->create();
        $client->setHeaders([
            'auth_user' => $this->configProvider->getAuthUser(),
            'auth_pass' => $this->configProvider->getAuthPass(),
        ]);

        $client->get($this->configProvider->getBaseUrl() . '/auth/gettoken');
        if ($client->getStatus() != 200) {
            throw new AuthenticationException(\__("Can't get Asics API Token"));
        }

        $body = $this->serializer->unserialize($client->getBody());
        if (!isset($body['tokenId'])) {
            throw new AuthenticationException(\__("Can't get Asics API Token"));
        }

        $this->token = $body['tokenId'];

        return $this->token;
    }
}

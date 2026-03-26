<?php
declare(strict_types=1);

namespace OnitsukaTiger\AepEventStreaming\Plugin\Model\Request;

use OnitsukaTiger\AepEventStreaming\Model\ConfigProvider;
use Magento\Framework\Encryption\EncryptorInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterface;
use Vaimo\AepEventStreaming\Model\Request\GetToken as Subject;
use Vaimo\AepEventStreaming\Api\Data\RequestInterfaceFactory as RequestFactory;

class GetToken
{
    private const REQUEST_NAME = 'aep.getToken';

    private const REQUEST_GROUP = 'aep';

    private const HTTP_METHOD = 'POST';

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var RequestFactory
     */
    private RequestFactory $requestFactory;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * Constructor
     *
     * @param ConfigProvider $configProvider
     * @param RequestFactory $requestFactory
     * @param ConfigInterface $config
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ConfigProvider $configProvider,
        RequestFactory $requestFactory,
        ConfigInterface $config,
        EncryptorInterface $encryptor
    ) {
        $this->configProvider = $configProvider;
        $this->requestFactory = $requestFactory;
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * Around plugin for build request method for updating URL and body
     *
     * @param Subject $subject
     * @param callable $proceed
     * @return RequestInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundBuildRequest(Subject $subject, callable $proceed)
    {
        if ($this->configProvider->isEnabled()) {
            $result = $this->requestFactory->create([
                'method' => $this->getMethod(),
                'uri' => $this->configProvider->getEndPointUrl(),
                'headers' => $this->getHeaders(),
                'requestName' => self::REQUEST_NAME,
                'requestGroup' => self::REQUEST_GROUP,
                'body' => \http_build_query($this->getBody(), '', '&'),
            ]);
        } else {
            $result = $proceed();
        }
        return $result;
    }

    /**
     * Get headers
     *
     * @return string[]
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cache-Control' => 'no-cache',
        ];
    }

    /**
     * Get method
     *
     * @return string
     */
    private function getMethod(): string
    {
        return self::HTTP_METHOD;
    }

    /**
     * Get body
     *
     * @return array
     */
    public function getBody(): array
    {
        return [
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->encryptor->decrypt($this->config->getClientSecret()),
            'grant_type' => $this->configProvider->getGrantType(),
            'scope' => $this->configProvider->getScope(),
        ];
    }
}

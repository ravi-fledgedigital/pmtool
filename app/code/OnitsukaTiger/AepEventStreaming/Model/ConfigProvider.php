<?php
declare(strict_types=1);

namespace OnitsukaTiger\AepEventStreaming\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    public const OAUTH_ENABLE = 'aep_event_streaming/oauth/enable';
    public const OAUTH_ENDPOINT = 'aep_event_streaming/oauth/endpoint';
    public const OAUTH_GRANT_TYPE = 'aep_event_streaming/oauth/grant_type';
    public const OAUTH_SCOPE = 'aep_event_streaming/oauth/scope';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    /**
     * Is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::OAUTH_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get end point url
     *
     * @return string
     */
    public function getEndPointUrl(): string
    {
        return $this->scopeConfig->getValue(self::OAUTH_ENDPOINT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get grant type
     *
     * @return string
     */
    public function getGrantType(): string
    {
        return $this->scopeConfig->getValue(self::OAUTH_GRANT_TYPE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scopeConfig->getValue(self::OAUTH_SCOPE, ScopeInterface::SCOPE_STORE);
    }
}

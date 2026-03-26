<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Config;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookRule;
use Magento\Framework\Config\ConverterInterface;

/**
 * Converts data from webhooks.xml files to the array of webhooks configurations
 */
class Converter implements ConverterInterface
{
    public const ELEMENT_BATCH = 'batch';
    public const ELEMENT_HOOK = 'hook';
    public const ELEMENT_HEADER = 'header';
    public const ELEMENT_FIELD = 'field';
    public const ELEMENT_RULE = 'rule';
    public const ELEMENT_DEVELOPER_CONSOLE_OAUTH = 'developerConsoleOauth';

    /**
     * @param RuleNameGenerator $ruleNameGenerator
     */
    public function __construct(
        private RuleNameGenerator $ruleNameGenerator
    ) {
    }

    /**
     * Convert dom node tree to array
     *
     * @param DOMDocument $source
     * @return array
     * @throws InvalidArgumentException
     */
    public function convert($source)
    {
        $output = [];
        $methods = $source->getElementsByTagName('method');
        /** @var DOMElement $methodElement */
        foreach ($methods as $methodElement) {
            $method = [
                Webhook::NAME => $methodElement->getAttribute(Webhook::NAME),
                Webhook::TYPE => $methodElement->getAttribute(Webhook::TYPE),
            ];
            foreach ($methodElement->getElementsByTagName(self::ELEMENT_BATCH) as $batchElement) {
                $batch = $this->processBatch($batchElement);
                $method[Webhook::BATCHES][$batch[Batch::NAME]] = $batch;
            }
            $output[] = $method;
        }

        return $output;
    }

    /**
     * Processes method batch and return as an array
     *
     * @param DOMElement $batchElement
     * @return array
     */
    private function processBatch(DOMElement $batchElement): array
    {
        $batch = [
            Batch::ORDER => $batchElement->getAttribute(Batch::ORDER),
            Batch::NAME => $batchElement->getAttribute(Batch::NAME),
        ];

        /** @var DOMElement $hookElement */
        foreach ($batchElement->getElementsByTagName(self::ELEMENT_HOOK) as $hookElement) {
            $hook = [
                Hook::NAME => $hookElement->getAttribute(Hook::NAME),
                Hook::URL => $hookElement->getAttribute(Hook::URL),
                Hook::METHOD => $hookElement->getAttribute(Hook::METHOD),
                Hook::PRIORITY => $hookElement->getAttribute(Hook::PRIORITY),
                Hook::SOFT_TIMEOUT => $hookElement->getAttribute(Hook::SOFT_TIMEOUT),
                Hook::TIMEOUT => $hookElement->getAttribute(Hook::TIMEOUT),
                Hook::REQUIRED => $hookElement->getAttribute(Hook::REQUIRED),
                Hook::FALLBACK_ERROR_MESSAGE => $hookElement->getAttribute(Hook::FALLBACK_ERROR_MESSAGE),
                Hook::TTL => $hookElement->getAttribute(Hook::TTL),
                Hook::SSL_VERIFICATION => $hookElement->getAttribute(Hook::SSL_VERIFICATION),
                Hook::SSL_CERTIFICATE_PATH => $hookElement->getAttribute(Hook::SSL_CERTIFICATE_PATH),
                Hook::REMOVE => $hookElement->getAttribute(Hook::REMOVE),
                Hook::HEADERS => $this->processHookHeaders($hookElement),
                Hook::FIELDS => $this->processHookFields($hookElement),
                Hook::RULES => $this->processHookRules($hookElement),
                Hook::XML_DEFINED => true
            ];

            $batch[Batch::HOOKS][$hook[Hook::NAME]] = $this->appendDeveloperConsoleOauth($hookElement, $hook);
        }

        return $batch;
    }

    /**
     * Processes hook headers and return as an array
     *
     * @param DOMElement $hookElement
     * @return array
     */
    private function processHookHeaders(DOMElement $hookElement): array
    {
        $headers = [];

        /** @var DOMElement $headerElement */
        foreach ($hookElement->getElementsByTagName(self::ELEMENT_HEADER) as $headerElement) {
            $header = [
                HookHeader::NAME => $headerElement->getAttribute(HookHeader::NAME) ?: null,
                HookHeader::VALUE => $headerElement->nodeValue,
                HookHeader::RESOLVER => $headerElement->getAttribute(HookHeader::RESOLVER) ?: null,
                HookHeader::REMOVE => $headerElement->getAttribute(HookHeader::REMOVE),
                HookHeader::XML_DEFINED => true,
            ];
            $headerKey = $header[HookHeader::RESOLVER] ?: $header[HookHeader::NAME];
            $headers[$headerKey] = $header;
        }

        return $headers;
    }

    /**
     * Processes hook fields and return as an array
     *
     * @param DOMElement $hookElement
     * @return array
     */
    private function processHookFields(DOMElement $hookElement): array
    {
        $fields = [];

        /** @var DOMElement $fieldElement */
        foreach ($hookElement->getElementsByTagName(self::ELEMENT_FIELD) as $fieldElement) {
            $fieldName = $fieldElement->getAttribute(HookField::NAME);
            $fields[$fieldName] = [
                HookField::NAME => $fieldName,
                HookField::SOURCE => $fieldElement->getAttribute(HookField::SOURCE) ?: null,
                HookField::CONVERTER => $fieldElement->getAttribute(HookField::CONVERTER) ?: null,
                HookField::REMOVE => $fieldElement->getAttribute(HookField::REMOVE),
                HookField::XML_DEFINED => true,
            ];
        }

        return $fields;
    }

    /**
     * Processes hook rules and return as an array
     *
     * @param DOMElement $hookElement
     * @return array
     */
    private function processHookRules(DOMElement $hookElement): array
    {
        $rules = [];

        /** @var DOMElement $ruleElement */
        foreach ($hookElement->getElementsByTagName(self::ELEMENT_RULE) as $ruleElement) {
            $field = $ruleElement->getAttribute(HookRule::FIELD);
            $operator = $ruleElement->getAttribute(HookRule::OPERATOR);
            $rules[$this->ruleNameGenerator->generate($field, $operator)] = [
                HookRule::FIELD => $field,
                HookRule::VALUE => $ruleElement->hasAttribute(HookRule::VALUE) ?
                    $ruleElement->getAttribute(HookRule::VALUE) : null,
                HookRule::OPERATOR => $operator,
                HookRule::REMOVE => $ruleElement->getAttribute(HookRule::REMOVE),
                HookRule::XML_DEFINED => true,
            ];
        }

        return $rules;
    }

    /**
     * Append developer console oauth to hook data array
     *
     * @param DOMElement $hookElement
     * @param array $hook
     * @return array
     */
    private function appendDeveloperConsoleOauth(DOMElement $hookElement, array $hook): array
    {
        $oauthElement = $hookElement->getElementsByTagName(self::ELEMENT_DEVELOPER_CONSOLE_OAUTH)->item(0);
        if ($oauthElement instanceof DOMElement) {
            $hook[DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID] =
                $oauthElement->getElementsByTagName('clientId')?->item(0)?->nodeValue;
            $hook[DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET] =
                $oauthElement->getElementsByTagName('clientSecret')?->item(0)?->nodeValue;
            $hook[DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID] =
                $oauthElement->getElementsByTagName('orgId')?->item(0)?->nodeValue;
            $hook[DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED] = true;
        }

        return $hook;
    }
}

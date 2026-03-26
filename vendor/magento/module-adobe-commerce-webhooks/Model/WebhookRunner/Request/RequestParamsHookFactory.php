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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request;

use Magento\AdobeCommerceWebhooks\Model\Config\VariablesResolverInterface;
use Magento\AdobeCommerceWebhooks\Model\Filter\FieldProcessorInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\HookHeaderResolverInterface;
use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Creates a RequestParams object based on Hook and webhook data.
 */
class RequestParamsHookFactory
{
    /**
     * @param FieldProcessorInterface $fieldProcessor
     * @param HookHeaderResolverInterface $hookHeaderResolver
     * @param VariablesResolverInterface $variablesResolver
     * @param RequestParamsFactory $requestParamsFactory
     * @param SensitiveDataSanitizerInterface $sensitiveDataSanitizer
     */
    public function __construct(
        private FieldProcessorInterface $fieldProcessor,
        private HookHeaderResolverInterface $hookHeaderResolver,
        private VariablesResolverInterface $variablesResolver,
        private RequestParamsFactory $requestParamsFactory,
        private SensitiveDataSanitizerInterface $sensitiveDataSanitizer
    ) {
    }

    /**
     * Creates a RequestParams object based on Hook and webhook data.
     *
     * Sanitizes sensitive data in webhook payload.
     *
     * @param Hook $hook
     * @param array $webhookData
     * @return RequestParams
     * @throws InvalidArgumentException
     */
    public function create(Hook $hook, array $webhookData): RequestParams
    {
        $resolvedUrl = $this->variablesResolver->resolve($hook->getUrl());
        $sanitizedWebhookData = $this->sensitiveDataSanitizer->sanitize($webhookData);
        $filteredData = $this->fieldProcessor->process($sanitizedWebhookData, $hook->getFields());
        $headers = $this->hookHeaderResolver->resolve($hook, $filteredData);

        return $this->requestParamsFactory->create(
            $resolvedUrl,
            $headers,
            $filteredData
        );
    }
}

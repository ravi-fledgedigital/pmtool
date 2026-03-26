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

namespace Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\Resolver;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestIdInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\VariablesResolverInterface;
use Magento\AdobeCommerceWebhooks\Model\Filter\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\HookHeaderResolverInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\ResolverFactory;
use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Returns a list of hook headers based on hook configuration
 */
class HookHeaderResolver implements HookHeaderResolverInterface
{
    /**
     * @param ResolverFactory $resolverFactory
     * @param VariablesResolverInterface $variablesResolver
     * @param RequestIdInterface $requestId
     * @param ContextPool $contextPool
     * @param ContextRetriever $contextRetriever
     */
    public function __construct(
        private ResolverFactory $resolverFactory,
        private VariablesResolverInterface $variablesResolver,
        private RequestIdInterface $requestId,
        private ContextPool $contextPool,
        private ContextRetriever $contextRetriever
    ) {
    }

    /**
     * Returns a list of hook headers based on hook configuration
     *
     * @param Hook $hook
     * @param array $hookData
     * @return array
     * @throws InvalidArgumentException in case if resolver class creation failed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Hook $hook, array $hookData): array
    {
        $headers = [
            RequestIdInterface::REQUEST_ID_HEADER => $this->requestId->get()
        ];

        foreach ($hook->getHeaders() as $hookHeader) {
            if ($hookHeader->shouldRemove()) {
                continue;
            }

            if (!empty($hookHeader->getResolver())) {
                $resolver = $this->resolverFactory->create($hookHeader->getResolver());
                $headers = array_replace($headers, $resolver->getHeaders());
            } else {
                $value = $hookHeader->getValue();
                $valueParts = explode('.', $value);

                if ($this->contextPool->has($valueParts[0])) {
                    $headers[$hookHeader->getName()] = $this->contextRetriever->getContextValue($value, $hook);
                } else {
                    $headers[$hookHeader->getName()] = $this->variablesResolver->resolve($value);
                }
            }
        }

        return $headers;
    }
}

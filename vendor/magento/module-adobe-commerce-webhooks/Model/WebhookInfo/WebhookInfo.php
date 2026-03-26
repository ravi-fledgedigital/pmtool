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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookInfo;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Framework\Exception\LocalizedException;

/**
 * Returns info about webhook payload
 */
class WebhookInfo
{
    public const NESTED_LEVEL = ClassToArrayConverterInterface::NESTED_LEVEL;

    /**
     * @param ArgumentCollectorInterface $argumentCollector
     * @param ClassToArrayConverterInterface $classToArrayConverter
     * @param PredefinedWebhookInfoInterface $predefinedWebhookInfo
     */
    public function __construct(
        private ArgumentCollectorInterface $argumentCollector,
        private ClassToArrayConverterInterface $classToArrayConverter,
        private PredefinedWebhookInfoInterface $predefinedWebhookInfo
    ) {
    }

    /**
     * Returns an info about webhook payload.
     *
     * @param Webhook $webhook
     * @param int $nestedLevel
     * @return array
     * @throws LocalizedException
     */
    public function getInfo(Webhook $webhook, int $nestedLevel = self::NESTED_LEVEL): array
    {
        if ($webhookInfo = $this->predefinedWebhookInfo->get($webhook)) {
            return $webhookInfo;
        }

        $params = $this->argumentCollector->collect($webhook);

        return $this->processParams($params, $nestedLevel);
    }

    /**
     * Process array of parameters with type and name to their array representations.
     *
     * @param array $params
     * @param int $nestedLevel
     * @return array
     */
    private function processParams(array $params, int $nestedLevel): array
    {
        $result = [];
        foreach ($params as $name => $param) {
            if (is_array($param)
                && !isset($param[ArgumentCollector::PARAM_TYPE])
                && !isset($param[ArgumentCollector::PARAM_NAME])
            ) {
                $result[$name] = $this->processParams($param, $nestedLevel);
                continue;
            }

            if (is_string($param[ArgumentCollector::PARAM_TYPE])
                && str_contains($param[ArgumentCollector::PARAM_TYPE], '\\')
            ) {
                $result[$param[ArgumentCollector::PARAM_NAME]] = $this->classToArrayConverter->convert(
                    $param[ArgumentCollector::PARAM_TYPE],
                    $nestedLevel
                );
            } else {
                $result[$param[ArgumentCollector::PARAM_NAME]] = $param[ArgumentCollector::PARAM_TYPE];
            }
        }

        return $this->clearEmptySubject($result);
    }

    /**
     * Returns webhook payload info in json format.
     *
     * @param Webhook $webhook
     * @param int $nestedLevel
     * @return string
     * @throws LocalizedException
     */
    public function getJsonInfo(Webhook $webhook, int $nestedLevel = self::NESTED_LEVEL): string
    {
        return str_replace('\\\\', '\\', json_encode($this->getInfo($webhook, $nestedLevel), JSON_PRETTY_PRINT));
    }

    /**
     * Removes subject element from the array of params if it's empty.
     *
     * Usually subject can't be converted to the array of data in the generated plugins as it's
     * an Interceptor object.
     *
     * @param array $result
     * @return array
     */
    private function clearEmptySubject(array $result)
    {
        if (isset($result['subject']) && empty($result['subject'])) {
            unset($result['subject']);
        }

        return $result;
    }
}

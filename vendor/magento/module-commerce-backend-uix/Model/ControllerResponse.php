<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model;

use Exception;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json as JsonResult;

class ControllerResponse
{
    private const STATUS = 'status';
    private const ERROR_MESSAGE = 'errorMessage';

    /**
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        private ResultFactory $resultFactory
    ) {
    }

    /**
     * Success message to JSON
     *
     * @return JsonResult
     */
    public function getSuccessResponse(): JsonResult
    {
        $responseContent = [
            self::STATUS => 'Success!'
        ];
        return $this->getJsonResponse($responseContent);
    }

    /**
     * Exception message to JSON
     *
     * @param Exception $e
     * @return JsonResult
     */
    public function getErrorResponse(Exception $e): JsonResult
    {
        $responseContent = [
            self::STATUS => 'Error!',
            self::ERROR_MESSAGE => $e->getMessage()
        ];
        return $this->getJsonResponse($responseContent);
    }

    /**
     * Response content to JSON
     *
     * @param array $responseContent
     * @return JsonResult
     */
    private function getJsonResponse(array $responseContent): JsonResult
    {
        /** @var JsonResult $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }
}

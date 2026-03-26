<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Exception;
use InvalidArgumentException;
use Magento\Framework\App;
use Magento\Framework\App\ExceptionHandlerInterface;
use Magento\Framework\App\FrontControllerInterface as FrontController;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\AppInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\Manager;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Application implements AppInterface
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param Manager $eventManager
     * @param Registry $registry
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param Response $response
     * @param RequestProxy $request
     */
    public function __construct(
        private ObjectManagerInterface  $objectManager,
        private Manager $eventManager,
        private Registry $registry,
        private ExceptionHandlerInterface $exceptionHandler,
        private Response $response,
        private RequestProxy $request,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function launch(): HttpInterface
    {
        /** @var FrontController $frontController */
        $frontController = $this->objectManager->create(FrontController::class);
        $response = $frontController->dispatch($this->request);
        $response = $this->handleResponse($response);
        $response = $this->handleHead($this->request, $response);
        $this->dispatchBeforeSendEvent($this->request, $response);
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function catchException(App\Bootstrap $bootstrap, Exception $exception): bool
    {
        return $this->exceptionHandler->handle($bootstrap, $exception, $this->response, $this->request);
    }

    /**
     * Handle response at application launch
     *
     * @param Response $result
     * @return HttpInterface
     */
    private function handleResponse($result): HttpInterface
    {
        // TODO: Temporary solution until all controllers return ResultInterface (MAGETWO-28359);
        if ($result instanceof ResultInterface) {
            $this->registry->register('use_page_cache_plugin', true, true);
            $result->renderResult($this->response);
        } elseif ($result instanceof HttpInterface) {
            $this->response->setContent($result->getContent());
            if ($this->response !== $result) { //do not double headers
                $this->response->getHeaders()->addHeaders($result->getHeaders());
            }
        } else {
            throw new InvalidArgumentException('Invalid return type');
        }
        return $this->response;
    }

    /**
     * Handle head at application launch
     *
     * @param HttpRequestInterface $request
     * @param HttpInterface $response
     * @return HttpInterface
     */
    private function handleHead(HttpRequestInterface $request, HttpInterface $response): HttpInterface
    {
        if ($request->isHead() && $response->getHttpResponseCode() == 200) {
            $contentLength = mb_strlen($response->getContent(), '8bit');
            $response->clearBody();
            $response->setHeader('Content-Length', $contentLength);
        }
        return $response;
    }

    /**
     * Dispatch before sent event at application launch
     *
     * @param mixed $request
     * @param HttpInterface $response
     * @return void
     */
    private function dispatchBeforeSendEvent(mixed $request, HttpInterface $response): void
    {
        // This event gives possibility to launch something before sending output (allow cookie setting)
        $eventParams = ['request' => $request, 'response' => $response];
        $this->eventManager->dispatch('controller_front_send_response_before', $eventParams);
    }
}

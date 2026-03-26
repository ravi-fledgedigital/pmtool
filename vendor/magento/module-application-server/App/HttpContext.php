<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Magento\ApplicationServer\ObjectManager\AppBootstrap as Bootstrap;
use Magento\ApplicationServer\ObjectManager\AppObjectManager;
use Magento\ApplicationServer\ObjectManager\ObjectManagerFactory;
use Magento\ApplicationServer\Plugin\Http as HttpPlugin;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\State\ReloadProcessorInterface;
use Magento\Framework\MessageQueue\PoisonPill\PoisonPillCompareInterface;
use Magento\Framework\MessageQueue\PoisonPill\PoisonPillReadInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\CookieDisablerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server;

/**
 * Class ServerStart
 *
 * Starts the Swoole Application Server
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HttpContext
{
    /**
     * @var string
     */
    private string $poisonPillVersion = '';

    /**
     * ServerStart constructor
     *
     * @param ObjectManagerFactory $objectManagerFactory
     * @param LoggerInterface $logger
     * @param DeploymentConfigChangeChecker $deploymentConfigChangeChecker
     */
    public function __construct(
        private ObjectManagerFactory $objectManagerFactory,
        private LoggerInterface $logger,
        private DeploymentConfigChangeChecker $deploymentConfigChangeChecker,
    ) {
    }

    /**
     * Checks for config changes and reloads if needed.
     *
     * @param ObjectManagerInterface $objectManager
     * @return bool
     */
    private function checkPoisonAndReloadConfigStateIfNeeded(ObjectManagerInterface $objectManager): void
    {
        if ($this->checkConfigHasChanged($objectManager)) {
            $this->reloadConfigState($objectManager);
        }
    }

    /**
     * Checks for config changes via poison pill. Returns true if new version of config.
     *
     * @param ObjectManagerInterface $objectManager
     * @return bool
     */
    private function checkConfigHasChanged(ObjectManagerInterface $objectManager): bool
    {
        /** @var PoisonPillCompareInterface $poisonPillCompare */
        $poisonPillCompare = $objectManager->get(PoisonPillCompareInterface::class);
        $haveDeploymentConfigFilesChanged = $this->deploymentConfigChangeChecker->haveFilesChanged();
        if ($poisonPillCompare->isLatestVersion($this->poisonPillVersion) && !$haveDeploymentConfigFilesChanged) {
            return false;
        }
        return true;
    }

    /**
     * Reloads the config and gets the latest poison pill version.
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    private function reloadConfigState(ObjectManagerInterface $objectManager): void
    {
        $startTime = microtime(true);
        // We update the poison pill version so that we can detect future config changes.
        /** @var PoisonPillReadInterface $poisonPill */
        $poisonPill = $objectManager->get(PoisonPillReadInterface::class);
        $this->poisonPillVersion = $poisonPill->getLatestVersion();
        $reloadProcessor = $objectManager->get(ReloadProcessorInterface::class);
        $reloadProcessor->reloadState();
        $this->logger->log(
            LogLevel::INFO,
            "127.0.0.1"
            . ' "' . strtoupper('RELOAD')
            . ' ' . '/poisonPill/put' . '"'
            . ' ' . Http::STATUS_CODE_205 // Reset Content
            . ' ' . number_format(-$startTime + microtime(true), 3)
            . ' ' . 0
            . ' ' . number_format(\memory_get_usage(true) / 1024.0 / 1024.0, 2)
        );
    }

    /**
     * Run application on Swoole Application Server
     *
     * @param mixed $server
     * @param string $areaCode
     * @param int $tickIntervalMs interval in milliseconds for checking if config has changed
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function attach(mixed $server, string $areaCode, int $tickIntervalMs = 0): void
    {
        $bootstrap = $this->objectManagerFactory->createBootstrap();
        $objectManager = $this->objectManagerFactory->create($areaCode, $bootstrap);
        // Note: We load the config here and get poison pill version before starting any children.
        $this->checkPoisonAndReloadConfigStateIfNeeded($objectManager);
        $server->on(
            "Request",
            function (
                SwooleHttpRequest $request,
                SwooleHttpResponse $response,
            ) use (
                $objectManager,
                $server,
                $bootstrap
            ) {
                $this->processRequest($request, $response, $objectManager, $server, $bootstrap);
            }
        );
        $server->on(
            "WorkerStart",
            function (Server $server, int $workerId) use ($objectManager) {
                $objectManager->_resetState();
            }
        );
        if ($tickIntervalMs > 0) {
            \Swoole\Timer::tick($tickIntervalMs, function () use ($objectManager, $server) {
                if ($this->checkConfigHasChanged($objectManager)) {
                    $this->reloadConfigState($objectManager);
                    $server->reload(); // Kills worker threads and creates new ones.
                    // Note: Workers currently processing a request don't get killed until done, or maxWaitTime
                }
            });
        }
    }

    /**
     * Process request in scope of Application Server
     *
     * @param mixed $request
     * @param mixed $swooleResponse
     * @param ObjectManagerInterface $objectManager
     * @param mixed $server
     * @param Bootstrap $bootstrap
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processRequest(
        $request,
        $swooleResponse,
        ObjectManagerInterface $objectManager,
        $server,
        Bootstrap $bootstrap,
    ) : void {
        $sessionAdapter = null;
        $app = null;
        try {
            $startTime = microtime(true);
            $appRequest = new Request($request);
            ob_start(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $objectManager->get(RequestProxy::class)->setSubject($appRequest);
            $this->checkPoisonAndReloadConfigStateIfNeeded($objectManager);
            /** @var SessionAdapter $sessionAdapter */
            $sessionAdapter = $objectManager->get(SessionAdapter::class);
            $sessionAdapter->startSession($appRequest);
            /** @var Application $app */
            $app = $objectManager->create(
                Application::class,
                [
                    'objectManager' => $objectManager,
                ]
            );
            $response = $app->launch($appRequest);
            $swooleResponse->status($response->getHttpResponseCode());
            /* Note: Session needs to be stored before HTTP response is sent.  Otherwise, race conditions possible.
             * and sessionAdapter->endSession needs to be called before headers are attached to response. */
            $sessionAdapter->endSession($response);
            /** @var CookieDisabler $cookieDisabler */
            $cookieDisabler = $objectManager->get(CookieDisablerInterface::class);
            foreach ($response->getHeaders()->toArray() as $name => $values) {
                if (($name === 'Set-Cookie') && $cookieDisabler->isCookiesDisabled()) {
                    continue;
                }
                $swooleResponse->header($name, $values, false);
            }
            // phpcs:disable Magento2.Functions.DiscouragedFunction
            $swooleResponse->end($response->getContent() . ob_get_clean());
            $this->logRequestProcessingStat($request, $response, $startTime);
        } catch (\Exception $exception) {
            // Note: the status code usually gets overridden by the exception handler, but we set here for other case.
            $swooleResponse->status(Http::STATUS_CODE_500, 'Internal Server Error');
            if ($app) {
                $objectManager->get(HttpPlugin::class)->setSwooleResponse($swooleResponse);
                if (!$app->catchException($bootstrap, $exception)) {
                    $this->logger->log(LogLevel::ERROR, $exception);
                    $swooleResponse->end('Internal Server Error');
                }
            } else {
                $this->logger->log(LogLevel::ERROR, $exception);
                $swooleResponse->end('Internal Server Error');
            }
        } catch (\Throwable $throwable) {
            $this->logger->log(LogLevel::ERROR, $throwable);
            $swooleResponse->status(Http::STATUS_CODE_500, 'Internal Server Error');
            $swooleResponse->end('Internal Server Error');
        } finally {
            try {
                ob_get_clean(); // phpcs:disable Magento2.Functions.DiscouragedFunction
                unset($appRequest, $app, $response);
                $objectManager->_resetState();
                if (null === $sessionAdapter) {
                    $this->logger->log(
                        LogLevel::ERROR,
                        "Exception caught before SessionAdapter. Ending thread."
                    );
                    $server->stop();
                    return;
                }
                $sessionAdapter->unsetSession();
                if ($objectManager != AppObjectManager::getInstance()) {
                    $this->logger->log(
                        LogLevel::ERROR,
                        "Detected Incorrect ObjectManager after response. Ending thread."
                    );
                    $server->stop();
                    return;
                }
            } catch (\Throwable $throwable) {
                $this->logger->log(LogLevel::ERROR, $throwable);
                $server->stop();
                return;
            }
        }
        gc_collect_cycles();
    }

    /**
     * Log request processing statistics
     *
     * @param SwooleHttpRequest $request
     * @param HttpInterface $response
     * @param float $startTime
     * @return void
     */
    private function logRequestProcessingStat(
        SwooleHttpRequest $request,
        HttpInterface $response,
        float $startTime
    ): void {
        $this->logger->log(
            LogLevel::INFO,
            $request->server['remote_addr']
            . ' "' . strtoupper($request->server['request_method'])
            . ' ' . $request->server['request_uri'] . '"'
            . ' ' . $response->getHttpResponseCode()
            . ' ' . number_format(-$startTime + microtime(true), 3)
            . ' ' . number_format(strlen($response->getContent()) / 1024.0, 2)
            . ' ' . number_format(\memory_get_usage(true) / 1024.0 / 1024.0, 2)
        );
        if ($response->getHttpResponseCode() === Http::STATUS_CODE_500) {
            $this->logger->log(LogLevel::ERROR, substr($response->getContent(), 0, 1024));
        }
    }
}

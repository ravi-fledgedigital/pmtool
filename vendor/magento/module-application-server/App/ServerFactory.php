<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Magento\Framework\App\DeploymentConfig;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swoole\Http\Server;
use Swoole\Process;

/**
 * Factory for Swoole HTTP Server
 */
class ServerFactory
{
    /**
     * @see https://www.php.net/manual/en/pcntl.constants.php
     */
    private const SIGUSR2 = 12;

    /**
     * Configuration path for application sever arguments
     */
    private const CONFIG_PATH = 'application_server/server_config';

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create server instance
     *
     * @see https://openswoole.com/docs/modules/swoole-http-server-doc
     * @param int $port
     * @param array $params
     * @return Server
     *
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function create(int $port, array $params): Server
    {
        $server = new Server("0.0.0.0", $port);
        $configuration = array_merge((array) $this->deploymentConfig->get(self::CONFIG_PATH, []), $params);
        $server->set($configuration);
        $server->on("Start", function (Server $server) {
            try {
                Process::signal(self::SIGUSR2, function () {
                    if (function_exists('meminfo_dump')) { // this functionality require meminfo php extension
                        $fileName = uniqid(sys_get_temp_dir() . '/mem_dump') . '.mem';
                        $this->logger->log(LogLevel::NOTICE, "ApplicationServer meminfo_dump into $fileName");
                        $fd = fopen($fileName, 'w');
                        meminfo_dump($fd);
                        fclose($fd);
                    }
                });
            } catch (\Throwable $e) {
                $this->logger->error($e);
            }
        });

        $server->on('AfterReload', function () {
            if (\function_exists('opcache_reset')) {
                opcache_reset();
            }
        });
        return $server;
    }
}

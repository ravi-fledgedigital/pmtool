<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\ApplicationServer\ObjectManager\AppBootstrap as Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManager\ConfigLoaderInterface;
use Magento\Framework\ObjectManagerInterface;

class ObjectManagerFactory
{
    //phpcs:disable Magento2.PHP.LiteralNamespaces
    /**
     * ObjectManager preferences that we set
     */
    private const PREFERENCES = [
        \Magento\Framework\Stdlib\CookieManagerInterface::class => \Magento\ApplicationServer\App\CookieManager::class,
        \Magento\Framework\Stdlib\CookieDisablerInterface::class =>
            \Magento\ApplicationServer\App\CookieDisabler::class,
        \Magento\Framework\Stdlib\Cookie\CookieReaderInterface::class =>
            \Magento\ApplicationServer\App\CookieManager::class,
        \Magento\Framework\App\RequestInterface::class => \Magento\ApplicationServer\App\RequestProxy::class,
        \Magento\Framework\App\PlainTextRequestInterface::class => \Magento\ApplicationServer\App\RequestProxy::class,
        \Magento\Framework\App\RequestSafetyInterface::class => \Magento\ApplicationServer\App\RequestProxy::class,
        \Magento\Framework\App\RequestContentInterface::class => \Magento\ApplicationServer\App\RequestProxy::class,
        \Magento\Framework\HTTP\PhpEnvironment\Request::class => \Magento\ApplicationServer\App\RequestProxy::class,
        \Magento\Framework\App\Request\Http::class => \Magento\ApplicationServer\App\RequestProxy::class,
        \Magento\Framework\HTTP\PhpEnvironment\Response::class => \Magento\Framework\App\Response\Http::class,
        \Magento\Framework\Session\SessionStartChecker::class =>
            \Magento\ApplicationServer\App\AppServerSessionStartChecker::class,
        \Magento\Framework\Webapi\ErrorProcessor::class =>
            \Magento\Framework\Webapi\RequestAwareErrorProcessor::class,
        \Magento\Eav\Model\Config::class =>
            \Magento\ApplicationServer\Eav\Model\Config\ClearWithoutCleaningCache::class,
        \Magento\Framework\Search\Request\Config::class =>
            \Magento\ApplicationServer\Framework\Search\Request\Config\ReinitData::class,
    ];

    /**
     * Loads bootstrap.
     *
     * @return Bootstrap
     */
    public function createBootstrap(): Bootstrap
    {
        // phpcs:ignore Magento2.Security.Superglobal
        return Bootstrap::create(BP, $_SERVER);
    }

    /**
     * Loads ObjectManager for given area.
     *
     * @param string $areaCode
     * @param Bootstrap $bootstrap
     * @return ObjectManagerInterface
     */
    public function create(string $areaCode, Bootstrap $bootstrap): ObjectManagerInterface
    {
        $globalObjectManager = $bootstrap->getObjectManager();
        $config = $globalObjectManager->get(ConfigLoaderInterface::class)->load($areaCode);
        $globalObjectManager->configure($config);
        $globalObjectManager->configure([AppObjectManager::RUNTIME_PREFERENCES => self::PREFERENCES]);
        /* Note: We need to modify virtual type for Magento\Framework\GraphQl\Config\Data.
         * But.... Virtual types don't use preferences (MAGETWO-64162)
         * Therefore, we have to use these two workarounds.  One for Dynamic mode, one for Compiled mode. */
        // Modify virtual type for Dynamic ObjectManager Config
        // @phpstan-ignore-next-line
        $globalObjectManager->configure([\Magento\Framework\GraphQl\Config\Data::class => [
            'type' => \Magento\ApplicationServer\Framework\Config\Data\ReinitData::class
        ]]);
        // Modify virtual type for Compiled ObjectManager Config
        $globalObjectManager->configure(['instanceTypes' => [
            // @phpstan-ignore-next-line
            \Magento\Framework\GraphQl\Config\Data::class =>
                \Magento\ApplicationServer\Framework\Config\Data\ReinitData::class
        ]]);
        $globalObjectManager->get(State::class)->setAreaCode($areaCode);
        return $globalObjectManager;
    }
}

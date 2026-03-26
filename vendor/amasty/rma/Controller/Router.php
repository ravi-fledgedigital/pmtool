<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Router\Base;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Router implements RouterInterface
{
    public const RMA_URL_SYSTEM_ROUTE = 'amasty_rma';

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * Router constructor.
     *
     * @param ActionFactory $actionFactory
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConfigProvider $configProvider
    ) {
        $this->actionFactory = $actionFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * @param RequestInterface $request
     *
     * @return ActionInterface|false
     * @throws NoSuchEntityException
     */
    public function match(RequestInterface $request)
    {
        $identifier = explode(DIRECTORY_SEPARATOR, trim($request->getPathInfo(), DIRECTORY_SEPARATOR));
        $compareUrl = $this->getPathUrlFromSetting();

        if (isset($identifier[0]) && ($compareUrl == $identifier[0])) {
            if (count($identifier) === 1) {
                $newPathInfo = Base::NO_ROUTE;
            } else {
                $newPathInfo = str_replace(
                    '/' . $compareUrl . '/',
                    '/' . self::RMA_URL_SYSTEM_ROUTE . '/',
                    $request->getPathInfo()
                );
            }

            $request->setPathInfo($newPathInfo);

            return $this->actionFactory->create(
                Forward::class,
                ['request' => $request]
            );
        }

        return false;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getPathUrlFromSetting()
    {
        return trim(
            $this->configProvider->getUrlPrefix(),
            DIRECTORY_SEPARATOR
        ) ? : "rma";
    }
}

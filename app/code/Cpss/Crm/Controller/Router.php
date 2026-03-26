<?php
/**
 * Copyright © Crm All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cpss\Crm\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

class Router implements RouterInterface
{

    protected $transportBuilder;
    protected $actionFactory;

    /**
     * Router constructor
     *
     * @param ActionFactory $actionFactory
     */
    public function __construct(ActionFactory $actionFactory)
    {
        $this->actionFactory = $actionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function match(RequestInterface $request)
    {
        $result = null;

        if ($request->getModuleName() != 'cpss_crm ' && $this->validateRoute($request)) {
            $request->setModuleName('cpss_crm')
                ->setControllerName('index')
                ->setActionName('index');
            $result = $this->actionFactory->create(Forward::class);
        }
        return $result;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function validateRoute(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        return strpos($identifier, 'customer') !== false || strpos($identifier, 'crm') !== false;
    }
}

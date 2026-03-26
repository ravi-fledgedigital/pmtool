<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\ParametersInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\PathInfoProcessorInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\Stdlib\StringUtils;

class Request extends \Magento\Framework\App\Request\Http
{
    /**
     * @var \Traversable
     */
    private $cookies = [];

    /**
     * phpcs:disable Magento2.Annotation.MethodArguments.NoTypeSpecified
     * @param $request
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct($request)
    {
        $objectManager = ObjectManager::getInstance();
        parent::__construct(
            $objectManager->get(CookieManager::class),
            $objectManager->get(StringUtils::class),
            $objectManager->get(ConfigInterface::class),
            $objectManager->get(PathInfoProcessorInterface::class),
            $objectManager,
        );
        $this->setEnv(new Parameters($_ENV)); // phpcs:ignore Magento2.Security.Superglobal
        $httpParams = [];
        foreach ($request->header ?? [] as $name => $val) {
            $httpParams['http_' . \strtolower($name)]  = $val;
        }
        if (!empty($request->getContent())) {
            $this->setContent($request->getContent());
        } elseif (!empty($request->server['query_string'])) {
            $this->setContent(urldecode($request->server['query_string']));
        }
        $this->setServer(new Parameters($request->server + $httpParams));
        $this->setMethod($request->getMethod() ?? 'GET');
        if (isset($request->server['request_uri'])) {
            $uri = !\str_contains($request->server['request_uri'], '?') && !empty($request->server['query_string'])
                ? $request->server['request_uri']  . '?' . $request->server['query_string']
                : $request->server['request_uri'];
            $this->setUri($uri);
        }

        if (!empty($request->get)) {
            $this->setQuery(new Parameters($request->get));
        }
        if (!empty($request->post)) {
            $this->setPost(new Parameters($request->post));
        }
        $this->getHeaders()->addHeaders($request->header);
        if (!empty($request->cookie)) {
            $this->setCookies($request->cookie);
        } else {
            $cookie = $this->getHeaders('Cookie');
            $cookie = $cookie instanceof \Iterator ? iterator_to_array($cookie) : [];
            $this->setCookies($cookie);
        }
    }

    /**
     * Retrieve http host
     *
     * @param boolean $trimPort
     * @return string
     */
    public function getHttpHost($trimPort = true)
    {
        $httpHost = $this->getServer('HTTP_HOST');
        if (empty($httpHost)) {
            return false;
        }
        if ($trimPort) {
            $host = explode(':', $httpHost);
            return $host[0];
        }
        return $httpHost;
    }

    /**
     * Retrieve the original path info
     *
     * @return string
     */
    public function getOriginalPathInfo()
    {
        return $this->getServer('REQUEST_URI') ?? '/';
    }

    /**
     * Set route name
     *
     * @param string $route
     * @return $this
     */
    public function setRouteName($route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Retrieve the server
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return ParametersInterface|mixed
     */
    public function getServer($name = null, $default = null)
    {
        return parent::getServer($name === null ? null : \strtolower($name), $default);
    }

    /**
     * Retrieve the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return (string)$this->module;
    }

    /**
     * Set the module name to use
     *
     * @param string $value
     * @return $this
     */
    public function setModuleName($value)
    {
        $this->module = $value;
        return $this;
    }

    /**
     * Retrieve the controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        return (string)$this->controller;
    }

    /**
     * Set the controller name to use
     *
     * @param string $value
     * @return $this
     */
    public function setControllerName($value)
    {
        $this->controller = $value;
        return $this;
    }

    /**
     * Retrieve the action name
     *
     * @return string
     */
    public function getActionName()
    {
        return (string)$this->action;
    }

    /**
     * Set the action name
     *
     * @param string $value
     * @return $this
     */
    public function setActionName($value)
    {
        $this->action = $value;
        return $this;
    }

    /**
     * Check method is safe
     *
     * @return bool
     */
    public function isSafeMethod()
    {
        if ($this->isSafeMethod === null) {
            $this->isSafeMethod = in_array(strtoupper($this->getMethod()), $this->safeRequestTypes, true);
        }
        return $this->isSafeMethod;
    }

    /**
     * Set cookie value
     *
     * @param array $cookie
     * @return $this
     */
    public function setCookies($cookie)
    {
        $this->cookies = $cookie;
        return parent::setCookies($cookie); // TODO: Change the autogenerated stub
    }

    /**
     * Retrieve cookie value
     *
     * @param string|null $name
     * @param string|null $default
     * @return string|null
     */
    public function getCookie($name = null, $default = null)
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function isAjax()
    {
        $isXmlHttpRequest = $this->getHeader('X_REQUESTED_WITH') === 'XMLHttpRequest';

        return $isXmlHttpRequest
            || $this->getParam('ajax')
            || $this->getParam('isAjax');
    }

    /**
     * Detect the base URI for the request
     *
     * Looks at a variety of criteria in order to attempt to autodetect a base
     * URI, including rewrite URIs, proxy URIs, etc.
     *
     * @return string
     */
    protected function detectRequestUri()
    {
        $requestUri = $this->getServer('REQUEST_URI');
        // HTTP proxy requests setup request URI with scheme and host [and port]
        // + the URL path, only use URL path.
        if ($requestUri !== null) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }
        return '/';
    }
}

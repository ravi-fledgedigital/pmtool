<?php

namespace OnitsukaTiger\Cegid\Plugin\Webapi;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response;

class ResponsePlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * Set status code
     *
     * @param Response $subject
     * @param mixed $code
     * @return int|mixed
     */
    public function beforeSetStatusCode(Response $subject, $code)
    {
        $pathInfo = $this->request->getPathInfo();
        if (strpos($pathInfo, 'V1/shipment') !== false) {
            if (method_exists($subject, 'isException') && $subject->isException()) {
                $message = $subject->getException()[0]->getMessage();
                if (strpos($message, 'API Successfully delete shipment') !== false) {
                    $code = 200;
                }
            }
        }

        return $code;
    }
}

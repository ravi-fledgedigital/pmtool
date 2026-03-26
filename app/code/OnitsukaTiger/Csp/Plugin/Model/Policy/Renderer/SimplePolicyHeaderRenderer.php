<?php

namespace OnitsukaTiger\Csp\Plugin\Model\Policy\Renderer;

use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Api\ModeConfigManagerInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Laminas\Http\Request;
use \Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\UrlInterface;

class SimplePolicyHeaderRenderer extends \Magento\Csp\Model\Policy\Renderer\SimplePolicyHeaderRenderer
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @param ModeConfigManagerInterface $modeConfig
     * @param Request $request
     * @param RedirectInterface $redirect
     */
    public function __construct(ModeConfigManagerInterface $modeConfig,Request $request,UrlInterface $urlInterface)
    {
        parent::__construct($modeConfig);
        $this->request = $request;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @param PolicyInterface $policy
     * @param HttpResponse $response
     * @return void
     */
    public function render(PolicyInterface $policy, HttpResponse $response): void
    {
        parent::render($policy, $response);
        $urlFrom = $this->urlInterface->getCurrentUrl();
        $allowUrl = str_contains($urlFrom, 'adyen/process/json');

        if( $allowUrl ){
            $response->setHeader('Content-Security-Policy-Report-Only','default-src self;',true);
        }

    }


}

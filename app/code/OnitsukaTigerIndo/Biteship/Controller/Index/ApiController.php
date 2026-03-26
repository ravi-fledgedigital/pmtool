<?php
/**
* phpcs:ignoreFile
*/
namespace OnitsukaTigerIndo\Biteship\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\CsrfAwareActionInterface as CsrfAwareActionInterface;

abstract class ApiController extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * Constructs a new instance.
     *
     * @param      \Magento\Framework\App\Action\Context  $context  The context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Creates a csrf validation exception.
     *
     * @param      \Magento\Framework\App\RequestInterface  $request  The request
     *
     * @return     InvalidRequestException|null             ( description_of_the_return_value )
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * { function_description }
     *
     * @param      \Magento\Framework\App\RequestInterface  $request  The request
     *
     * @return     bool|null                                ( description_of_the_return_value )
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

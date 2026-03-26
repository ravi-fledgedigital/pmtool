<?php

namespace OnitsukaTiger\Webapi\Controller\Rest;

use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Framework\Webapi\Validator\EntityArrayValidator\InputArraySizeLimitValue;
use Magento\Integration\Api\Exception\UserTokenException;
use Magento\Integration\Api\UserTokenValidatorInterface;
use Magento\Webapi\Controller\Rest\ParamsOverrider;
use Magento\Webapi\Controller\Rest\RequestValidator;
use Magento\Webapi\Controller\Rest\Router;
use Magento\Integration\Api\UserTokenReaderInterface;
use Magento\Integration\Api\IntegrationServiceInterface;


/**
 * This class is responsible for retrieving resolved input data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InputParamsResolver extends \Magento\Webapi\Controller\Rest\InputParamsResolver
{
    /**
     * @var RestRequest
     */
    private $request;
    /**
     * @var ParamsOverrider
     */
    private $paramsOverrider;
    /**
     * @var ServiceInputProcessor
     */
    private $serviceInputProcessor;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var RequestValidator
     */
    private $requestValidator;
    /**
     * @var UserTokenReaderInterface|null
     */
    private $tokenReader;
    /**
     * @var UserTokenValidatorInterface|null
     */
    private $tokenValidator;
    /**
     * @var IntegrationServiceInterface
     */
    private $integrationService;

    protected $methodsMap;

    protected $inputArraySizeLimitValue;

    protected $userTokenReader;

    protected $userTokenValidator;


    /**
     * @param RestRequest $request
     * @param ParamsOverrider $paramsOverrider
     * @param ServiceInputProcessor $serviceInputProcessor
     * @param Router $router
     * @param RequestValidator $requestValidator
     * @param MethodsMap|null $methodsMap
     * @param InputArraySizeLimitValue|null $inputArraySizeLimitValue
     */
    public function __construct(
        RestRequest $request,
        ParamsOverrider $paramsOverrider,
        ServiceInputProcessor $serviceInputProcessor,
        Router $router,
        RequestValidator $requestValidator,
        IntegrationServiceInterface $integrationService,
        MethodsMap $methodsMap = null,
        ?InputArraySizeLimitValue $inputArraySizeLimitValue = null,
        ?UserTokenReaderInterface $tokenReader = null,
        ?UserTokenValidatorInterface $tokenValidator = null
    ) {
        parent::__construct(
            $request,
            $paramsOverrider,
            $serviceInputProcessor,
            $router,
            $requestValidator,
            $methodsMap,
            $inputArraySizeLimitValue
        );
        $this->request = $request;
        $this->paramsOverrider = $paramsOverrider;
        $this->serviceInputProcessor = $serviceInputProcessor;
        $this->router = $router;
        $this->requestValidator = $requestValidator;
        $this->integrationService = $integrationService;
        $this->methodsMap = $methodsMap ?: ObjectManager::getInstance()
            ->get(MethodsMap::class);
        $this->inputArraySizeLimitValue = $inputArraySizeLimitValue ?? ObjectManager::getInstance()
            ->get(InputArraySizeLimitValue::class);
        $this->userTokenReader = $tokenReader ?? ObjectManager::getInstance()->get(UserTokenReaderInterface::class);
        $this->userTokenValidator = $tokenValidator
            ?? ObjectManager::getInstance()->get(UserTokenValidatorInterface::class);
    }
    /**
     * Process and resolve input parameters
     *
     * @return array
     * @throws Exception|AuthorizationException|LocalizedException
     */
    public function resolve()
    {
        $this->requestValidator->validate();
        $route = $this->getRoute();
        $this->inputArraySizeLimitValue->set($route->getInputArraySizeLimit());

        $response = $this->serviceInputProcessor->process(
            $route->getServiceClass(),
            $route->getServiceMethod(),
            $this->getInputData(),
        );

        return $response;
    }

    /**
     * Get API input data
     *
     * @return array
     * @throws InputException|Exception
     */
    public function getInputData()
    {
        $route = $this->getRoute();
        $serviceMethodName = $route->getServiceMethod();
        $serviceClassName = $route->getServiceClass();
        /*
         * Valid only for updates using PUT when passing id value both in URL and body
         */
        if ($this->request->getHttpMethod() == RestRequest::HTTP_METHOD_PUT) {
            $inputData = $this->paramsOverrider->overrideRequestBodyIdWithPathParam(
                $this->request->getParams(),
                $this->request->getBodyParams(),
                $serviceClassName,
                $serviceMethodName
            );
            $inputData = array_merge($inputData, $this->request->getParams());
        } else {
            $inputData = $this->request->getRequestData();
        }
        $this->validateParameters($serviceClassName, $serviceMethodName, array_keys($route->getParameters()));

        return $this->paramsOverrider->override($inputData, $route->getParameters());
    }

    /**
     * Validate that parameters are really used in the current request.
     *
     * @param string $serviceClassName
     * @param string $serviceMethodName
     * @param array $paramOverriders
     * @throws Exception
     */
    private function validateParameters(
        string $serviceClassName,
        string $serviceMethodName,
        array $paramOverriders
    ): void {
        $methodParams = $this->methodsMap->getMethodParams($serviceClassName, $serviceMethodName);

        // check if exits param storeId , check required
        foreach ($methodParams as $param) {
            if ($param[MethodsMap::METHOD_META_NAME] == 'storeId'  && $serviceMethodName == 'get') {
                $this->checkMissingStore();
                $this->checkWrongStore();
            }
        }

        foreach ($paramOverriders as $key => $param) {
            $arrayKeys = explode('.', $param);
            $value = array_shift($arrayKeys);

            foreach ($methodParams as $serviceMethodParam) {
                $serviceMethodParamName = $serviceMethodParam[MethodsMap::METHOD_META_NAME];
                $serviceMethodType = $serviceMethodParam[MethodsMap::METHOD_META_TYPE];

                $camelCaseValue = SimpleDataObjectConverter::snakeCaseToCamelCase($value);
                if ($serviceMethodParamName === $value || $serviceMethodParamName === $camelCaseValue) {
                    if (count($arrayKeys) > 0) {
                        $camelCaseKey = SimpleDataObjectConverter::snakeCaseToCamelCase('set_' . $arrayKeys[0]);
                        $this->validateParameters($serviceMethodType, $camelCaseKey, [implode('.', $arrayKeys)]);
                    }
                    unset($paramOverriders[$key]);
                    break;
                }
            }
        }
        if (!empty($paramOverriders)) {
            $message = 'The current request does not expect the next parameters: '
                . implode(', ', $paramOverriders);
            throw new \UnexpectedValueException(__($message)->__toString());
        }
    }

    /**
     * @return bool|void
     * @throws \Magento\Framework\Exception\IntegrationException
     */
    public function checkStoreId()
    {
        $authorizationHeaderValue = $this->request->getHeader('Authorization');
        if (!$authorizationHeaderValue) {
            $this->isRequestProcessed = true;
            return;
        }

        $headerPieces = explode(" ", $authorizationHeaderValue);
        if (count($headerPieces) !== 2) {
            $this->isRequestProcessed = true;
            return;
        }

        $tokenType = strtolower($headerPieces[0]);
        if ($tokenType !== 'bearer') {
            $this->isRequestProcessed = true;
            return;
        }

        $bearerToken = $headerPieces[1];
        try {
            $token = $this->userTokenReader->read($bearerToken);
        } catch (UserTokenException $exception) {
            $this->isRequestProcessed = true;
            return;
        }
        try {
            $this->userTokenValidator->validate($token);
        } catch (AuthorizationException $exception) {
            $this->isRequestProcessed = true;
            return;
        }

        $this->userId = $token->getUserContext()->getUserId();
        $storeIdParam = $this->request->getParam("storeId");
        $storeId = '';
        if ($this->userId) {
            $integrationData = $this->integrationService->get($this->userId);
            $storeId = $integrationData->getStoreIds();
        }
        $checkStoreInArray = $storeId == 0 || in_array(0, explode(',', $storeId)) ? "true" : in_array($storeIdParam, explode(',', $storeId));
        if (!$checkStoreInArray) {
            return true;
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function checkMissingStore()
    {
        $storeIdParam = $this->request->getParam("storeId");
        if ($storeIdParam == "") {
            throw new \Magento\Framework\Webapi\Exception(
                __('Store ID (Required) Missing'),
                0,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
    }


    /**
     * @return void
     * @throws Exception
     * @throws \Magento\Framework\Exception\IntegrationException
     */
    public function checkWrongStore()
    {
        if ($this->checkStoreId() == true) {
            throw new \Magento\Framework\Webapi\Exception(
                __('You don’t have access for this store'),
                0,
                \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED
            );
        }
    }
}

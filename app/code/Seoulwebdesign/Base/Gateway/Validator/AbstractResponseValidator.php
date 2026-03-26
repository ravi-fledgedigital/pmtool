<?php
namespace Seoulwebdesign\Base\Gateway\Validator;

use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

abstract class AbstractResponseValidator extends AbstractValidator
{

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    private $response;

    /**
     * ResponseValidator constructor.x
     * @param ResultInterfaceFactory $resultFactory
     * @param RemoteAddress $remoteAddress
     * @param Header $httpHeader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        RemoteAddress $remoteAddress,
        Header $httpHeader
    ) {
        parent::__construct($resultFactory);
        $this->httpHeader = $httpHeader;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $subject
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(array $subject)
    {
        $this->response = $subject['response'];
        $checkIp = function () {
            $whitelist = $this->getWhiteListIps();
            $remoteAddress = $this->remoteAddress->getRemoteAddress();
            $agent = $this->httpHeader->getHttpUserAgent(true);
            $result = !($whitelist && !in_array($remoteAddress, $whitelist));
            return [
                $result,
                "Malicious client ($agent) from ($remoteAddress)",
                '404'
            ];
        };

        $validResponse = function () {
            $result = $this->isValidResponse($this->response['object']);
            $message = 'Payment is not successful : Decrypt response data failed.';
            $code = '';
            if(is_array($result)) {
                $isValidResponse = $result['isValid'];
                $message = $result['message'] ? $result['message'] : $message;
                $code = $result['result_code'] ? $result['result_code'] : $code;
            } else {
                $isValidResponse = $result;
            }
            return [
                $isValidResponse,
                $message,
                $code
            ];
        };

        $statements = [$checkIp, $validResponse];
        /** @var \Closure $statement */
        foreach ($statements as $statement) {
            $result = $statement();
            if (!array_shift($result)) {
                return $this->createResult(false, [__(array_shift($result))], [__(array_shift($result))] );
            }
        }

        return $this->createResult(true);
    }

    public function getErrorMessage($response) {
        return 'Payment is not successful : Decrypt response data failed.';
    }

    /**
     * @return array
     */
    abstract protected function getWhiteListIps(): array;

    /**
     * @param array $response
     * @return mixed
     */
    abstract public function isValidResponse(array $response);



}

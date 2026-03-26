<?php
namespace Seoulwebdesign\Kakaopay\Gateway\Validator\Capture;

use Seoulwebdesign\Base\Gateway\Validator\AbstractResponseValidator;

class ResponseValidator extends AbstractResponseValidator
{

    /**
     * @return array
     */
    protected function getWhiteListIps(): array
    {
        return [];
    }

    /**
     * @param array $response
     * @return array
     */
    public function isValidResponse(array $response) : array
    {
        $result['isValid'] = false;
        $result['message'] = '';
        $result['result_code'] = '';
        if (isset($response['tid']) && $response['tid'] && isset($response['partner_order_id']) && $response['partner_order_id']) {
            $result['isValid'] = true;
            return $result;
        }
        $result['isValid'] = false;
        if (isset($response['extras']) && isset($response['extras']['method_result_message'])) {
            $result['message'] = $response['extras']['method_result_message'];
        }
        if (isset($response['extras']) && isset($response['extras']['method_result_code'])) {
            $result['result_code'] = $response['extras']['method_result_code'];
        }
        return $result;
    }
}

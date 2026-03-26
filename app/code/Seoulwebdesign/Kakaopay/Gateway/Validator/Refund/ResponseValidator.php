<?php
namespace Seoulwebdesign\Kakaopay\Gateway\Validator\Refund;

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
     * @return bool
     */
    public function isValidResponse(array $response): bool
    {
        if ($response && isset($response['status']) && isset($response['aid'])) {
            if ($response['status'] === 'CANCEL_PAYMENT') {
                return true;
            }
            if ($response['status'] === 'PART_CANCEL_PAYMENT') {
                return true;
            }
        }
        return false;
    }
}

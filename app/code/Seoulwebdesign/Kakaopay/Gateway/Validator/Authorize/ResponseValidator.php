<?php
namespace Seoulwebdesign\Kakaopay\Gateway\Validator\Authorize;

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
        if ($response && isset($response['tid'])
            && (isset($response['next_redirect_pc_url']) || isset($response['next_redirect_mobile_url']))
        ) {
            return true;
        }
        return false;
    }
}

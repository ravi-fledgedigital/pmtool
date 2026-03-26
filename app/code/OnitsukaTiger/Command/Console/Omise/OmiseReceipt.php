<?php

namespace OnitsukaTiger\Command\Console\Omise;
/**
 * Class OmiseReciept
 * @package OnitsukaTiger\Command\Console\Omise
 *
 * Please use Omise PHP library instead of this class if Omise alrady implement Reciept API call.
 */
class OmiseReceipt extends \OmiseApiResource
{
    const ENDPOINT = 'receipts';

    /**
     * Retrieves recipients.
     *
     * @param  string $date
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return OmiseReceipt
     */
    public static function retrieve($date = '', $publickey = null, $secretkey = null)
    {
        $params = [];
        if ($date) {
            $params['from'] = $date;
            $params['to'] = $date;
        }

        $resource = call_user_func([self::class, 'getInstance'], $publickey, $secretkey);
        $result   = $resource->execute(self::getUrl(), self::REQUEST_GET, $resource->getResourceKey(), $params);
        $resource->refresh($result);

        return $resource;
    }

    /**
     * @return string
     */
    private static function getUrl()
    {
        return OMISE_API_URL . self::ENDPOINT . '/';
    }
}

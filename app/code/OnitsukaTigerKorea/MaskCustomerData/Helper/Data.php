<?php

namespace OnitsukaTigerKorea\MaskCustomerData\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Mask name
     *
     * @param $name
     * @return array|string|string[]|null
     */
    public function maskName($name)
    {
        $name = trim($name);
        if (empty($name)) {
            return $name;
        }

        $totalLength = mb_strlen($name, 'UTF-8');

        if ($totalLength == 2) {
            // Mask only the second character
            return mb_substr($name, 0, 1, 'UTF-8') . '*';
        } elseif ($totalLength > 2) {
            // Mask all characters except the first and last
            return mb_substr($name, 0, 1, 'UTF-8')
                . str_repeat('*', $totalLength - 2)
                . mb_substr($name, -1, 1, 'UTF-8');
        }

        return $name;
    }

    /**
     * Mask email address
     *
     * @param $email
     * @return string
     */
    public function maskEmail($email)
    {
        if (empty($email)) {
            return $email;
        }

        list($name, $domain) = explode('@', $email);
        $maskedName = substr($name, 0, -2) . '**';
        $maskedDomain = substr($domain, 0, 1) . str_repeat('*', strlen($domain) - 1);

        return $maskedName . '@' . $maskedDomain;
    }

    /**
     * Mask phone number
     *
     * @param $phone
     * @return array|string|string[]|null
     */
    public function maskPhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }

        return preg_replace('/(\d{3})(\d{4})(\d+)/', '$1****$3', $phone);
    }

    /**
     *  Mask Address
     *
     * @param $address
     * @return string
     */
    public function maskAddress($address)
    {
        if (empty($address)) {
            return $address;
        }
        
        $words = explode(' ', $address);
        if (count($words) <= 2) {
            return $address;
        }

        $firstTwoWords = array_slice($words, 0, 2);
        $remainingPart = implode(' ', array_slice($words, 2));
        $maskedPart = str_repeat('*', mb_strlen($remainingPart, 'UTF-8'));

        return implode(' ', $firstTwoWords) . ' ' . $maskedPart;
    }
}

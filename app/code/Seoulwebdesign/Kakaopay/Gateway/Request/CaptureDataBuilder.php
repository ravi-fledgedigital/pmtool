<?php
namespace Seoulwebdesign\Kakaopay\Gateway\Request;


use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class RefundDataBuilder
 */
class CaptureDataBuilder implements BuilderInterface
{

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $data = [
            'cid' => $buildSubject['cid'],
            'tid' => $buildSubject['tid'],
            'partner_order_id' => $buildSubject['partner_order_id'],
            'partner_user_id' =>  $buildSubject['partner_user_id'],
            'pg_token' => $buildSubject['pg_token']
        ];

        return $data;
    }
}

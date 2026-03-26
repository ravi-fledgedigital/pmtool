<?php

namespace Seoulwebdesign\Kakaopay\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionStartChecker;

class TransparentSessionChecker
{
    /**
     * @var string[]
     */
    private $disableSessionUrls = [
        'kakaopay/checkout/fail',
        'kakaopay/checkout/approval'
    ];

    /**
     * @var Http
     */
    private $request;

    /**
     * @param Http $request
     */
    public function __construct(
        Http $request
    ) {
        $this->request = $request;
    }

    /**
     * Prevents session starting while instantiating redirect controller.
     *
     * @param SessionStartChecker $subject
     * @param bool $result
     * @return bool
     */
    public function afterCheck(SessionStartChecker $subject, bool $result): bool
    {
        if ($result === false) {
            return false;
        }

        foreach ($this->disableSessionUrls as $url) {
            if (strpos((string)$this->request->getPathInfo(), $url) !== false) {
                return false;
            }
        }

        return true;
    }
}

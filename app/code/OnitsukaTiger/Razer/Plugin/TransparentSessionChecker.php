<?php
declare(strict_types=1);

namespace OnitsukaTiger\Razer\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionStartChecker;

class TransparentSessionChecker
{
    private const TRANSPARENT_PATH = 'seamless';

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
     * @param SessionStartChecker $subject
     * @param bool $result
     * @return bool
     */
    public function afterCheck(SessionStartChecker $subject, bool $result): bool
    {
        if ($result === false) {
            return $result;
        }

        if ($this->request->getPostValue('mpsorderid')
            || $this->request->getPostValue('status')) {
            return strpos((string)$this->request->getPathInfo(), self::TRANSPARENT_PATH) === false;
        }

        return $result;
    }
}

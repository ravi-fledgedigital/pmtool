<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Laminas\Http\Header\SetCookie;
use Magento\ApplicationServer\Plugin\SessionMangerStarter;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Adapts PHP's session handling to work with Swoole in our ApplicationServer
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SessionAdapter
{

    /**
     * @param CookieManagerInterface $cookieManager
     * @param SessionMangerStarter $sessionMangerStarter
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly SessionMangerStarter $sessionMangerStarter,
    ) {
    }

    /**
     * Starts session
     *
     * Sets session_id based on session_name's cookie.  (mimics PHP's session behaviour)
     * Uses SessionMangerStarter to start any existing SessionManagers currently instantiated
     *
     * @param Request $appRequest
     * @return void
     */
    public function startSession(Request $appRequest) : void
    {
        $sessionName = session_name(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $sessionCookie = $sessionName ? ($appRequest->getCookie($sessionName) ?? '') : '';
        if (strlen($sessionCookie)) {
            session_id($sessionCookie); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        }
        $this->sessionMangerStarter->startSessions();
    }

    /**
     * End session
     *
     * If session is active, sets cookie in CookieManager when needed.  It tries to mimic behaviour from normal PHP
     * session so that it is compatible with ApplicationServer.
     * Closes, writes, and unsets session.
     *
     * @param Response $response
     * @return void
     */
    public function endSession(Response $response) : void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {  // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $sessionId = session_id(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $sessionName = session_name(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (strlen((string)$sessionId)
                && strlen((string)$sessionName)
                && !strlen($this->cookieManager->getCookie($sessionName) ?? '')
            ) {
                $cookieParams = session_get_cookie_params(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $setCookieHeader = new SetCookie(
                    $sessionName,
                    $sessionId,
                    $cookieParams['lifetime'] ? time() + $cookieParams['lifetime'] : 0,
                    $cookieParams['path'],
                    $cookieParams['domain'],
                    $cookieParams['secure'],
                    $cookieParams['httponly'],
                    null,
                    null,
                    null,
                );
                $response->getHeaders()->addHeader($setCookieHeader);
            }
            session_write_close(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        }
        $this->unsetSession();
    }

    /**
     * Unset session.
     *
     * @return void
     */
    public function unsetSession() : void
    {
        session_abort(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        session_name('PHPSESSID'); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        session_unset(); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        session_id(''); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $_SESSION = []; // phpcs:ignore Magento2.Security.Superglobal
    }
}

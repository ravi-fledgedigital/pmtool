<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\Plugin;

use Magento\Framework\App\Response\Http as HttpOriginal;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Stdlib\CookieDisablerInterface;
use Swoole\Http\Response as SwooleHttpResponse;

class Http implements ResetAfterRequestInterface
{
    /**
     * @var SwooleHttpResponse|null // Note: required temporal coupling/state
     */
    private ?SwooleHttpResponse $swooleResponse = null;

    /**
     * @param CookieDisablerInterface $cookieDisabler
     */
    public function __construct(private readonly CookieDisablerInterface $cookieDisabler)
    {
    }

    /**
     * Sets the swooleResponse for later.
     *
     * Note: This temporal coupling is required.  Must be called before around methods when using Swoole.
     *
     * @param SwooleHttpResponse $swooleResponse
     * @return void
     */
    public function setSwooleResponse(SwooleHttpResponse $swooleResponse) : void
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * Use Swoole to send headers
     *
     * Note: temporal coupling.  This should be called before aroundSendContent.
     *
     * @param HttpOriginal $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundSendHeaders(HttpOriginal $subject, callable $proceed)
    {
        if (!$this->swooleResponse) {
            return $proceed();
        }
        $this->swooleResponse->status($subject->getStatusCode(), $subject->getReasonPhrase());
        foreach ($subject->getHeaders()->toArray() as $name => $values) {
            if (($name === 'Set-Cookie') && $this->cookieDisabler->isCookiesDisabled()) {
                continue;
            }
            $this->swooleResponse->header($name, $values, false);
        }
        return $subject;
    }

    /**
     * Use Swoole to send content
     *
     * Note: temporal coupling.  This ends the swoole response.
     *
     * @param HttpOriginal $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundSendContent(HttpOriginal $subject, callable $proceed)
    {
        if (!$this->swooleResponse) {
            return $proceed();
        }
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $this->swooleResponse->end($subject->getContent() . ob_get_clean());
        return $subject;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->swooleResponse = null;
    }
}

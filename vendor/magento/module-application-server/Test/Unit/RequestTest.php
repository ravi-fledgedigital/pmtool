<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\ApplicationServer\Test\Unit;

use Magento\ApplicationServer\App\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
            self::markTestSkipped('Swoole extension is not loaded');
        }
    }

    public function testRequest(): void
    {
        $data = "GET /cgi-bin/process.cgi?licenseID=string&content=string&/paramsXML=string HTTP/1.1\r\n";
        $data .=  <<<"HTTP"
User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)
Host: www.example.com
Cookie: foo=bar; bar=baz
Accept-Language: en-us
Accept-Encoding: gzip, deflate
Connection: Keep-Alive

HTTP;
        $sRequest = \Swoole\Http\Request::create(['parse_cookie' => true, 'parse_body' => true]);
        $sRequest->parse($data);

        $request = new Request($sRequest);
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('Mozilla/4.0 (compatible; MSIE5.01; Windows NT)', $request->getHeader('User-Agent'));
        $this->assertEquals('www.example.com', $request->getHeader('Host'));
        $this->assertEquals('en-us', $request->getHeader('Accept-Language'));
        $this->assertEquals('gzip, deflate', $request->getHeader('Accept-Encoding'));
        $this->assertEquals('keep-alive', $request->getHeader('Connection'));
        $this->assertEquals('string', $request->getParam('licenseID'));
        $this->assertEquals('bar', $request->getCookie('foo'));
        $this->assertEquals('baz', $request->getCookie('bar'));
    }
}

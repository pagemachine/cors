<?php
declare(strict_types = 1);
namespace PAGEmachine\Cors\Tests\Unit\AccessControl;

/*
 * This file is part of the PAGEmachine CORS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Cors\AccessControl\Request;

/**
 * Testcase for PAGEmachine\Cors\AccessControl\Request
 */
class RequestTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider crossOriginEnvironments
     *
     * @param array $environment
     * @param bool $isCrossOrigin
     */
    public function detectsCrossOriginRequests(array $environment, $isCrossOrigin)
    {
        $request = new Request($environment);
        $this->assertEquals($isCrossOrigin, $request->isCrossOrigin());
    }

    /**
     * @test
     * @dataProvider credentialEnvironments
     *
     * @depends detectsCrossOriginRequests
     * @param array $environment
     * @param bool $hasCredentials
     */
    public function detectsCredentials(array $environment, $hasCredentials)
    {
        $request = new Request($environment);
        $this->assertEquals($hasCredentials, $request->hasCredentials());
    }

    /**
     * @test
     * @dataProvider preflightEnvironments
     * @depends detectsCrossOriginRequests
     *
     * @param array $environment
     * @param bool $isPreflight
     * @param string $requestMethod
     * @param array $requestHeaders
     */
    public function detectsPreflightRequests(array $environment, $isPreflight, $requestMethod, $requestHeaders)
    {
        $request = new Request($environment);
        $this->assertEquals($isPreflight, $request->isPreflight());
        $this->assertEquals($requestMethod, $request->getRequestMethod());
        $this->assertEquals($requestHeaders, $request->getRequestHeaders());
    }

    /**
     * @return array
     */
    public function crossOriginEnvironments()
    {
        return [
            'Regular' => [
                [
                    'HTTP_HOST' => 'example.org',
                ],
                false,
            ],
            'Origin but regular' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.org',
                ],
                false,
            ],
            'Different scheme' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.org',
                    'HTTPS' => 'on',
                ],
                true,
            ],
            'Different host' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                ],
                true,
            ],
            'Different port' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.org',
                    'SERVER_PORT' => 8080,
                ],
                true,
            ],
            'Implicit origin port' => [
                [
                    'HTTP_ORIGIN' => 'https://example.org',
                    'HTTP_HOST' => 'example.org',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 443,
                ],
                false,
            ],
            'Implicit destination port' => [
                [
                    'HTTP_ORIGIN' => 'https://example.org:443',
                    'HTTP_HOST' => 'example.org',
                    'HTTPS' => 'on',
                ],
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function credentialEnvironments()
    {
        return [
            'Regular' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                ],
                false,
            ],
            'Cookie' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'HTTP_COOKIE' => 'foo=bar',
                ],
                true,
            ],
            'HTTP authentication' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'HTTP_AUTHORIZATION' => 'Authorization: Basic d2lraTpwZWRpYQ==', // wiki:pedia
                ],
                true,
            ],
            'Without SSL client certificate' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'SSL_CLIENT_VERIFY' => 'NONE',
                ],
                false,
            ],
            'With SSL client certificate' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'SSL_CLIENT_VERIFY' => 'SUCCESS',
                ],
                true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function preflightEnvironments()
    {
        return [
            'No preflight' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'REQUEST_METHOD' => 'GET',
                ],
                false,
                null,
                [],
            ],
            'Regular' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'REQUEST_METHOD' => 'OPTIONS',
                ],
                true,
                null,
                [],
            ],
            'Request method' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PUT',
                ],
                true,
                'PUT',
                [],
            ],
            'Request headers' => [
                [
                    'HTTP_ORIGIN' => 'http://example.org',
                    'HTTP_HOST' => 'example.com',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-Requested-With, Foo',
                ],
                true,
                null,
                ['X-Requested-With', 'Foo'],
            ],
        ];
    }
}

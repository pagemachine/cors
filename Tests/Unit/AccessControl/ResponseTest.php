<?php

declare(strict_types = 1);

namespace Pagemachine\Cors\Tests\Unit\AccessControl;

/*
 * This file is part of the Pagemachine CORS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pagemachine\Cors\AccessControl\Response;

/**
 * Testcase for Pagemachine\Cors\AccessControl\Response
 */
class ResponseTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider properties
     *
     * @param string $allowedOrigin
     * @param bool $allowCredentials
     * @param array $exposedHeaders
     * @param bool $isPreflight
     * @param array $allowedMethods
     * @param array $allowedHeaders
     *
     * @param array $expectedHeaders
     * @param bool $expectedSkipBodyAndExit
     */
    public function sendsCorrectHeaders(string $allowedOrigin, bool $allowCredentials, array $exposedHeaders, bool $isPreflight, array $allowedMethods, array $allowedHeaders, array $expectedHeaders, bool $expectedSkipBodyAndExit)
    {
        $headers = [];
        $skipBodyAndExit = false;

        /** @var Response|\PHPUnit_Framework_MockObject_MockObject */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['sendHeader', 'skipBodyAndExit'])
            ->getMock();
        $response->expects($this->any())
            ->method('sendHeader')
            ->will($this->returnCallback(function ($header) use (&$headers) {
                $headers[] = $header;
            }));
        $response->expects($this->atMost(1))
            ->method('skipBodyAndExit')
            ->will($this->returnCallback(function () use (&$skipBodyAndExit) {
                $skipBodyAndExit = true;
            }));

        $response->setAllowedOrigin($allowedOrigin);
        $response->setAllowCredentials($allowCredentials);
        $response->setExposedHeaders($exposedHeaders);
        $response->setPreflight($isPreflight);
        $response->setAllowedMethods($allowedMethods);
        $response->setAllowedHeaders($allowedHeaders);
        $response->send();

        $this->assertEquals($expectedHeaders, $headers);
        $this->assertEquals($expectedSkipBodyAndExit, $skipBodyAndExit);
    }

    /**
     * @return array
     */
    public function properties(): array
    {
        return [
            'Basic' => [
                'http://example.org',
                false,
                [],
                false,
                [],
                [],
                [
                    'Access-Control-Allow-Origin: http://example.org',
                ],
                false,
            ],
            'Credentials' => [
                'http://example.org',
                true,
                [],
                false,
                [],
                [],
                [
                    'Access-Control-Allow-Origin: http://example.org',
                    'Access-Control-Allow-Credentials: true',
                ],
                false,
            ],
            'Exposed headers' => [
                'http://example.org',
                false,
                ['X-Foo', 'X-Bar'],
                false,
                [],
                [],
                [
                    'Access-Control-Allow-Origin: http://example.org',
                    'Access-Control-Expose-Headers: X-Foo, X-Bar',
                ],
                false,
            ],
            'Preflight' => [
                'http://example.org',
                false,
                [],
                true,
                [],
                [],
                [
                    'Access-Control-Allow-Origin: http://example.org',
                ],
                true,
            ],
            'Preflight method' => [
                'http://example.org',
                false,
                [],
                true,
                ['PUT'],
                [],
                [
                    'Access-Control-Allow-Origin: http://example.org',
                    'Access-Control-Allow-Methods: PUT',
                ],
                true,
            ],
            'Preflight headers' => [
                'http://example.org',
                false,
                [],
                true,
                [],
                ['X-Foo'],
                [
                    'Access-Control-Allow-Origin: http://example.org',
                    'Access-Control-Allow-Headers: X-Foo',
                ],
                true,
            ],
        ];
    }
}

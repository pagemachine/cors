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
use Pagemachine\Cors\AccessControl\Exception\AccessDeniedException;
use Pagemachine\Cors\AccessControl\Negotiator;
use Pagemachine\Cors\AccessControl\Request;
use Pagemachine\Cors\AccessControl\Response;

/**
 * Testcase for Pagemachine\Cors\AccessControl\Negotiator
 */
class NegotiatorTest extends UnitTestCase
{
    /**
     * @var Negotiator
     */
    protected $negotiator;

    /**
     * Common setup for all tests
     */
    public function setUp()
    {
        $this->negotiator = new Negotiator();
    }

    /**
     * @test
     */
    public function doesNothingForSameOriginRequest()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.org',
        ]);
        $response = new Response();

        $this->negotiator->processRequest($request, $response);

        $this->assertEquals(new Response(), $response);
    }

    /**
     * @test
     */
    public function allowsWildcardOriginWithoutCredentials()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['*']);
        $this->negotiator->processRequest($request, $response);

        $this->assertEquals('*', $response->getAllowedOrigin());
    }

    /**
     * @test
     * @dataProvider credentialHeaders
     */
    public function throwsExceptionForWildcardOriginWithCredentials(array $credentialHeaders)
    {
        $request = new Request(array_merge([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ], $credentialHeaders));
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['*']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(1413983266);

        $this->negotiator->processRequest($request, $response);
    }

    /**
     * @return array
     */
    public function credentialHeaders(): array
    {
        return [
            'cookie' => [
                ['HTTP_COOKIE' => 'test=1'],
            ],
            'authorization' => [
                ['HTTP_AUTHORIZATION' => 'Basic YWxhZGRpbjpvcGVuc2VzYW1l'],
            ],
            'client certificate' => [
                ['SSL_CLIENT_VERIFY' => 'SUCCESS'],
            ],
        ];
    }

    /**
     * @test
     */
    public function allowsOriginByList()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['http://example.org', 'http://example.com']);
        $this->negotiator->processRequest($request, $response);

        $this->assertEquals('http://example.com', $response->getAllowedOrigin());
    }

    /**
     * @test
     */
    public function allowsOriginByPattern()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOriginsPattern('http:\/\/example\.(org|com)');
        $this->negotiator->processRequest($request, $response);

        $this->assertEquals('http://example.com', $response->getAllowedOrigin());
    }

    /**
     * @test
     */
    public function throwsExceptionIfOriginIsNotAllowed()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ]);
        $response = new Response();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(1413983266);

        $this->negotiator->processRequest($request, $response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfOriginPortIsNotAllowed()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.org:4040',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['http://example.org:8080']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(1413983266);

        $this->negotiator->processRequest($request, $response);
    }

    /**
     * @test
     * @dataProvider credentialRequests
     */
    public function allowsCredentials(array $credentialHeaders, bool $allowCredentials, bool $expectedAllowCredentials)
    {
        $request = new Request(array_merge([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ], $credentialHeaders));
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['http://example.com']);
        $this->negotiator->setAllowCredentials($allowCredentials);
        $this->negotiator->processRequest($request, $response);

        $this->assertEquals($expectedAllowCredentials, $response->getAllowCredentials());
    }

    /**
     * @return array
     */
    public function credentialRequests(): array
    {
        return [
            'Without credentials, credentials not allowed' => [
                [],
                false,
                false,
            ],
            'Without credentials, credentials allowed' => [
                [],
                true,
                false,
            ],
            'With credentials, credentials not allowed' => [
                ['HTTP_COOKIE' => 'test=1'],
                false,
                false,
            ],
            'With credentials, credentials allowed' => [
                ['HTTP_COOKIE' => 'test=1'],
                true,
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider exposedHeaderRequests
     */
    public function exposesHeaders(array $exposedHeaders)
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['*']);
        $this->negotiator->setExposedHeaders($exposedHeaders);
        $this->negotiator->processRequest($request, $response);

        $this->assertEquals($exposedHeaders, $response->getExposedHeaders());
    }

    /**
     * @return array
     */
    public function exposedHeaderRequests(): array
    {
        return [
            'No headers' => [
                [],
            ],
            'Exposed headers' => [
                ['X-Foo', 'X-Bar'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider preflightRequests
     *
     * @param string $requestMethod
     * @param array $requestHeaders
     * @param array $allowedMethods
     * @param array $allowedHeaders
     */
    public function allowsValidPreflightRequests(string $requestMethod, array $requestHeaders, array $allowedMethods, array $allowedHeaders)
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => $requestMethod,
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => implode(',', $requestHeaders),
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['*']);
        $this->negotiator->setAllowedMethods($allowedMethods);
        $this->negotiator->setAllowedHeaders($allowedHeaders);
        $this->negotiator->processRequest($request, $response);

        $this->assertContains($requestMethod, $response->getAllowedMethods());

        foreach ($requestHeaders as $requestHeader) {
            $this->assertContains($requestHeader, $response->getAllowedHeaders());
        }
    }

    /**
     * @return array
     */
    public function preflightRequests(): array
    {
        return [
            'Simple method with custom header' => [
                'POST',
                ['X-Foo'],
                [],
                ['X-Foo', 'X-Bar'],
            ],
            'Non-simple method' => [
                'PUT',
                [],
                ['PUT'],
                [],
            ],
            'Non-simple method with custom header' => [
                'DELETE',
                ['X-Bar'],
                ['PUT', 'DELETE'],
                ['X-Bar'],
            ],
        ];
    }

    /**
     * @test
     */
    public function throwsExceptionForPreflightWithDisallowedRequestMethod()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'DELETE',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['*']);
        $this->negotiator->setAllowedMethods(['PUT']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(1413983927);

        $this->negotiator->processRequest($request, $response);
    }

    /**
     * @test
     */
    public function throwsExceptionForPreflightWithNotAllowedRequestHeaders()
    {
        $request = new Request([
            'HTTP_HOST' => 'example.org',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test',
            'HTTP_ORIGIN' => 'http://example.com',
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-Foo',
        ]);
        $response = new Response();

        $this->negotiator->setAllowedOrigins(['*']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(1413988013);

        $this->negotiator->processRequest($request, $response);
    }
}

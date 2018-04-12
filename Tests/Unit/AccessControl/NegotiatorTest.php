<?php
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
use PAGEmachine\Cors\AccessControl\Negotiator;
use PAGEmachine\Cors\AccessControl\Request;
use PAGEmachine\Cors\AccessControl\Response;
use PAGEmachine\Cors\Http\Uri;

/**
 * Testcase for PAGEmachine\Cors\AccessControl\Negotiator
 */
class NegotiatorTest extends UnitTestCase
{
    /**
     * @var \PAGEmachine\Cors\AccessControl\Request
     */
    protected $request;

    /**
     * @var \PAGEmachine\Cors\AccessControl\Response
     */
    protected $response;

    /**
     * @var \PAGEmachine\Cors\AccessControl\Negotiator
     */
    protected $negotiator;

    /**
     * Common setup for all tests
     */
    public function setUp()
    {
        $origin = $this->getMockBuilder(Uri::class)
            ->setMethods(null)
            ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->request->setOrigin($origin);

        $this->response = $this->getResponseMock();

        $this->negotiator = new Negotiator();
    }

    /**
     * @test
     */
    public function doesNothingForSameOriginRequest()
    {
        $expectedResponse = $this->getResponseMock();

        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertEquals($expectedResponse, $this->response);
    }

    /**
     * @test
     */
    public function allowsWildcardOriginWithoutCredentials()
    {
        $this->request->setCrossOrigin(true);

        $this->negotiator->setAllowedOrigins(['*']);
        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertEquals('*', $this->response->getAllowedOrigin());
    }

    /**
     * @test
     * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
     * @expectedExceptionCode 1413983266
     */
    public function throwsExceptionForWildcardOriginWithCredentials()
    {
        $this->request->setCrossOrigin(true);
        $this->request->setHasCredentials(true);

        $this->negotiator->setAllowedOrigins(['*']);
        $this->negotiator->processRequest($this->request, $this->response);
    }

    /**
     * @test
     */
    public function allowsOriginByList()
    {
        $this->request->getOrigin()->setScheme('http');
        $this->request->getOrigin()->setHostname('example.org');
        $this->request->setCrossOrigin(true);

        $this->negotiator->setAllowedOrigins(['http://example.org', 'http://example.com']);
        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertEquals('http://example.org', $this->response->getAllowedOrigin());
    }

    /**
     * @test
     * @dataProvider originPatternRequests
     *
     * @param string $pattern
     * @param string $origin
     */
    public function allowsOriginByPattern($pattern, $origin)
    {
        $this->request->setCrossOrigin(true);

        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $this->request->getOrigin()->setScheme($scheme);
        $host = parse_url($origin, PHP_URL_HOST);
        $this->request->getOrigin()->setHostname($host);

        $this->negotiator->setAllowedOriginsPattern($pattern);
        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertEquals($origin, $this->response->getAllowedOrigin());
    }

    /**
     * @test
     * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
     * @expectedExceptionCode 1413983266
     */
    public function throwsExceptionIfOriginIsNotAllowed()
    {
        $this->request->getOrigin()->setScheme('http');
        $this->request->getOrigin()->setHostname('example.org');
        $this->request->setCrossOrigin(true);

        $this->negotiator->processRequest($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
     * @expectedExceptionCode 1413983266
     */
    public function throwsExceptionIfOriginPortIsNotAllowed()
    {
        $this->request->getOrigin()->setScheme('http');
        $this->request->getOrigin()->setHostname('example.org');
        $this->request->getOrigin()->setPort(80);
        $this->request->setCrossOrigin(true);

        $this->negotiator->setAllowedOrigins(['http://example.org:8080']);
        $this->negotiator->processRequest($this->request, $this->response);
    }

    /**
     * @test
     * @dataProvider credentialRequests
     *
     * @param bool $requestHasCredentials
     * @param bool $allowCredentials
     * @param bool $expectedAllowCredentials
     */
    public function allowsCredentials($requestHasCredentials, $allowCredentials, $expectedAllowCredentials)
    {
        $this->request->getOrigin()->setScheme('http');
        $this->request->getOrigin()->setHostname('example.org');
        $this->request->setCrossOrigin(true);
        $this->request->setHasCredentials($requestHasCredentials);

        $this->negotiator->setAllowedOrigins(['http://example.org', 'http://example.com']);
        $this->negotiator->setAllowCredentials($allowCredentials);
        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertEquals($expectedAllowCredentials, $this->response->getAllowCredentials());
    }

    /**
     * @test
     * @dataProvider exposedHeaderRequests
     *
     * @param array $exposedHeaders
     * @return array
     */
    public function exposesHeaders(array $exposedHeaders)
    {
        $this->request->getOrigin()->setScheme('http');
        $this->request->getOrigin()->setHostname('example.org');
        $this->request->setCrossOrigin(true);

        $this->negotiator->setAllowedOrigins(['http://example.org', 'http://example.com']);
        $this->negotiator->setExposedHeaders($exposedHeaders);
        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertEquals($exposedHeaders, $this->response->getExposedHeaders());
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
    public function allowsValidPreflightRequests($requestMethod, array $requestHeaders, array $allowedMethods, array $allowedHeaders)
    {
        $this->request->getOrigin()->setScheme('http');
        $this->request->getOrigin()->setHostname('example.org');
        $this->request->setCrossOrigin(true);
        $this->request->setPreflight(true);
        $this->request->setRequestMethod($requestMethod);
        $this->request->setRequestHeaders($requestHeaders);

        $this->negotiator->setAllowedOrigins(['http://example.org']);
        $this->negotiator->setAllowedMethods($allowedMethods);
        $this->negotiator->setAllowedHeaders($allowedHeaders);
        $this->negotiator->processRequest($this->request, $this->response);

        $this->assertContains($requestMethod, $this->response->getAllowedMethods());

        foreach ($requestHeaders as $requestHeader) {
            $this->assertContains($requestHeader, $this->response->getAllowedHeaders());
        }
    }

    /**
     * @test
     * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
     * @expectedExceptionCode 1413983849
     */
    public function throwsExceptionForPreflightWithoutRequestMethod()
    {
        $this->request->setCrossOrigin(true);
        $this->request->setPreflight(true);

        $this->negotiator->processRequest($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
     * @expectedExceptionCode 1413983927
     */
    public function throwsExceptionForPreflightWithNotAllowedRequestMethod()
    {
        $this->request->setCrossOrigin(true);
        $this->request->setPreflight(true);
        $this->request->setRequestMethod('DELETE');

        $this->negotiator->setAllowedMethods(['PUT']);
        $this->negotiator->processRequest($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
     * @expectedExceptionCode 1413988013
     */
    public function throwsExceptionForPreflightWithNotAllowedRequestHeaders()
    {
        $this->request->setCrossOrigin(true);
        $this->request->setPreflight(true);
        $this->request->setRequestMethod('POST');
        $this->request->setRequestHeaders(['X-Foo']);

        $this->negotiator->processRequest($this->request, $this->response);
    }

    /**
     * @return array
     */
    public function originPatternRequests()
    {
        return [
            [
                'http:\/\/example\.(org|com)',
                'http://example.org',
            ],
            [
                'http:\/\/example\.(org|com)',
                'http://example.com',
            ],
        ];
    }

    /**
     * @return array
     */
    public function credentialRequests()
    {
        return [
            'No credentials, not allowed' => [
                false,
                false,
                false,
            ],
            'No credentials, allowed' => [
                false,
                true,
                false,
            ],
            'Credentials, not allowed' => [
                true,
                false,
                false,
            ],
            'Credentials, allowed' => [
                true,
                true,
                true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function exposedHeaderRequests()
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
     * @return array
     */
    public function preflightRequests()
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
     * Builds a mocked response object
     *
     * @return \PAGEmachine\Cors\AccessControl\Response
     */
    protected function getResponseMock()
    {
        return $this->getMockBuilder(Response::class)
            ->setMethods(null)
            ->getMock();
    }
}

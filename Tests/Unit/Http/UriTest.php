<?php
namespace PAGEmachine\Cors\Tests\Unit\Http;

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
use PAGEmachine\Cors\Http\Uri;

/**
 * Testcase for PAGEmachine\Cors\Http\Uri
 */
class UriTest extends UnitTestCase
{
    /**
     * List of URI object properties
     *
     * @var array
     */
    protected $properties = [
        'scheme',
        'hostname',
        'port',
        'username',
        'password',
        'path',
        'query',
        'fragment',
    ];

    /**
     * @test
     * @dataProvider uris
     *
     * @param string $uri
     * @param array $expected
     */
    public function extractsUriComponents($uri, $expected)
    {
        $uri = new Uri($uri);

        foreach ($this->properties as $property) {
            $propertyGetter = 'get' . ucfirst($property);
            $this->assertEquals($uri->$propertyGetter(), $expected[$property], $property . ' does not match');
        }
    }

    /**
     * @return array
     */
    public function uris()
    {
        return [
            'Basic' => [
                'http://example.org/',
                [
                    'scheme' => 'http',
                    'hostname' => 'example.org',
                    'port' => null,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'Authentication' => [
                'http://foo:bar@example.org/',
                [
                    'scheme' => 'http',
                    'hostname' => 'example.org',
                    'port' => null,
                    'username' => 'foo',
                    'password' => 'bar',
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'Query and tricky fragment' => [
                'http://example.org/?foo=bar#?baz=qux',
                [
                    'scheme' => 'http',
                    'hostname' => 'example.org',
                    'port' => null,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => 'foo=bar',
                    'fragment' => '?baz=qux',
                ],
            ],
            'HTTPS' => [
                'https://example.org/',
                [
                    'scheme' => 'https',
                    'hostname' => 'example.org',
                    'port' => null,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider environments
     *
     * @param array $environment
     * @param array $expected
     */
    public function evaluatesEnvironment($environment, $expected)
    {
        $uri = Uri::fromEnvironment($environment);

        foreach ($this->properties as $property) {
            $propertyGetter = 'get' . ucfirst($property);
            $this->assertEquals($uri->$propertyGetter(), $expected[$property], $property . ' does not match: ' . $uri->$propertyGetter());
        }
    }

    /**
     * @return array
     */
    public function environments()
    {
        return [
            'Basic' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/',
                ],
                [
                    'scheme' => 'http',
                    'hostname' => 'example.org',
                    'port' => 80,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'Authentication' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/',
                    'PHP_AUTH_USER' => 'foo',
                    'PHP_AUTH_PW' => 'bar',
                ],
                [
                    'scheme' => 'http',
                    'hostname' => 'example.org',
                    'port' => 80,
                    'username' => 'foo',
                    'password' => 'bar',
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'Query and tricky fragment' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/?foo=bar#?baz=qux',
                ],
                [
                    'scheme' => 'http',
                    'hostname' => 'example.org',
                    'port' => 80,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => 'foo=bar',
                    'fragment' => '?baz=qux',
                ],
            ],
            'HTTPS' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 443,
                    'REQUEST_URI' => '/',
                ],
                [
                    'scheme' => 'https',
                    'hostname' => 'example.org',
                    'port' => 443,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'HTTPS through proxy' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                    'SERVER_PORT' => 443,
                    'REQUEST_URI' => '/',
                ],
                [
                    'scheme' => 'https',
                    'hostname' => 'example.org',
                    'port' => 443,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
            'HTTPS through proxy with forwarded port' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                    'HTTP_X_FORWARDED_PORT' => 443,
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/',
                ],
                [
                    'scheme' => 'https',
                    'hostname' => 'example.org',
                    'port' => 443,
                    'username' => null,
                    'password' => null,
                    'path' => '/',
                    'query' => null,
                    'fragment' => null,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider environmentsWithoutPort
     *
     * @param array $environment
     * @param int $expectedPort
     */
    public function returnsNormalizedPort(array $environment, $expectedPort)
    {
        $uri = Uri::fromEnvironment($environment);

        $this->assertEquals($expectedPort, $uri->getNormalizedPort());
    }

    /**
     * @return array
     */
    public function environmentsWithoutPort()
    {
        return [
            'HTTPS' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'HTTPS' => 'on',
                    'REQUEST_URI' => '/',
                ],
                443,
            ],
            'HTTP' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'REQUEST_URI' => '/',
                ],
                80,
            ],
            'HTTP with custom port' => [
                [
                    'HTTP_HOST' => 'example.org',
                    'SERVER_PORT' => 8080,
                    'REQUEST_URI' => '/',
                ],
                8080,
            ],
        ];
    }

    /**
     * @test
     */
    public function throwsExceptionOnInvalidUri()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1446565362);
        
        $uri = new Uri('javascript://');
    }
}

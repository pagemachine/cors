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

use PAGEmachine\Cors\Http\Uri;

/**
 * Testcase for PAGEmachine\Cors\Http\Uri
 */
class UriTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
  public function extractsUriComponents($uri, $expected) {

    $uri = new Uri($uri);

    foreach ($this->properties as $property) {

      $propertyGetter = 'get' . ucfirst($property);
      $this->assertEquals($uri->$propertyGetter(), $expected[$property], $property . ' does not match');
    }
  }

  /**
   * @test
   * @dataProvider environments
   *
   * @param array $environment
   * @param array $expected
   */
  public function evaluatesEnvironment($environment, $expected) {

    $uri = Uri::fromEnvironment($environment);

    foreach ($this->properties as $property) {

      $propertyGetter = 'get' . ucfirst($property);
      $this->assertEquals($uri->$propertyGetter(), $expected[$property], $property . ' does not match: ' . $uri->$propertyGetter());
    }
  }

  /**
   * @test
   * @expectedException InvalidArgumentException
   * @expectedExceptionCode 1446565362
   */
  public function throwsExceptionOnInvalidUri() {

    new Uri('javascript://');
  }

  /**
   * @return array
   */
  public function uris() {

    return [
      'Basic' => ['http://example.org/', [
        'scheme' => 'http',
        'hostname' => 'example.org',
        'port' => NULL,
        'username' => NULL,
        'password' => NULL,
        'path' => '/',
        'query' => NULL,
        'fragment' => NULL,
      ]],
      'Authentication' => ['http://foo:bar@example.org/', [
        'scheme' => 'http',
        'hostname' => 'example.org',
        'port' => NULL,
        'username' => 'foo',
        'password' => 'bar',
        'path' => '/',
        'query' => NULL,
        'fragment' => NULL,
      ]],
      'Query and tricky fragment' => ['http://example.org/?foo=bar#?baz=qux', [
        'scheme' => 'http',
        'hostname' => 'example.org',
        'port' => NULL,
        'username' => NULL,
        'password' => NULL,
        'path' => '/',
        'query' => 'foo=bar',
        'fragment' => '?baz=qux',
      ]],
      'HTTPS' => ['https://example.org/', [
        'scheme' => 'https',
        'hostname' => 'example.org',
        'port' => NULL,
        'username' => NULL,
        'password' => NULL,
        'path' => '/',
        'query' => NULL,
        'fragment' => NULL,
      ]],
    ];
  }

  /**
   * @return array
   */
  public function environments() {

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
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
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
          'query' => NULL,
          'fragment' => NULL,
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
          'username' => NULL,
          'password' => NULL,
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
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
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
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
        ],
      ],
    ];
  }
}

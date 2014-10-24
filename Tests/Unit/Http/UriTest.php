<?php
namespace PAGEmachine\CORS\Tests\Http;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Mathias Brodala <mbrodala@pagemachine.de>, PAGEmachine AG
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/ 

use PAGEmachine\CORS\Http\Uri;

/**
 * Testcase for PAGEmachine\CORS\Http\Uri
 */
class UriTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

  /**
   * List of URI object properties
   *
   * @var array
   */
  protected $properties = array(
    'scheme',
    'hostname',
    'port',
    'username',
    'password',
    'path',
    'query',
    'fragment',
  );

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
   * @return array
   */
  public function uris() {

    return array(
      'Basic' => array('http://example.org/', array(
        'scheme' => 'http',
        'hostname' => 'example.org',
        'port' => 80,
        'username' => NULL,
        'password' => NULL,
        'path' => '/',
        'query' => NULL,
        'fragment' => NULL,
      )),
      'Authentication' => array('http://foo:bar@example.org/', array(
        'scheme' => 'http',
        'hostname' => 'example.org',
        'port' => 80,
        'username' => 'foo',
        'password' => 'bar',
        'path' => '/',
        'query' => NULL,
        'fragment' => NULL,
      )),
      'Query and tricky fragment' => array('http://example.org/?foo=bar#?baz=qux', array(
        'scheme' => 'http',
        'hostname' => 'example.org',
        'port' => 80,
        'username' => NULL,
        'password' => NULL,
        'path' => '/',
        'query' => 'foo=bar',
        'fragment' => '?baz=qux',
      )),
      'HTTPS' => array('https://example.org/', array(
        'scheme' => 'https',
        'hostname' => 'example.org',
        'port' => 443,
        'username' => NULL,
        'password' => NULL,
        'path' => '/',
        'query' => NULL,
        'fragment' => NULL,
      )),
    );
  }

  /**
   * @return array
   */
  public function environments() {

    return array(
      'Basic' => array(
        array(
          'HTTP_HOST' => 'example.org',
          'SERVER_PORT' => 80,
          'REQUEST_URI' => '/',
        ),
        array(
          'scheme' => 'http',
          'hostname' => 'example.org',
          'port' => 80,
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
        ),
      ),
      'Authentication' => array(
        array(
          'HTTP_HOST' => 'example.org',
          'SERVER_PORT' => 80,
          'REQUEST_URI' => '/',
          'PHP_AUTH_USER' => 'foo',
          'PHP_AUTH_PW' => 'bar',
        ),
        array(
          'scheme' => 'http',
          'hostname' => 'example.org',
          'port' => 80,
          'username' => 'foo',
          'password' => 'bar',
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
        ),
      ),
      'Query and tricky fragment' => array(
        array(
          'HTTP_HOST' => 'example.org',
          'SERVER_PORT' => 80,
          'REQUEST_URI' => '/?foo=bar#?baz=qux',
        ),
        array(
          'scheme' => 'http',
          'hostname' => 'example.org',
          'port' => 80,
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => 'foo=bar',
          'fragment' => '?baz=qux',
        ),
      ),
      'HTTPS' => array(
        array(
          'HTTP_HOST' => 'example.org',
          'HTTPS' => 'on',
          'SERVER_PORT' => 443,
          'REQUEST_URI' => '/',
        ),
        array(
          'scheme' => 'https',
          'hostname' => 'example.org',
          'port' => 443,
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
        ),
      ),
      'HTTPS through proxy' => array(
        array(
          'HTTP_HOST' => 'example.org',
          'HTTP_X_FORWARDED_PROTO' => 'https',
          'SERVER_PORT' => 443,
          'REQUEST_URI' => '/',
        ),
        array(
          'scheme' => 'https',
          'hostname' => 'example.org',
          'port' => 443,
          'username' => NULL,
          'password' => NULL,
          'path' => '/',
          'query' => NULL,
          'fragment' => NULL,
        ),
      ),
    );
  }
}

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

use PAGEmachine\Cors\AccessControl\Request;

/**
 * Testcase for PAGEmachine\Cors\AccessControl\Request
 */
class RequestTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

  /**
   * @test
   * @dataProvider crossOriginEnvironments
   *
   * @param array $environment
   * @param bool $isCrossOrigin
   */
  public function detectsCrossOriginRequests(array $environment, $isCrossOrigin) {

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
  public function detectsCredentials(array $environment, $hasCredentials) {

    $request = new Request($environment);
    $this->assertEquals($hasCredentials, $request->hasCredentials());
  }

  /**
   * @test
   * @dataProvider preflightEnvironments
   * @depends detectsCrossOriginRequests
   *
   * @param array $environment
   * @param boolean $isPreflight
   * @param string $requestMethod
   * @param array $requestHeaders
   */
  public function detectsPreflightRequests(array $environment, $isPreflight, $requestMethod, $requestHeaders) {

    $request = new Request($environment);
    $this->assertEquals($isPreflight, $request->isPreflight());
    $this->assertEquals($requestMethod, $request->getRequestMethod());
    $this->assertEquals($requestHeaders, $request->getRequestHeaders());
  }

  /**
   * @return array
   */
  public function crossOriginEnvironments() {

    return array(
      'Regular' => array(
        array(
          'HTTP_HOST' => 'example.org',
        ),
        FALSE,
      ),
      'Origin but regular' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.org',
        ),
        FALSE,
      ),
      'Different scheme' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.org',
          'HTTPS' => 'on',
        ),
        TRUE,
      ),
      'Different host' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
        ),
        TRUE,
      ),
      'Different port' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.org',
          'SERVER_PORT' => 8080,
        ),
        TRUE,
      ),
    );
  }

  /**
   * @return array
   */
  public function credentialEnvironments() {

    return array(
      'Regular' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
        ),
        FALSE,
      ),
      'Cookie' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'HTTP_COOKIE' => 'foo=bar',
        ),
        TRUE,
      ),
      'HTTP authentication' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'HTTP_AUTHORIZATION' => 'Authorization: Basic d2lraTpwZWRpYQ==', // wiki:pedia
        ),
        TRUE,
      ),
      'Without SSL client certificate' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'SSL_CLIENT_VERIFY' => 'NONE',
        ),
        FALSE,
      ),
      'With SSL client certificate' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'SSL_CLIENT_VERIFY' => 'SUCCESS',
        ),
        TRUE,
      ),
    );
  }

  /**
   * @return array
   */
  public function preflightEnvironments() {

    return array(
      'No preflight' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'REQUEST_METHOD' => 'GET',
        ),
        FALSE,
        NULL,
        array(),
      ),
      'Regular' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'REQUEST_METHOD' => 'OPTIONS',
        ),
        TRUE,
        NULL,
        array(),
      ),
      'Request method' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'REQUEST_METHOD' => 'OPTIONS',
          'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PUT',
        ),
        TRUE,
        'PUT',
        array(),
      ),
      'Request headers' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'REQUEST_METHOD' => 'OPTIONS',
          'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-Requested-With, Foo',
        ),
        TRUE,
        NULL,
        array('X-Requested-With', 'Foo'),
      ),
    );
  }
}

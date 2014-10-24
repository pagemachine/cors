<?php
namespace PAGEmachine\CORS\Tests\AccessControl;

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

use PAGEmachine\CORS\AccessControl\Request;

/**
 * Testcase for PAGEmachine\CORS\AccessControl\Request
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
          'SERVER_PORT' => 80,
        ),
        FALSE,
      ),
      'Origin but regular' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.org',
          'SERVER_PORT' => 80,
        ),
        FALSE,
      ),
      'Different scheme' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.org',
          'HTTPS' => 'on',
          'SERVER_PORT' => 443,
        ),
        TRUE,
      ),
      'Different host' => array(
        array(
          'HTTP_ORIGIN' => 'http://example.org',
          'HTTP_HOST' => 'example.com',
          'SERVER_PORT' => 80,
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

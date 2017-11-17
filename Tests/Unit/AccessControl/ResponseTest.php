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
use PAGEmachine\Cors\AccessControl\Response;

/**
 * Testcase for PAGEmachine\Cors\AccessControl\Response
 */
class ResponseTest extends UnitTestCase {

  /**
   * @test
   * @dataProvider properties
   *
   * @param string $allowedOrigin
   * @param boolean $allowCredentials
   * @param array $exposedHeaders
   * @param boolean $isPreflight
   * @param array $allowedMethods
   * @param array $allowedHeaders
   *
   * @param array $expectedHeaders
   * @param boolean $expectedSkipBodyAndExit
   */
  public function sendsCorrectHeaders($allowedOrigin, $allowCredentials, array $exposedHeaders, $isPreflight, array $allowedMethods, array $allowedHeaders, array $expectedHeaders, $expectedSkipBodyAndExit) {

    $headers = [];
    $skipBodyAndExit = FALSE;

    $response = $this->getMockBuilder(Response::class)
      ->setMethods(['sendHeader', 'skipBodyAndExit'])
      ->getMock();
    $response->expects($this->any())
      ->method('sendHeader')
      ->will($this->returnCallback(function($header) use (&$headers) {

        $headers[] = $header;
      }));
    $response->expects($this->atMost(1))
      ->method('skipBodyAndExit')
      ->will($this->returnCallback(function() use (&$skipBodyAndExit) {

        $skipBodyAndExit = TRUE;
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
  public function properties() {

    return [
      'Basic' => [
        'http://example.org',
        FALSE,
        [],
        FALSE,
        [],
        [],
        [
          'Access-Control-Allow-Origin: http://example.org',
        ],
        FALSE,
      ],
      'Credentials' => [
        'http://example.org',
        TRUE,
        [],
        FALSE,
        [],
        [],
        [
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Allow-Credentials: true',
        ],
        FALSE,
      ],
      'Exposed headers' => [
        'http://example.org',
        FALSE,
        ['X-Foo', 'X-Bar'],
        FALSE,
        [],
        [],
        [
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Expose-Headers: X-Foo, X-Bar',
        ],
        FALSE,
      ],
      'Preflight' => [
        'http://example.org',
        FALSE,
        [],
        TRUE,
        [],
        [],
        [
          'Access-Control-Allow-Origin: http://example.org',
        ],
        TRUE,
      ],
      'Preflight method' => [
        'http://example.org',
        FALSE,
        [],
        TRUE,
        ['PUT'],
        [],
        [
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Allow-Methods: PUT',
        ],
        TRUE,
      ],
      'Preflight headers' => [
        'http://example.org',
        FALSE,
        [],
        TRUE,
        [],
        ['X-Foo'],
        [
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Allow-Headers: X-Foo',
        ],
        TRUE,
      ],
    ];
  }
}

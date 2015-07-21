<?php
namespace PAGEmachine\CORS\AccessControl;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use PAGEmachine\CORS\Http\Uri;

/**
 * Represents a HTTP request
 */
class Request {

  /**
   * @var boolean
   */
  protected $isCrossOrigin = FALSE;

  /**
   * @var boolean
   */
  protected $isPreflight = FALSE;

  /**
   * @var boolean
   */
  protected $hasCredentials = FALSE;

  /**
   * @var Uri $origin
   */
  protected $origin;

  /**
   * @return Uri
   */
  public function getOrigin() {
    return $this->origin;
  }

  /**
   * @param Uri $origin
   * @return void
   */
  public function setOrigin(Uri $origin) {
    $this->origin = $origin;
  }

  /**
   * @var Uri $destination
   */
  protected $destination;

  /**
   * @return Uri
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * @param Uri $destination
   * @return void
   */
  public function setDestination(Uri $destination) {
    $this->destination = $destination;
  }

  /**
   * @var string $requestMethod
   */
  protected $requestMethod;

  /**
   * @return string
   */
  public function getRequestMethod() {
    return $this->requestMethod;
  }

  /**
   * @param string $requestMethod
   * @return void
   */
  public function setRequestMethod($requestMethod) {
    $this->requestMethod = $requestMethod;
  }

  /**
   * @var array $requestHeaders
   */
  protected $requestHeaders = array();

  /**
   * @return array
   */
  public function getRequestHeaders() {
    return $this->requestHeaders;
  }

  /**
   * @param array $requestHeaders
   * @return void
   */
  public function setRequestHeaders(array $requestHeaders) {
    $this->requestHeaders = $requestHeaders;
  }

  /**
   * Constructs a new request object
   *
   * @param array $environment Server environment (from $_SERVER)
   */
  public function __construct(array $environment) {

    $this->destination = Uri::fromEnvironment($environment);

    if (isset($environment['HTTP_ORIGIN'])) {

      $this->origin = new Uri($environment['HTTP_ORIGIN']);

      $this->isCrossOrigin = $this->origin->getScheme() != $this->destination->getScheme() ||
        $this->origin->getHostname() != $this->destination->getHostname() ||
        $this->origin->getPort() != $this->destination->getPort();

      $this->hasCredentials = isset($environment['HTTP_COOKIE']) ||
        isset($environment['HTTP_AUTHORIZATION']) ||
        isset($environment['SSL_CLIENT_VERIFY']) && $environment['SSL_CLIENT_VERIFY'] !== 'NONE';

      if ($environment['REQUEST_METHOD'] == 'OPTIONS') {

        $this->isPreflight = TRUE;

        if (isset($environment['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {

          $this->requestMethod = $environment['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
        }

        if (isset($environment['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {

          $this->requestHeaders = GeneralUtility::trimExplode(',', $environment['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        }
      }
    }
  }

  /**
   * Returns TRUE, if the current request is cross origin, FALSE otherwise
   *
   * A request is cross origin if one of scheme, host or protocol does not match
   *
   * @return boolean
   */
  public function isCrossOrigin() {
    return $this->isCrossOrigin;
  }

  /**
   * @param bool $isCrossOrigin
   * @return void
   */
  public function setIsCrossOrigin($isCrossOrigin) {
    $this->isCrossOrigin = $isCrossOrigin;
  }

  /**
   * Returns TRUE, if the current request has credentials, FALSE otherwise
   *
   * Credentials include cookies, HTTP authentication data and SSL client certificates
   *
   * @return boolean
   */
  public function hasCredentials() {
    return $this->hasCredentials;
  }

  /**
   * @param bool $hasCredentials
   * @return void
   */
  public function setHasCredentials($hasCredentials) {
    $this->hasCredentials = $hasCredentials;
  }

  /**
   * Returns TRUE if the current request is a preflight request, FALSE otherwise
   *
   * @return boolean
   */
  public function isPreflight() {
    return $this->isPreflight;
  }

  /**
   * @param boo $isPreflight
   * @return void
   */
  public function setIsPreflight($isPreflight) {
    $this->isPreflight = $isPreflight;
  }
}

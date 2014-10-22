<?php
namespace PAGEmachine\CORS\AccessControl;

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
 * Represents a HTTP request
 */
class Request {

  /**
   * @var boolean
   */
  protected $isCrossOrigin = FALSE;

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
}

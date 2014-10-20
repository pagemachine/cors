<?php
namespace PAGEmachine\CORS\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use PAGEmachine\CORS\AccessController;
use PAGEmachine\CORS\Http\Uri;

/**
 * Sends CORS-related headers as configured
 */
class ContentPostProcessorHook {

  /**
   * @var TypoScriptFrontendController
   */
  protected $frontendController;

  /**
   * Processes configuration and sends headers
   *
   * @param array $parameters
   * @param TypoScriptFrontendController $frontendController
   * @return void
   */
  public function process(array $parameters, TypoScriptFrontendController $frontendController) {

    if (!isset($_SERVER['HTTP_ORIGIN'])) {

      return;
    }

    $origin = new Uri($_SERVER['HTTP_ORIGIN']);
    $request = Uri::fromEnvironment($_SERVER);
    $accessController = new AccessController();

    if (!$accessController->isCrossOriginRequest($origin, $request)) {

      return;
    }

    $this->frontendController = $frontendController;
    $configuration = $this->getConfiguration($frontendController->config['config']);

    if (isset($configuration['allowCredentials'])) {

      $accessController->setAllowCredentials($configuration['allowCredentials']);
    }

    if (isset($configuration['allowHeaders'])) {

      $accessController->setAllowedHeaders($configuration['allowHeaders']);
    }

    if (isset($configuration['allowMethods'])) {

      $accessController->setAllowedMethods($configuration['allowMethods']);
    }

    if (isset($configuration['allowOrigin'])) {

      $accessController->setAllowedOrigins($configuration['allowOrigin']);
    }

    if (isset($configuration['allowOriginPattern'])) {

      $accessController->setAllowedOriginsPattern($configuration['allowOriginPattern']);
    }

    if (isset($configuration['exposeHeaders'])) {

      $accessController->setExposedHeaders($configuration['exposeHeaders']);
    }

    if (isset($configuration['maxAge'])) {

      $accessController->setMaximumAge($configuration['maxAge']);
    }

    $accessController->sendHeadersForOrigin($origin);
  }

  /**
   * Returns the parsed and processed configuration
   *
   * @param array $rawConfiguration Raw configuration from TypoScriptFrontendController::$config['config']
   * @return array
   */
  protected function getConfiguration(array $rawConfiguration) {

    $configuration = isset($rawConfiguration['cors.']) ? $rawConfiguration['cors.'] : array();

    foreach ($configuration as $option => $value) {

      // Perform stdWrap processing on all options
      if (StringUtility::isLastPartOfString($option, '.') && isset($value['stdWrap.'])) {

        unset($configuration[$option]);
        $option = substr($option, 0, -1);
        $value = $this->frontendController->cObj->stdWrap($configuration[$option], $value['stdWrap.']);
      }
      
      switch ($option) {

        case 'allowCredentials':

          $value = in_array($value, array('1', 'true'), TRUE);
          break;

        case 'allowHeaders':
        case 'allowMethods':
        case 'allowOrigin':
        case 'exposeHeaders':

          $value = GeneralUtility::trimExplode(',', $value);
          break;

        case 'maxAge':

          $value = (int) $value;
          break;
      }

      if ($option == 'allowOrigin.' && isset($value['pattern'])) {

        $option = 'allowOriginPattern';
        $value = $value['pattern'];
      }

      $configuration[$option] = $value;
    }

    return $configuration;
  }
}

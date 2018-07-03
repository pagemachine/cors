<?php
declare(strict_types = 1);

namespace Pagemachine\Cors\AccessControl;

/*
 * This file is part of the Pagemachine CORS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Represents a HTTP response
 */
class Response
{
    /**
     * @var string $allowedOrigin
     */
    protected $allowedOrigin;

    /**
     * @return string
     */
    public function getAllowedOrigin(): string
    {
        return $this->allowedOrigin;
    }

    /**
     * @param string $allowedOrigin
     * @return void
     */
    public function setAllowedOrigin(string $allowedOrigin)
    {
        $this->allowedOrigin = $allowedOrigin;
    }

    /**
     * @var bool $allowCredentials
     */
    protected $allowCredentials = false;

    /**
     * @return bool
     */
    public function getAllowCredentials(): bool
    {
        return $this->allowCredentials;
    }

    /**
     * @param bool $allowCredentials
     * @return void
     */
    public function setAllowCredentials(bool $allowCredentials)
    {
        $this->allowCredentials = $allowCredentials;
    }

    /**
     * @var string[] $exposedHeaders
     */
    protected $exposedHeaders = [];

    /**
     * @return string[]
     */
    public function getExposedHeaders()
    {
        return $this->exposedHeaders;
    }

    /**
     * @param string[] $exposedHeaders
     * @return void
     */
    public function setExposedHeaders(array $exposedHeaders)
    {
        $this->exposedHeaders = $exposedHeaders;
    }

    /**
     * @var bool $isPreflight
     */
    protected $isPreflight = false;

    /**
     * @return bool
     */
    public function isPreflight(): bool
    {
        return $this->isPreflight;
    }

    /**
     * @param bool $isPreflight
     * @return void
     */
    public function setPreflight(bool $isPreflight)
    {
        $this->isPreflight = $isPreflight;
    }

    /**
     * @var string[] $allowedMethods
     */
    protected $allowedMethods = [];

    /**
     * @return string[]
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }

    /**
     * @param string[] $allowedMethods
     * @return void
     */
    public function setAllowedMethods(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @var string[] $allowedHeaders
     */
    protected $allowedHeaders = [];

    /**
     * @return string[]
     */
    public function getAllowedHeaders()
    {
        return $this->allowedHeaders;
    }

    /**
     * @param string[] $allowedHeaders
     * @return void
     */
    public function setAllowedHeaders(array $allowedHeaders)
    {
        $this->allowedHeaders = $allowedHeaders;
    }

    /**
     * @var int $maximumAge
     */
    protected $maximumAge;

    /**
     * @return int
     */
    public function getMaximumAge(): int
    {
        return $this->maximumAge;
    }

    /**
     * @param int $maximumAge
     * @return void
     */
    public function setMaximumAge(int $maximumAge)
    {
        $this->maximumAge = $maximumAge;
    }

    /**
     * Sends all HTTP headers and the body as necessary
     *
     * @return void
     */
    public function send()
    {
        if ($this->getAllowedOrigin()) {
            $this->sendHeader($this->buildHeaderString('Access-Control-Allow-Origin', $this->getAllowedOrigin()));
        }

        if ($this->getAllowCredentials()) {
            $this->sendHeader($this->buildHeaderString('Access-Control-Allow-Credentials', 'true'));
        }

        if (count($this->getExposedHeaders())) {
            $this->sendHeader($this->buildHeaderString('Access-Control-Expose-Headers', $this->getExposedHeaders()));
        }

        if ($this->isPreflight()) {
            if (count($this->getAllowedMethods())) {
                $this->sendHeader($this->buildHeaderString('Access-Control-Allow-Methods', $this->getAllowedMethods()));
            }

            if (count($this->getAllowedHeaders())) {
                $this->sendHeader($this->buildHeaderString('Access-Control-Allow-Headers', $this->getAllowedHeaders()));
            }

          // No need for a body in a preflight response
            $this->skipBodyAndExit();
        }
    }

    /**
     * Builds an HTTP response header
     *
     * Multiple values are joined like "foo, bar"
     *
     * @param string $name Name of an HTTP header
     * @param mixed $value Simple or multivalued value
     * @return string
     */
    protected function buildHeaderString(string $name, $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return $name . ': ' . $value;
    }

    /**
     * Sends an HTTP response header
     *
     * @param string $header HTTP header
     * @return void
     */
    protected function sendHeader(string $header)
    {
        header($header);
    }

    /**
     * Skips the HTTP response body, sends an according
     * header (status 204) and stops script execution
     *
     * @return void
     */
    public function skipBodyAndExit()
    {
        HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_204);
    }
}

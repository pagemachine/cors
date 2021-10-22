<?php

declare(strict_types = 1);

namespace Pagemachine\Cors\Http;

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

/**
 * Represents a URI as object
 */
class Uri
{
    /**
     * @var string $scheme
     */
    protected $scheme = '';

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return void
     */
    public function setScheme(string $scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * @var string $hostname
     */
    protected $hostname = '';

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     * @return void
     */
    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @var int $port
     */
    protected $port = 0;

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * The normalized port, 443 for HTTPS and 80 for HTTP unless explicitly set
     *
     * @return int
     */
    public function getNormalizedPort(): int
    {
        $normalizedPort = $this->getPort();

        if ($normalizedPort === 0) {
            switch ($this->getScheme()) {
                case 'https':
                    $normalizedPort = 443;
                    break;

                case 'http':
                    $normalizedPort = 80;
                    break;
            }
        }

        return $normalizedPort;
    }

    /**
     * @param int $port
     * @return void
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    /**
     * @var string $username
     */
    protected $username = '';

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return void
     */
    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    /**
     * @var string $password
     */
    protected $password = '';

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return void
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @var string $path
     */
    protected $path = '';

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @var string $query
     */
    protected $query = '';

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return void
     */
    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    /**
     * @var string $fragment
     */
    protected $fragment = '';

    /**
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @param string $fragment
     * @return void
     */
    public function setFragment(string $fragment)
    {
        $this->fragment = $fragment;
    }

    /**
     * Builds a new Uri object
     *
     * @param string $uri A full request URI
     * @throws \InvalidArgumentException if a passed URI could not be parsed
     */
    public function __construct(string $uri = null)
    {
        if ($uri !== null) {
            $uriComponents = parse_url($uri);

            if ($uriComponents === false) {
                throw new \InvalidArgumentException(sprintf('Failed to parse URI "%s"', $uri), 1446565362);
            }

            $this->setPropertiesFromUriComponents($uriComponents);
        }
    }

    /**
     * Builds a new URI object from server environment
     *
     * @param array $environment Server environment (e.g. $_SERVER)
     * @return Uri
     */
    public static function fromEnvironment(array $environment): Uri
    {
        $uri = new self();
        $uri->setScheme(
            in_array($environment['HTTPS'] ?? null, ['on', 1], true)
            ||
            ($environment['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https'
            ? 'https'
            : 'http'
        );
        $uri->setHostname($environment['HTTP_HOST']);
        $uri->setPort((int)($environment['HTTP_X_FORWARDED_PORT'] ?? $environment['SERVER_PORT'] ?? 0));
        $uri->setUsername($environment['PHP_AUTH_USER'] ?? '');
        $uri->setPassword($environment['PHP_AUTH_PW'] ?? '');

        $requestUriParts = explode('?', $environment['REQUEST_URI'] ?? '', 2);
        $uri->setPath($requestUriParts[0]);

        if (isset($requestUriParts[1])) {
            $queryParts = explode('#', $requestUriParts[1], 2);
            $uri->setQuery($queryParts[0]);
            $uri->setFragment($queryParts[1] ?? '');
        }

        return $uri;
    }

    /**
     * Sets internal properties from URI components,
     * as returned by parse_url()
     *
     * @param array $uriComponents
     * @return void
     */
    protected function setPropertiesFromUriComponents(array $uriComponents)
    {
        // Map some component names to more readable properties
        static $componentPropertyMapping = [
            'host' => 'hostname',
            'user' => 'username',
            'pass' => 'password',
        ];

        foreach ($uriComponents as $component => $value) {
            $property = $componentPropertyMapping[$component] ?? $component;
            $this->$property = $value;
        }
    }
}

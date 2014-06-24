<?php

namespace React\Uri;

/**
 * Immutable value object representing a URI
 *
 * @property-read string $scheme
 * @property-read string $user
 * @property-read string $pass
 * @property-read string $host
 * @property-read int $port
 * @property-read string $path
 * @property-read string $query
 * @property-read string $fragment
 */
class Uri
{
    /**
     * Constants to identify the type of the host property
     */
    const HOSTTYPE_NONE = 0;
    const HOSTTYPE_IP   = 0b0001;
    const HOSTTYPE_IPv4 = 0b0011;
    const HOSTTYPE_IPv6 = 0b0101;
    const HOSTTYPE_NAME = 0b1000;

    /**
     * Standard parse_url() components
     */
    protected $scheme;
    protected $user;
    protected $pass;
    protected $host;
    protected $port;
    protected $path;
    protected $query;
    protected $fragment;

    /**
     * One of the self::HOSTTYPE_* constants
     *
     * @var int
     */
    protected $hostType;

    /**
     * Constructor parses a URI string to properties of the instance
     *
     * @param string $uri
     * @throws InvalidUriException
     */
    public function __construct($uri)
    {
        if ($uri instanceof self) {
            foreach (['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment', 'hostType'] as $name) {
                $this->$name = $uri->$name;
            }
        } else {
            if (!$components = parse_url((string)$uri)) {
                throw new InvalidUriException('Unable to parse ' . $uri . ' as a valid URI');
            }

            foreach ($components as $name => $value) {
                $this->$name = $value;
            }

            list($this->host, $this->hostType) = $this->normalizeHost($this->host);
        }
    }

    /**
     * Return a URI string with all available components
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getConnectionString();
    }

    /**
     * Get the value of a named property
     *
     * @param string $name
     * @return string|null
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    /**
     * Find and populate the value of $hostType
     *
     * Normalise IPv6 address to [square bracketed] form, as parse_url() will accept some URIs without it
     *
     * @param string $host
     * @return array
     */
    protected function normalizeHost($host)
    {
        if ($this->host === null) {
            return [null, self::HOSTTYPE_NONE];
        } else if (filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return [$host, self::HOSTTYPE_IPv4];
        } else if ($host = filter_var(trim($this->host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return [$host, self::HOSTTYPE_IPv6];
        }

        return [$host, self::HOSTTYPE_NAME];
    }

    /**
     * Get the value of a component based on whether it was requested and overriden
     *
     * Helper method for getConnectionString()
     *
     * @param string $name
     * @param array $components
     * @param array $overrides
     * @return string|null
     */
    private function getComponent($name, $components, $overrides)
    {
        if (isset($components[$name])) {
            if (isset($overrides[$name])) {
                return $overrides[$name];
            }

            return $this->$name;
        }

        return null;
    }

    /**
     * Construct a URI string from the component of this object
     *
     * @param array $components An array of component names to include in the result
     * @param array $overrides An associative array of values to use instead of the properties of this instance
     * @return string
     */
    public function getConnectionString(array $components = null, array $overrides = [])
    {
        $components = array_flip($components ?: ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment']);
        $result = '';

        if (null !== $scheme = $this->getComponent('scheme', $components, $overrides)) {
            $result .= $scheme . ':';
        }

        if (isset($components['host'])) {
            if (isset($overrides['host'])) {
                list($host, $hostType) = $this->normalizeHost($overrides['host']);
            } else if (isset($this->host)) {
                $host = $this->host;
                $hostType = $this->hostType;
            }

            if (isset($host, $hostType)) {
                $result .= '//';

                if (null !== $user = $this->getComponent('user', $components, $overrides)) {
                    $result .= $user;
                    if (null !== $pass = $this->getComponent('pass', $components, $overrides)) {
                        $result .= ':' . $pass;
                    }
                    $result .= '@';
                }

                $result .= $hostType === self::HOSTTYPE_IPv6 ? "[{$host}]" : $host;

                if (null !== $port = $this->getComponent('port', $components, $overrides)) {
                    $result .= ':' . $port;
                }
            }
        }

        if (null !== $path = $this->getComponent('path', $components, $overrides)) {
            $result .= $path;
        }

        if (null !== $query = $this->getComponent('query', $components, $overrides)) {
            $result .= '?' . $query;
        }

        if (null !== $fragment = $this->getComponent('fragment', $components, $overrides)) {
            $result .= '#' . $fragment;
        }

        return $result;
    }
}

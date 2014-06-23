<?php

namespace React\Uri;

/**
 * Mutable value object representing a URI
 *
 * @property string $host
 */
class MutableUri extends Uri
{
    /**
     * Standard parse_url() components as public properties
     */
    public $scheme;
    public $user;
    public $pass;
    public $port;
    public $path;
    public $query;
    public $fragment;

    /**
     * Set the value of a named property
     *
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value)
    {
        if ($name === 'host') {
            $this->host = $value;
            $this->findHostType();
        }
    }
}

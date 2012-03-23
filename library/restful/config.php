<?php
/**
 * RESTful API PHP Framework
 *
 * LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     Restful
 * @subpackage  Config
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 * @version
 */
namespace Restful;

/**
 * Restful Configuration Manager
 *
 * @package     Restful
 * @subpackage  Config
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Config implements \Iterator
{
    /**
     * Configuration
     * @var array
     */
    protected $_config;

    /**
     * String that separates nesting levels of configuration data identifiers
     *
     * @var string
     */
    protected $_nestSeparator = '.';

    /**
     * Expand the config keys in the Zend Framework way
     *
     * @param mixed $config
     * @return array
     */
    protected function _expandKeys($config)
    {
        if (is_object($config))
            $config = (array) $config;

        if (is_array($config))
        {
            foreach ($config as $key => & $value)
            {
                if (strpos($key, $this->_nestSeparator) !== false)
                {
                    $parts = explode($this->_nestSeparator, $key, 2);
                    if ($parts[0] && $parts[1])
                    {
                        if (isset($config[$parts[0]]))
                        {
                            if (is_array($config[$parts[0]]))
                                $config[$parts[0]][$parts[1]] = $value;
                            else
                                throw new \Exception('Cannot create sub-key ' . $parts[0] . ' as key already exists.');
                        }
                        else
                            $config[$parts[0]] = array($parts[1] => $value);
                    }
                    else
                        throw new \Exception('Invalid configuration key.');

                    unset($config[$key]);
                }
            }

            // Redo for each key
            foreach ($config as & $value)
                $value = $this->_expandKeys($value);
        }
        return $config;
    }

    /**
     * Load the flat configuration array
     *
     * @param array $config
     * @return @void
     */
    public function __construct(array $config)
    {
        $this->_config = $this->_expandKeys($config);
        if (is_array($this->_config))
            reset($this->_config);
        else
            throw new \Exception('Invalid configuration.');
    }

    /**
     * Get the configuration values
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_config[$key]))
        {
            if (is_scalar($this->_config[$key]))
                return $this->_config[$key];
            else
                return new Config($this->_config[$key]);
        }
    }

    /**
     * Protect against writings
     * @throw Exception
     */
    public function __set($key, $value)
    {
        throw new \Exception(__CLASS__ . ' objects are not modifiable.');
    }

    /**
     * Dump the whole configuration as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_config;
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function rewind()
    {
        reset($this->_config);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        //return current($this->_config);
        if (is_scalar(current($this->_config)))
            return current($this->_config);
        else
            return new Config(current($this->_config));
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_config);
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function next()
    {
        next($this->_config);
    }

    /**
     * Defined by Iterator interface
     *
     * @return boolean
     */
    public function valid()
    {
        // If
        if (key($this->_config) === null)
            return false;
        else
            return true;
    }
}
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
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 * @version
 */

/**
 * Restful Server Resource
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Server_Resource
{
    /**
     * Resource configuration
     * @var array
     */
    protected $_config = array(
                            'path'      => '/resources',
                            'max-age'   => 0,
                            );

    /**
     * The class reflection object
     * @var ReflectionClass
     */
    protected $_reflection;

    /**
     * Create the resource
     * @param array $config
     * @return void
     * @throw Exception
     */
    public function __construct($config)
    {
        // Configuration file
        $config = parse_ini_file(RESTFUL_PATH . $config, true);

        // Configuration
        $this->_config = array_merge($this->_config, $config);

        if (!isset($this->_config['resource']['class']))
            throw new Exception('Undefined class name.', 500);

        require_once(RESTFUL_PATH . $this->_config['resource']['path'] . DIRECTORY_SEPARATOR . $this->_config['resource']['class'] . '.php');

        $this->_reflection = new ReflectionClass($this->_config['resource']['class']);
    }

    /**
     * Return the instantiate object
     *
     * @return mixed
     */
    public function get()
    {
        return new $this->_config['resource']['class']($this->_config['model']);
    }

    /**
     * Alias for $this->get()
     *
     * @return mixed
     */
    public function __invoke()
    {
        return $this->get();
    }

    /**
     * Return the max-age
     *
     * @return integer
     */
    public function maxAge()
    {
        return $this->_config['max-age'];
    }

    /**
     * Return the methods list
     *
     * @return array
     */
    public function getMethods()
    {
        $methods = array();
        foreach ($this->_reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
            // Exclude magic methods
            $name = $method->getName();
            if (strpos($name, '__') !== 0)
                $methods[] = $method->getName();
        }
        return $methods;
    }

    /**
     * Check if a method exists
     *
     * @param string $method
     * @return boolean
     */
    public function hasMethod($method)
    {
        return in_array($method, $this->getMethods());
    }

    /**
     * Return the parameters list
     *
     * @param string $method
     * @return array
     * @throw Exception
     */
    public function getParams($method)
    {
        $method = $this->_reflection->getMethod($method);

        // Avoid non public methods
        if (!$method->isPublic())
            throw new Exception ('Method unavailable.', 500);

        // Try to read types from DocComment
        $types = array();
        $comment = $method->getDocComment();
        foreach (explode("\n", $comment) as $row)
        {
            if (preg_match('/^\s*\*\s*@param\s(\w+)\s\$(\w+)(\s|$)/', $row, $matches))
                $types[$matches[2]] = $matches[1];
        }

        // Build the params list
        $params = array();
        foreach ($method->getParameters() as $param)
        {
            $name = $param->getName();
            if (empty($types[$name]))
                $types[$name] = 'Undeclared';
            $params[$name] = array(
                                    'position'      => $param->getPosition(),
                                    'type'          => $types[$name],
                                    'is structured' => $param->isArray(),
                                    'can be null'   => $param->allowsNull(),
                                    'has default'   => $param->isDefaultValueAvailable(),
                                    'is optional'   => $param->isOptional(),
                                    );
            if ($params[$name]['is optional'])
                $params[$name]['defaults to'] = $param->getDefaultValue();
        }
        return $params;
    }

    /**
     * Check if a param list fit a method requirements
     *
     * @param string $method
     * @param array $params
     * @return boolean
     * @throw Exception
     */
    public function fitParams($method, $params)
    {
        $params = (array) $params;
        $methodParams = $this->getParams($method);
        $pos = 0;
        foreach ($params as $name => $value)
        {
            if (!isset($methodParams[$name]))
                return;

            if ($methodParams[$name]['position'] != $pos)
                return;

            // TODO More type investigation
            if ($methodParams[$name]['is structured'] && is_scalar($value))
                return;
            if (!$methodParams[$name]['is structured'] && !is_scalar($value))
                return;

            unset($methodParams[$name]);
            $pos++;
        }

        // Remaining params
        foreach ($methodParams as $param)
        {
            if (!$param['is optional'])
                return;
        }

        return true;
    }
}

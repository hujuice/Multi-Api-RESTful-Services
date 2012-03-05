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
                            'path'          => '/resources',
                            'httpMethod'    => 'GET',
                            'max-age'       => 0,
                            );

    /**
     * The class reflection object
     * @var ReflectionClass
     */
    protected $_reflection;

    /**
     * Read a DocComment
     *
     * @param string $comment
     * @return array
     */
    protected function _docComment($comment)
    {
        // TODO More type mapping based on http://www.php.net/manual/en/language.types.type-juggling.php
        $fields = array(
                        'desc'      => '',
                        'purpose'   => '',
                        'params'    => array(),
                        'return'    => array(),
                        );
        foreach (explode("\n", $comment) as $row)
        {
            if (preg_match('/^\s*\*\s*(\w.*)$/', $row, $matches))
            {
                if ($fields['desc'])
                    $fields['purpose'] .= $matches[1] . ' ';
                else
                    $fields['desc'] = $matches[1];
            }
            else if (preg_match('/^\s*\*\s*@param\s(\w+)\s\$(\w+)\s*(.*)$/', $row, $matches))
                $fields['params'][$matches[2]] = array('type' => $matches[1], 'desc' => $matches[3]);
            else if (preg_match('/^\s*\*\s*@return\s(\w+)\s*(.*)$/', $row, $matches))
                $fields['return'] = array('type' => $matches[1], 'desc' => $matches[2]);
        }

        return $fields;
    }

    /**
     * Change array key to lower
     *
     * @param array $array
     * @return array
     */
    protected function _key2lower($array)
    {
        $lower = array();
        $array = (array) $array;
        foreach ($array as $key => $value)
            $lower[strtolower($key)] = $value;
        return $lower;
    }

    /**
     * Create the resource
     * @param array $config
     * @return void
     * @throw Exception
     */
    public function __construct($config)
    {
        // Configuration file
        $config = parse_ini_file(API_PATH . $config, true);

        // Configuration
        $this->_config = array_merge($this->_config, $config);

        if (!isset($this->_config['resource']['class']))
            throw new Exception('Undefined class name.');

        if (!in_array($this->_config['resource']['httpMethod'], Restful_Server_Request::$httpMethods))
            throw new Exception('The method resource is not allowed.');

        require_once(API_PATH . $this->_config['resource']['path'] . DIRECTORY_SEPARATOR . $this->_config['resource']['class'] . '.php');

        $this->_reflection = new ReflectionClass($this->_config['resource']['class']);
    }

    /**
     * Give the resource or method description
     *
     * @param string|null $method
     * @return array
     */
    public function desc($method = null)
    {
        if ($method)
            $comment = $this->_docComment($this->_reflection->getMethod($this->checkMethod($method))->getDocComment());
        else
            $comment = $this->_docComment($this->_reflection->getDocComment());
        return array('desc' => $comment['desc'], 'purpose' => $comment['purpose']);
    }

    /**
     * Give the wanted HTTP method
     *
     * @return string
     */
    public function httpMethod()
    {
        return $this->_config['resource']['httpMethod'];
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
            // Method names to lowercase
            $name = $method->getName();
            if (strpos($name, '__') !== 0) // Exclude the '__' methods
                $methods[] = strtolower($method->getName());
        }
        return $methods;
    }

    /**
     * Check if a method exists and gives its normalized name
     *
     * @param string $method
     * @return string
     */
    public function checkMethod($method)
    {
        $method = strtolower($method);
        if (in_array($method, $this->getMethods()))
            return $method;
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
        if ($methodName = $this->checkMethod($method))
        {
            $method = $this->_reflection->getMethod($methodName);

            // Try to read types from DocComment
            $types = array();

            $comment = $this->_docComment($method->getDocComment());
            $comment = $this->_key2lower($comment['params']);

            // Build the params list
            $params = array();
            foreach ($method->getParameters() as $param)
            {
                $name = strtolower($param->getName());
                $params[$name] = array(
                                        'position'      => $param->getPosition(),
                                        'is structured' => $param->isArray(),
                                        'has default'   => $param->isDefaultValueAvailable(),
                                        'is optional'   => $param->isOptional(),
                                        );
                if ($params[$name]['is optional'])
                    $params[$name]['defaults to'] = $param->getDefaultValue();

                if (isset($comment[$name]))
                {
                    $params[$name]['type'] = $comment[$name]['type'];
                    $params[$name]['desc'] = $comment[$name]['desc'];
                }
            }
            return $params;
        }
        else
            throw new Exception('Unknown method \'' . $method . '\'.');
    }

    /**
     * Check if a param list fit a method requirements normalize it
     *
     * Keys are changed to lower and unneeded params are discaded.
     *
     * @param string $method
     * @param array $params
     * @return array
     * @throw Exception
     */
    public function checkParams($method, $params)
    {
        try
        {
            $params = $this->_key2lower($params);
            $methodParams = $this->getParams($method);
            $goodParams = array();

            foreach($methodParams as $name => $value)
            {
                if (isset($params[$name]))
                {
                    if (!$value['is structured'] && !is_scalar($params[$name]))
                        return;
                    else if ($value['is structured'] && is_scalar($params[$name]))
                        $goodParams[$name] = (array) $params[$name];
                    else
                        $goodParams[$name] = $params[$name];
                }
                else if (!$value['is optional'])
                    return;
            }

            return $goodParams;
        }
        catch (Exception $e)
        {
            throw new Exception('Unable to find the \'' . $method . '\' method.', 500, $e);
        }
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
     * Execute the requested method
     *
     * @param string $method
     * @param array $params
     * @return mixed
     * @throw Exception
     */
    public function exec($method, $params)
    {
        if (!$method = $this->checkMethod($method))
            throw new Exception('Unknown method \'' . $method . '\'.');

        if (!is_array($params = $this->checkParams($method, $params)))
            throw new Exception('Incomplete or bad parameter structure.');

        try
        {
            $model = new $this->_config['resource']['class']($this->_config['model']);
            return call_user_func_array(array($model, $method), $params);
        }
        catch (Exception $e)
        {
            throw new Exception('The resource model gave an invalid response.', 500, $e);
        }
    }
}

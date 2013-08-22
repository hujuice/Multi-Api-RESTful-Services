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
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 * @version     1.0
 */
namespace Restful\Server;

/**
 * Restful Server Resource
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Resource
{
    /**
     * Resource configuration
     * @var array
     */
    protected $_config;

    /**
     * The class reflection object
     * @var ReflectionClass
     */
    protected $_reflection;

    /**
     * Parse a DocComment
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
                        'return'    => 'void',
                        );
        // http://www.phpdoc.org/docs/latest/for-users/anatomy-of-a-docblock.html#short-description
        // http://www.phpdoc.org/docs/latest/for-users/anatomy-of-a-docblock.html#long-description
        $text = array();
        foreach (explode("\n", $comment) as $row)
        {
            if ($row = preg_replace('/^\s*\*\s*/', '', $row))
            {
                if (preg_match('/^(\w.*)\s*$/', $row, $matches))
                    $text[] = $matches[1];
                else if (preg_match('/^@param\s([\w|]+)\s\$(\w+)\s*(.*)$/', $row, $matches))
                    $fields['params'][$matches[2]] = array('desc' => $matches[3], 'type' => $matches[1]);
                else if (preg_match('/^@return\s([\w|]+)\s*(.*)$/', $row, $matches))
                    $fields['return'] = array('desc' => $matches[2], 'type' => $matches[1]);
            }
            else
                $text[] = '';
        }
        
        $field = 'desc';
        foreach ($text as $text_row)
        {
            if ($fields['desc'] && empty($text_row))
                $field = 'purpose';
            else
                $fields[$field] .= $text_row . PHP_EOL;

            if (preg_match('/^.+\.\s*$/', $text_row))
                $field = 'purpose';
        }

        return $fields;
    }

    /**
     * Analyze the method params
     *
     * @param string $methodName
     * @return array
     */
    protected function _methodParams($methodName)
    {
        $method = $this->_reflection->getMethod($methodName);

        // Try to read types from DocComment
        $types = array();

        $comment = $this->_docComment($method->getDocComment());
        if (isset($comment['params']))
            $comment['params'] = $this->_key2lower($comment['params']);
        else
            $comment['params'] = array();

        // Build the params list
        $params = array();
        foreach ($method->getParameters() as $param)
        {
            $name = strtolower($param->getName());
            $params[$name] = array();

            if (isset($comment['params'][$name]))
            {
                $params[$name]['desc'] = $comment['params'][$name]['desc'];
                $params[$name]['type'] = $comment['params'][$name]['type'];
            }
            else
            {
                $params[$name]['desc'] = '';
                $params[$name]['type'] = 'unknown';
            }

            $params[$name]['is_optional'] = $param->isOptional();

            if ($params[$name]['is_optional'])
                $params[$name]['defaults_to'] = $param->getDefaultValue();
            else
                $params[$name]['defaults_to'] = null;

            $params[$name]['position'] = $param->getPosition();
        }

        return $params;
    }

    /**
     * Map input params to method params
     *
     * @param array $methodParams
     * @param arrat $inputParams
     * @return array
     */
    protected function _mapParams(array $methodParams, array $inputParams)
    {
        $inputParams = $this->_key2lower($inputParams);
        $params = array();

        foreach($methodParams as $name => $value)
        {
            if (isset($inputParams[$name]))
            {
                // TODO More complex type check
                $params[$name] = $inputParams[$name];
            }
            else if ($value['is_optional'])
                $params[$name] = $value['defaults_to'];
            else
                return;
        }

        return $params;
    }

    /**
     * Change array key to lower
     *
     * @param array $array
     * @return array
     */
    protected function _key2lower(array $array)
    {
        $lower = array();
        $array = (array) $array;
        foreach ($array as $key => $value)
            $lower[strtolower($key)] = $value;
        return $lower;
    }

    /**
     * Create the resource
     *
     * @param string $className
     * @param array $construct
     * @param string $httpMethod
     * @param integer $max_age
     * @return void
     * @throw Exception
     */
    public function __construct($className, array $construct = array(), $httpMethod = 'GET', $max_age = 600)
    {
        if ($httpMethod)
        {
            if (in_array($httpMethod, Request::$httpMethods))
                $this->_config['httpMethod'] = $httpMethod;
            else
                throw new \Exception('The resource HTTP method (' . $httpMethod . ') is not allowed.');
        }
        else
            $this->_config['httpMethod'] = 'GET';

        if ($this->_config['httpMethod'] == 'GET')
        {
            if ($max_age >= 0)
                $this->_config['maxAge'] = (integer) $max_age;
            else
                throw new \Exception('\'max-age\' MUST be a non-negative integer (' . $max_age . ' given).');
        }
        else
            $this->_config['maxAge'] = 0;

        $this->_config['construct'] = (array) $construct;

        $this->_reflection = new \ReflectionClass('\\' . $className);

        // Constructor
        if ($this->_reflection->hasMethod('__construct'))
        {
            $params = $this->_methodParams('__construct');
            if (!is_array($this->_config['construct'] = $this->_mapParams($params, $this->_config['construct'])))
                throw new \Exception('Invalid constructor arguments (' . json_encode($this->_config['construct']) . ').');
        }
    }

    /**
     * Give the resource or method description
     *
     * @param string $method
     * @return array
     */
    public function desc($method = '')
    {
        if ($method)
        {
            $comment = $this->_docComment($this->_reflection->getMethod($this->checkMethod($method))->getDocComment());
            return array('desc' => $comment['desc'], 'purpose' => $comment['purpose'], 'return' => $comment['return']);
        }
        else
        {
            $comment = $this->_docComment($this->_reflection->getDocComment());
            return array('desc' => $comment['desc'], 'purpose' => $comment['purpose']);
        }
    }

    /**
     * Give the wanted HTTP method
     *
     * @return string
     */
    public function httpMethod()
    {
        return $this->_config['httpMethod'];
    }

    /**
     * Return the max-age
     *
     * @return integer
     */
    public function maxAge()
    {
        return $this->_config['maxAge'];
    }

    /**
     * Return the methods list
     *
     * @return array
     */
    public function getMethods()
    {
        $methods = array();
        foreach ($this->_reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
        {
            // Method names to lowercase
            $name = $method->getName();
            if (strpos($name, '__') !== 0) // Exclude the '__' methods
                $methods[] = strtolower($name);
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
            return $this->_methodParams($methodName);
        else
            throw new \Exception('Unknown method (' . $method . ').');
    }

    /**
     * Check if a param list fit a method requirements normalize it
     *
     * Keys are changed to lower and unneeded params are discaded.
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public function checkParams($method, array $params)
    {
        $params = $this->_key2lower($params);
        $methodParams = $this->getParams($method);

        $params = $this->_mapParams($methodParams, $params);
        if (is_array($params))
            return $params;
    }

    /**
     * Execute the requested method
     *
     * @param string $method
     * @param array $params
     * @return mixed
     * @throw Exception
     */
    public function exec($method, array $params)
    {
        if (!$method = $this->checkMethod($method))
            throw new \Exception('Unknown method (' . $method . ').');

        if (!is_array($params = $this->checkParams($method, $params)))
            throw new \Exception('Incomplete or bad parameter structure.');

        $model = $this->_reflection->newInstanceArgs($this->_config['construct']);
        return $this->_reflection->getMethod($method)->invokeArgs($model, $params);
    }
}

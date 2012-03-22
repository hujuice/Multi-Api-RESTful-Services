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
 * @version
 */
namespace Restful\Server;

/**
 * Restful Server Discovery Tool
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Discover
{
    /**
     * Base URL
     * @var string
     */
    protected $_baseUrl;

    /**
     * Available resources
     * @var array
     */
    protected $_resources;

    /**
     * Build the discover
     *
     * @param array $resources
     * @return void
     */
    public function __construct(array $resources, $baseUrl = '')
    {
        $this->_baseUrl = '/' . trim($baseUrl, '/');
        $this->_resources = $resources;
    }

    /**
     * Give the resource catalogue
     *
     * @return array
     */
    public function resources()
    {
        $resources = array('resources' => array());
        foreach ($this->_resources as $name => $resource)
        {
            $resources['resources'][$name] = $resource->desc();
            $resources['resources'][$name]['HTTP'] = $resource->httpMethod();
            $resources['resources'][$name]['methods'] = $this->methods($name);
            $resources['resources'][$name]['discover'] = 'http://' . $_SERVER['SERVER_NAME'] . $this->_baseUrl . 'discover/methods?resource=' . $name;
        }

        return $resources;
    }

    /**
     * Give the methods for the resource
     *
     * @param string $resource
     * @return array
     */
    public function methods($resource)
    {
        $methods = array();
        if (isset($this->_resources[$resource]))
        {
            foreach ($this->_resources[$resource]->getMethods() as $method)
            {
                //$methods[$method] = $this->_resources[$resource]->desc($method);
                $desc = $this->_resources[$resource]->desc($method);
                $methods[$method]['desc'] = $desc['desc'];
                $methods[$method]['purpose'] = $desc['purpose'];
                $methods[$method]['params'] = $this->params($resource, $method);
                $methods[$method]['return'] = $desc['return'];
                $methods[$method]['discover'] = 'http://' . $_SERVER['SERVER_NAME'] . $this->_baseUrl . 'discover/params?resource=' . $resource . '&method=' . $method;
            }

            return $methods;
        }
    }

    /**
     * Give the params for the method/resource
     *
     * @param string $resource
     * @param string $method
     * @return array
     */
    public function params ($resource, $method)
    {
        $params = array();
        if (isset($this->_resources[$resource]))
        {
            if ($method = $this->_resources[$resource]->checkMethod($method))
                return $this->_resources[$resource]->getParams($method);
        }
    }
}
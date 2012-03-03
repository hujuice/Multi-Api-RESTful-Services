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
 * Restful Server Router
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Server_Router
{
    /**
     * The resources array
     * @var array
     */
    protected $_resources;

    /**
     * Base URL
     * @var string
     */
    protected $_baseUrl;

    /**
     * The selected resource
     * @var string
     */
    protected $_resourceName;

    /**
     * The selected method
     * @var string
     */
    protected $_method;

    /**
     * The selected params
     * @var array
     */
    protected $_params;

    /**
     * The requested Content-Type
     * @var string
     */
    protected $_contentType;

    /**
     * Reset the selection
     *
     * @return void
     */
    protected function _reset()
    {
        $this->_resourceName = null;
        $this->_method = null;
        $this->_params = null;
    }

    /**
     * Acquire the base URL
     *
     * @param array $resources
     * @param string $baseUrl
     * @return void
     */
    public function __construct($resources, $baseUrl = '')
    {
        $this->_resources = $resources;
        $this->_baseUrl = $baseUrl;
        $this->_reset();
    }

    /**
     * Find resource, method and params for the given request
     *
     * @param Restful_Server_Request $request
     * @return boolean
     * @throw Exception
     */
    public function route(Restful_Server_Request $request)
    {
        // TODO: find a way to map http methods to the classes via configuration
        if ($request->method != 'GET')
            throw new Exception ('Only the GET method is implemented.', 405);

        if ($path = trim(parse_url($request->uri, PHP_URL_PATH), '/'))
        {
            $parts = explode('/', $path);
            if (isset($this->_resources[$parts[0]]))
            {
                $this->_resourceName = $parts[0];
                if (empty($parts[1]))
                    return;

                if ($this->_resources[$parts[0]]->hasMethod($parts[1]))
                {
                    $this->_method = $parts[1];

                    $data = $request->data ? $request->data : $request->query;

                    if ($this->_resources[$parts[0]]->fitParams($parts[1], $data))
                    {
                        $this->_params = $data;
                        return true;
                    }
                    else
                    {
                        $this->_reset();
                        throw new Exception('Parameters are incomplete or invalid.', 400);
                    }
                }
                else
                {
                    $this->_reset();
                    throw new Exception('The ' . $parts[0] . ' resource doesn\'t have a ' . $parts[1] . ' method.', 404);
                }
            }
            else
            {
                $this->_reset();
                throw new Exception($parts[0] . ' is an unknown resource.', 404);
            }

            return true;
        }
        else
            $this->_reset(); // No exception here
    }

    /**
     * Give the selcted resource name
     *
     * @return string
     */
    public function getResource()
    {
        return $this->_resourceName;
    }

    /**
     * Give the selcted method name
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Give the selcted params values
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Discover resources, method and params
     *
     * @return array
     */
    public function discover()
    {
        $dics = array();
        foreach ($this->_resources as $name => $resource)
        {
            $disc[$name] = array();
            foreach ($resource->getMethods() as $method)
            {
                $disc[$name][$method] = array();
                foreach ($resource->getParams($method) as $param => $info)
                    $disc[$name][$method][$param] = $info;
            }
        }
        return $disc;
    }
}

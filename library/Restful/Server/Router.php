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
     * The selected params
     * @var array
     */
    protected $_params = array(
                            'resource'      => null,
                            'method'        => null,
                            'params'        => null,
                            'contentType'   => null,
                            );

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
    }

    /**
     * Find resource, method and params for the given request
     *
     * @param Restful_Server_Request $request
     * @return boolean
     */
    public function route(Restful_Server_Request $request)
    {
        // Content-Type
        foreach ($request->accept as $content_type)
        {
            if (in_array($content_type, Restful_Server_Response::$contentTypes))
            {
                $this->_params['contentType'] = $content_type;
                break;
            }
            else if ('*/' == substr($content_type, 0, 2))
            {
                $subtype = substr($content_type, 1); // Starts with '/' to be comparable with strstr
                foreach (Restful_Server_Response::$contentTypes as $allowed)
                {
                    if (strstr($allowed, '/') == $subtype)
                    {
                        $this->_params['contentType'] = $allowed;
                        break 2;
                    }
                }
            }
            else if ('/*' == substr($content_type, -2))
            {
                $type = substr($content_type, 0, -2); // Now strstr will exclude the needle (see docs)
                foreach (Restful_Server_Response::$contentTypes as $allowed)
                {
                    if (strstr($allowed, '/', true) == $subtype)
                    {
                        $this->_params['contentType'] = $allowed;
                        break 2;
                    }
                }
            }
        }

        // Path
        if ($path = trim(parse_url(substr($request->uri, strlen($this->_baseUrl)), PHP_URL_PATH), '/'))
        {
            $parts = explode('/', $path);
            $parts[0] = strtolower($parts[0]);
            if (isset($this->_resources[$parts[0]]) && ($request->method == ($this->_resources[$parts[0]]->httpMethod())))
            {
                $resource = $parts[0];

                if (empty($parts[1]))
                {
                    $this->_params['resource'] = 'discover';
                    $this->_params['method']   = 'methods';
                    $this->_params['params'] = array('resource' => $resource);
                }
                else
                {
                    $this->_params['resource'] = $resource;

                    // Check for extension
                    $method = explode('.', $parts[1], 2);
                    if (isset($method[1]))
                    {
                        if (isset(Restful_Server_Response::$contentTypes[$method[1]]))
                        {
                            $this->_params['contentType'] = Restful_Server_Response::$contentTypes[$method[1]];
                            $method = $method[0];
                        }
                        else
                            $method = null;
                    }
                    else
                        $method = $method[0];

                    if ($method && ($this->_params['method'] = $this->_resources[$this->_params['resource']]->checkMethod($method)))
                    {
                        // Params
                        if ('GET' == $request->method)
                            $data = $request->query;
                        else
                            $data = $request->data;
                        $this->_params['params'] = $this->_resources[$this->_params['resource']]->checkParams($this->_params['method'], $data);
                    }
                }
            }
        }
        else
        {
            $this->_params['resource'] = 'discover';
            $this->_params['method']   = 'resources';
            $this->_params['params'] = array();
        }

        if ($this->_params['contentType'] &&
            $this->_params['resource'] &&
            $this->_params['method'] &&
            is_array($this->_params['params']))
            return true;
    }

    /**
     * Give the selcted params values
     *
     * @param string $elem
     * @return array
     */
    public function getRouteParams($elem = null)
    {
        if ($elem)
        {
            if (isset($this->_params[$elem]))
                return $this->_params[$elem];
        }
        else
            return $this->_params;
    }

    /**
     * Discover resources, method and params
     *
     * @param string|null $resource
     * @return array
     */
    public function discover($resource = null)
    {
        if ($resource && isset($this->_resources[$resource]))
            $resources = array($resource => $this->_resources[$resource]);
        else
            $resources = $this->_resources;

        $disc = array();
        foreach ($resources as $name => $resource)
        {
            $disc[$name] = $resource->desc();
            $disc[$name]['methods'] = array();
            foreach ($resource->getMethods() as $method)
            {
                $disc[$name]['methods'][$method] = $resource->desc($method);
                $disc[$name]['methods'][$method]['params'] = array();
                foreach ($resource->getParams($method) as $param => $info)
                    $disc[$name]['methods'][$method]['params'][$param] = $info;
            }
        }
        return $disc;
    }
}

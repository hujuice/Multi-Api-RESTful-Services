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
 * Restful Server Router
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Router
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
     * Prepend another content-type
     * @param string $mime
     * @return boolean
     */
    protected function _addContentType($mime, & $content_types)
    {
        if(isset(Response::$contentTypes[$mime]))
        {
            // Check if already in the array
            if (($key = array_search(Response::$contentTypes[$mime], $content_types)) !== false)
                unset($content_types[$key]);
            array_unshift($content_types, Response::$contentTypes[$mime]);
            return true;
        }
        else
            return false;
    }

    /**
     * Acquire the base URL
     *
     * @param array $resources
     * @param string $baseUrl
     * @return void
     */
    public function __construct(array $resources, $baseUrl = '')
    {
        $this->_resources = $resources;
        $this->_baseUrl = $baseUrl;
    }

    /**
     * Find resource, method and params for the given request
     *
     * @param Restful\Server\Request $request
     * @return boolean
     * @throw Exception
     */
    public function route(Request $request)
    {
        // Valid Content-Types
        $content_types = array();
        foreach ($request->accept as $content_type)
        {
            if (in_array($content_type, Response::$contentTypes))
            {
                $content_types = array($content_type);
                break;
            }
            else if ('*/*' == $content_type)
            {
                $content_types = array_values(Response::$contentTypes);
                break;
            }
            else if ('*/' == substr($content_type, 0, 2))
            {
                $subtype = substr($content_type, 1); // Starts with '/' to be comparable with strstr
                foreach (Response::$contentTypes as $allowed)
                {
                    if (strstr($allowed, '/') == $subtype)
                    {
                        $content_types[] = $allowed;
                        break 2;
                    }
                }
            }
            else if ('/*' == substr($content_type, -2))
            {
                $subtype = substr($content_type, 0, -2); // Now strstr will exclude the needle (see docs)
                foreach (Response::$contentTypes as $allowed)
                {
                    if (strstr($allowed, '/', true) == $subtype)
                    {
                        $content_types[] = $allowed;
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

            // Checks
            if (!isset($this->_resources[$parts[0]]))
                throw new \Exception('Invalid resource ' . $parts[0] . '.');
            if ($request->method != $this->_resources[$parts[0]]->httpMethod())
                throw new \Exception('Invalid HTTP method for this resource. (Request:' . $request->method . ', Resource: ' . $this->_resources[$parts[0]]->httpMethod() . ')');

            // Route
            $resource = $parts[0];

            if ('ui' == $resource)
            {
                $content_types = array(Response::$contentTypes['js']);
                $this->_params['resource'] = 'ui';
                $this->_params['method']   = 'get';
                $this->_params['params'] = array();
            }
            else if (empty($parts[1]))
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
                    $this->_addContentType($method[1], $content_types);
                $method = $method[0];

                if ($this->_params['method'] = $this->_resources[$this->_params['resource']]->checkMethod($method))
                {
                    // Params
                    if (('GET' == $request->method) || ('PUT' == $request->method))
                        $data = $request->query;
                    elseif ('POST' == $request->method)
                        $data = $request->data;
                    else // PUT
                        $data = $request->data + $request->query; // Is this kind of merge useful?
                        
                    // Check for restful_format
                    if (isset($data['content_type']) && $this->_addContentType($data['content_type'], $content_types))
                        unset($data['content_type']);

                    // Check if it is a (valid) JSONp request
                    if (!empty($data['jsonp']) && in_array(Response::$contentTypes['js'], $content_types))
                    {
                        // Add a hard control over the $data['jsonp'] value (risk of JS injection)
                        if (preg_match('/[^\w]/', $data['jsonp']))
                            throw new Exception('Please, provide a valid function name for jsonp: word character only (' . $data['jsonp'] . ' given).');

                        $content_types = array(Response::$contentTypes['js']);
                        $this->_params['jsonp'] = $data['jsonp'];
                        unset($data['jsonp']);
                    }
                    else if (empty($data['jsonp']) && ($content_types[0] == Response::$contentTypes['js']))
                        $this->_params['jsonp'] = 'parseResponse';

                    $this->_params['params'] = $this->_resources[$this->_params['resource']]->checkParams($this->_params['method'], $data);
                }
            }
        }
        else
        {
            $this->_params['resource'] = 'discover';
            $this->_params['method']   = 'resources';
            $this->_params['params'] = array();
        }

        // Content-Type by order
        $this->_params['contentType'] = $content_types[0];

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
    public function getRouteParams($elem = '')
    {
        if ($elem)
        {
            if (isset($this->_params[$elem]))
                return $this->_params[$elem];
        }
        else
            return $this->_params;
    }
}

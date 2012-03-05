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
 * Complete Restful Server
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Server
{
    /**
     * Service configuration
     * @var array
     */
    protected $_config = array(
                            'baseUrl'       => '',
                            'debug'         => false,
                            );

    /**
     * The resources array
     * @var array
     */
    protected $_resources;

    /**
     * The request
     * @var Restful_Server_Request
     */
    protected $_request;

    /**
     * Calcualte an etag base on content type and data array
     *
     * @param array $data
     * @param string $content_type
     * @return string
     */
    public function _etag($data, $content_type)
    {
        return md5(serialize(array('data' => $data, 'contentType' => $content_type)));
    }

    /**
     * Explore exception stack
     *
     * @param Exception $e
     * @return string
     */
    protected function _digException($e)
    {
        if ($prev = $e->getPrevious())
        {
            return  "\n" .
                    'Previous Exception' . "\n" .
                    "\n" . 'Message: ' . $prev->getMessage() . "\n" .
                    "\n" . 'Location: ' . $prev->getFile() . ':' . $exception->getLine() . "\n" .
                    "\n" . 'Stack trace:' . "\n" . $prev->getTraceAsString() . "\n" .
                    "\n" . $this->_digException($prev);
        }
    }

    /**
     * Error handler
     *
     * @param integer $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return boolean
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if ($this->_config['debug'])
            return false; // Back to ordinary errors
        else
            Restful_Server_Response::response(500, 'Something went wrong in the framework.' . "\n" . $errstr, 'text/plain', 0, null, array()); // Will exit
    }

    /**
     * Exception handler
     *
     * @param Exception $exception
     * @return void
     */
    public function exceptionHandler($exception)
    {
        if ($exception->getCode())
            $code = $exception->getCode();
        else
            $code = 500;

        if ($this->_config['debug'])
        {
            $body = 'Internal Exception' . "\n" .
                    "\n" . 'Message: ' . $exception->getMessage() . "\n" .
                    "\n" . 'Location: ' . $exception->getFile() . ':' . $exception->getLine() . "\n" .
                    "\n" . 'Stack trace:' . "\n" . $exception->getTraceAsString() . "\n" .
                    $this->_digException($exception);

            Restful_Server_Response::response($code, $body, 'text/plain', 0, null, array()); // Will exit
        }
        else
            Restful_Server_Response::response($code, 'Something went wrong in the framework.' . "\n" . $exception->getMessage(), 'text/plain', 0, null, array()); // Will exit
    }

    /**
     * General autoloader for the whole application
     *
     * @param string $class
     * @return void
     */
    public function autoloader($class)
    {
        require_once(str_replace('_', DIRECTORY_SEPARATOR, (string) $class) . '.php');
    }

    /**
     * Get config and create the resource
     *
     * @param string $config
     * @return void
     * @throw Exception
     */
    public function __construct($config)
    {
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));
        date_default_timezone_set('GMT'); // To be validated in http://redbot.org/
        ini_set('default_mimetype', 'text/plain');
        ini_set('html_errors', false);

        // Register the autoloader
        spl_autoload_register(array($this, 'autoloader'));

        // Configuration file
        $config = parse_ini_file($config, true);

        // API behaviour
        $this->_config = array_merge($this->_config, array_filter($config['server']));

        // Resources
        foreach($config['resources'] as $resourceName => $resourceConfig)
            $this->_resources[strtolower($resourceName)] = new Restful_Server_Resource($resourceConfig);

        // Route
        $this->_router = new Restful_Server_Router($this->_resources, $this->_config['baseUrl']);
    }

    /**
     * Analyze the request and try to respond
     * @return void
     */
    public function run()
    {
        // Request
        try
        {
            $request = new Restful_Server_Request();

            if ($this->_router->route($request))
            {
                // Success!
                $status = 200;

                $routeParams = $this->_router->getRouteParams();
                $content_type       = $routeParams['contentType'];
                $path               = $routeParams['path'];
                $resource           = $routeParams['resource'];
                $method             = $routeParams['method'];
                $params             = $routeParams['params'];

                $data = $this->_resources[$resource]->exec($method, $params);

                $max_age = $this->_resources[$resource]->maxAge();

                // Try to guess a $last_modified value and format
                if (isset($data['lastModified']))
                    $last_modified = $data['lastModified'];
                else if (isset($data['lastmod']))
                    $last_modified = $data['lastmod'];
                else if (isset($data['modified']))
                    $last_modified = $data['modified'];
                else if (isset($data['last_modified']))
                    $last_modified = $data['last_modified'];
                else
                    $last_modified = 0;
                if ($last_modified)
                {
                    if (!@date(DATE_RFC850, $last_modified)) // Timestamp
                    {
                        if (@date(DATE_RFC850, @strtotime($last_modified))) // String format
                            $last_modified = strtotime($last_modified);
                        else
                            $last_modified = 0;
                    }
                }
            }
            else
            {
                // Error investigation
                $routeParams = $this->_router->getRouteParams();

                $status = 400; // Bad request
                $content_type = $routeParams['contentType']
                                ? $routeParams['contentType']
                                : 'text/plain';
                $data = array('You MUST specify a valid resource and method, with appropriate params.
Try to navigate http://' . $_SERVER['SERVER_NAME'] . '/' . $this->_config['baseUrl'] . ' with your preferred browser or any device that prefers \'text/html\' to learn more.');
                $max_age = 3600;
                $last_modified = time();

                // If a browser visit the home page or a resource home page...
                if ('text/html' == $content_type)
                {
                    if (!$routeParams['path'])
                    {
                        $status = 200;
                        $data = $this->_router->discover();
                    }
                    else if ($routeParams['resource'])
                    {
                        $status = 200;
                        $data = $this->_router->discover($routeParams['resource']);
                    }
                }
            }
        }
        catch (Exception $e)
        {
            if(!$status = $e->getCode())
                $status = 500;
            $content_type = 'text/plain';
            $data = array($e->getMessage());
            $max_age = 0;
            $last_modified = time();
        }

        // Etag
        $etag = md5(serialize(array('data' => $data, 'contentType' => $content_type)));

        // Simply validate!
        if (!$this->_config['debug'] && (200 == $status) && (strtotime($request->ifModifiedSince) >= $last_modified) || ($request->ifMatch == $etag))
        {
            $status = 304;
            $data = null;
        }

        // Rich HTML response
        if ('text/html' == $content_type)
        {
            $rich = array();
            $rich['request'] = array(
                                    'HTTP Method'       => $request->method,
                                    'URI path'          => $request->uri,
                                    'Query string'      => $request->query,
                                    'POST data'         => $request->data,
                                    'Accept'            => implode(', ', $request->accept),
                                    'If-Modified-Since' => $request->ifModifiedSince,
                                    'If-Match'          => $request->ifMatch,
                                    );
            if ($this->_config['debug'])
                $rich['resource'] = $this->_router->getRouteParams();
            //$rich['data'] = $data;
            $rich['response'] = array(
                                    'Status code'   => $status,
                                    'Content-Type'  => $content_type,
                                    'Data'          => $data,
                                    'max-age'       => $max_age,
                                    'Last-Modified' => $last_modified,
                                    'Etag'          => $etag,
                                    );
            $data = $rich;
        }

        $response = new Restful_Server_Response($status, $content_type);
        $response->render($data, $max_age, $last_modified, $etag);
    }
}

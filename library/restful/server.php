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
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 * @version     1.0
 */
namespace Restful;

/**
 * Complete Restful Server
 *
 * @package     Restful
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Server
{
    /**
     * Service default configuration
     * @var array
     */
    protected $_config = array(
                            'baseUrl'   => '',
                            'html'      => 'default.html',
                            'debug'     => false,
                            );

    /**
     * The resources array
     * @var array
     */
    protected $_resources;

    /**
     * The request
     * @var Server\Request
     */
    protected $_request;
    
    /**
     * Routing
     * @var Server\Router
     */
    protected $_router;

    /**
     * Calculate an etag, based on content type and data array
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
    protected function _digException(\Exception $e)
    {
        if ($prev = $e->getPrevious())
        {
            return  "\n" . 'Previous Exception' . "\n" .
                    "\n" . 'Message: ' . $prev->getMessage() . "\n" .
                    "\n" . 'Location: ' . $prev->getFile() . ':' . $prev->getLine() . "\n" .
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
        {
            Server\Response::raw(500, 'Something went wrong in the framework.' . "\n" . $errstr, 'text/plain', 0, null, null, array());
            exit;
        }
    }

    /**
     * Exception handler
     *
     * @param Exception $exception
     * @return void
     */
    public function exceptionHandler(\Exception $exception)
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

            Server\Response::raw($code, $body, 'text/plain', 0, null, null, array());
        }
        else
            Server\Response::raw($code, $exception->getMessage(), 'text/plain', 0, null, null, array());

        exit;
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

        // Default autoloader
        spl_autoload_extensions('.php');
        spl_autoload_register();

        // Configuration file
        $config = parse_ini_file($config, true);

        // Server behaviour
        $this->_config = array_merge($this->_config, array_filter($config['server']));
        
        // Base url normalization
        /*
         * Base Url policy:
         * URLs should always start with a trailing slash
         * and never end with a trailing slash.
         * Valid examples:
         * '/a/b/c'
         * '/'
         * To allow this, the last character of base Url cannot be a slash
         */
        $this->_config['baseUrl'] = trim($this->_config['baseUrl'], '/ ');
        if ($this->_config['baseUrl'])
            $this->_config['baseUrl'] = '/' . $this->_config['baseUrl'];

        // User defined resources
        $this->_resources = array();
        if ($config['resources'])
        {
            $config['resources'] = new Config($config['resources']);
            foreach($config['resources'] as $resourceName => $resourceConfig)
            {
                $resourceName = strtolower($resourceName);
                if (('discover' == $resourceName) || ('ui' == $resourceName))
                    throw new \Exception('The resource name (' . $resourceName . ') is reserved for internal purposes.', 500);
                $this->_resources[$resourceName] = new Server\Resource\User($resourceName, $resourceConfig);
            }
        }

        // Add the Discover resource
        $this->_resources['discover'] = new Server\Resource('Restful\Server\Discover', array('resources' => $this->_resources, 'baseUrl' => $this->_config['baseUrl']));

        // Add the JavaScript resource
        $this->_resources['ui'] = new Server\Resource('Restful\Server\Ui', array('baseUrl' => $this->_config['baseUrl']));

        // Route
        $this->_router = new Server\Router($this->_resources, $this->_config['baseUrl']);
    }

    /**
     * Analyze the request and try to respond
     * @return void
     */
    public function run()
    {
        $request = new Server\Request();

        if ($this->_router->route($request)) // Success!
        {
            // Data
            $data = $this->_resources[$this->_router->getRouteParams('resource')]->exec($this->_router->getRouteParams('method'), $this->_router->getRouteParams('params'));
            
            // Status
            // http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
            switch ($request->method)
            {
                case 'GET':
                    $status = 200;
                    break;
                case 'POST':
                    $data ? $status = 200
                          : $status = 204;
                    break;
                case 'PUT':
                    $data ? $status = 200
                          : $status = 204;
                    break;
                case 'DELETE':
                    $data ? $status = 200
                          : $status = 204;
                    break;
                default:
                    $status = 500;
            }

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
                        $last_modified = time();
                }
            }
            else
                $last_modified = time();
        }
        else // Failed!
        {
            $message = '';
            if (!$this->_router->getRouteParams('resource'))
            {
                $status = 404;
                $message .= 'Resource not found.' . PHP_EOL;
            }
            else if (!$this->_router->getRouteParams('method'))
            {
                $status = 404;
                $message .= 'Method not found for the resource \'' . $this->_router->getRouteParams('resource') . '\'.' . PHP_EOL;
            }
            else
            {
                $status = 400;
                if (!is_array($this->_router->getRouteParams('params')))
                    $message .= 'Invalid parameters.' . PHP_EOL;
                if (!$this->_router->getRouteParams('contentType'))
                    $message .= 'The requested Content-Type(s) is (are) not available.' . PHP_EOL;
            }
            $message .= PHP_EOL . 'You MUST specify a valid resource and method, with appropriate params.' . PHP_EOL
                     . 'Try to navigate http://' . $_SERVER['SERVER_NAME'] . $this->_config['baseUrl'] . '/ with your preferred browser to learn more.' . PHP_EOL;

            // Dump debug informations
            if ($this->_config['debug'])
            {
                $message .= 'Internal informations dump' . PHP_EOL;
                $message .= '==========================' . PHP_EOL;
                $message .= 'Request URI: ' . $request->uri . PHP_EOL;
                $message .= 'Method: ' . $request->method . PHP_EOL;
                $message .= 'Query string: ' . $request->query . PHP_EOL;
                $message .= 'Data: ' . $request->data . PHP_EOL;
                $message .= PHP_EOL;
                $message .= print_r($_SERVER, true) . PHP_EOL;
            }

            $data = array($message);
            $last_modified = time();
        }

        if ($this->_router->getRouteParams('resource'))
            $max_age = $this->_resources[$this->_router->getRouteParams('resource')]->maxAge();
        else
            $max_age = 3600; // An arbitrary value

        $response = array(
                        'request'   => $request,
                        'route'     => $this->_router->getRouteParams(),
                        'resource'  => $this->_router->getRouteParams('resource'),
                        'resources' => $this->_resources, // ???
                        'status'    => $status,
                        'data'      => $data,
                        'cache'     => array(
                                            'lastModified'  => $last_modified,
                                            'maxAge'        => $max_age,
                                            ),
                        'html'      => $this->_config['html'],
                        'debug'     => $this->_config['debug'],
                        );
        Server\Response::response($response);
    }
}

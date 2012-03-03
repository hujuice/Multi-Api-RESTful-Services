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
                            'html-template' => '/templates/response.html',
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
            Restful_Server_Response::response(500, $errstr); // Will exit
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
            Restful_Server_Response::response($code, 'Internal Exception' . "\n" .
                                                     "\n" . 'Message: ' . $exception->getMessage() . "\n" .
                                                     "\n" . 'Location: ' . $exception->getFile() . ':' . $exception->getLine() . "\n" .
                                                     "\n" . 'Stack trace:' . "\n" . $exception->getTraceAsString()); // Will exit
        else
            Restful_Server_Response::response($code, 'Internal Error'); // Will exit
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
            $this->_resources[$resourceName] = new Restful_Server_Resource($resourceConfig);

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
        $this->_request = new Restful_Server_Request();

        if ($this->_router->route($this->_request))
        {
            // Normal operations

            $resource   = $this->_router->getResource();
            $method     = $this->_router->getMethod();
            $params     = $this->_router->getParams();
            $model = $this->_resources[$this->_router->getResource()]->get();

            $data = call_user_func_array(array($model, $method), $params);
            $max_age = $this->_resources[$this->_router->getResource()]->maxAge();
            if (empty($data['lastModified'])) // TODO Try other keys like 'last_modified', 'modified', 'lastmod', etc.
                $last_modified = 0;
            else
                $last_modified = $data['lastModified']; // TODO Check the format!!
        }
        else
        {
            // Service discovery

            $data = $this->_router->discover();
            $max_age = 0;
            $last_modified = 0;
        }

        $response = new Restful_Server_Response(array('text/plain'));
        $response->render($data, $max_age, $last_modified);
    }
}

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
 * Complete Restful Client
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Client
{
    protected $_baseUrl;

    protected $_httpMethod;

    protected $_accept;

    // TODO Try to implement curl_multi_init
    // TODO HTTP Proxies
    // TODO External configuration for http client
    protected $_curlOpts = array(
                                CURLOPT_FOLLOWLOCATION  => true,
                                CURLOPT_HEADER          => true,
                                CURLOPT_RETURNTRANSFER  => true,
                                CURLOPT_TIMEOUT         => 5,
                                CURLOPT_USERAGENT       => 'Multi-API Restful Services - Client',
                                CURLOPT_ENCODING        => '',
                                );

    public static $accepts = array(
                                'json'  => 'application/json',
                                'xml'   => 'application/xml',
                                );

    /**
     * General autoloader for the whole application
     *
     * @param string $class
     * @return void
     */
    public function autoloader($class)
    {
        require_once(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, (string) $class) . '.php');
    }

    public function __construct($baseUrl, $http_method = 'GET', $accept = 'application/json')
    {
        // Register the autoloader
        spl_autoload_register(array($this, 'autoloader'));

        if (filter_var($baseUrl, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) // The flag is because the server architecture
            $this->_baseUrl = $baseUrl;
        else
            throw new Exception('Invalid service URL.');

        if (in_array($http_method, Restful_Http::$methods))
            $this->_httpMethod = $http_method;
        else
            throw new Exception('Invalid HTTP method.');

        if (in_array($accept, self::$accepts))
            $this->_accept = $accept;
        else
            throw new Exception('Invalid accepted Content-Type.');
    }

    public function __call($name, $args)
    {
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) // ...if directly invoked (see http://it2.php.net/manual/en/functions.user-defined.php)
            $url = $this->_baseUrl . '/' . strtolower($name) . '/'; // See http://www.php.net/manual/en/book.curl.php#95733
        else
            throw new Exception('Invalid method name.');

        $ch = curl_init();
        curl_setopt_array($ch, $this->_curlOpts);

        $headers = array(
                        'Accept: ' . $this->_accept,
                        'Connection: keep-alive',
                        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->_httpMethod);

        switch($this->_httpMethod)
        {
            case 'GET':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($args));
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_URL, $url);
                $data = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-length: ' . strlen($data);
                break;

            case 'PUT':
                // $args SHOULD contain an entity identifier field, in the form of the GET requests
                if (isset($args['identifier']))
                    $query = '?' . http_build_query($args['identifier']);
                else
                    $query = '';
                curl_setopt($ch, CURLOPT_URL, $url . $query);
                $data = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-length: ' . strlen($data);
                break;

            default:
                throw new Exception('Unknown HTTP method.');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($error = curl_error($ch))
            throw new Exception($error, curl_errno($ch)); // http://curl.haxx.se/libcurl/c/libcurl-errors.html
        else
        {
            // $info = curl_getinfo($ch);
            $response = explode("\n", $response);
            $body = '';
            $headers = array();
            $isBody = false;
            foreach ($response as $row)
            {
                $row = trim($row);
                if ($isBody)
                    $body .= $row . PHP_EOL;
                else
                {
                    if ($row)
                    {
                        if (preg_match('/^([^:]+):\s*(.*)$/', $row, $matches))
                            $headers[$matches[1]] = $matches[2];
                    }
                    else
                        $isBody = true;
                }
            }

            if (isset($headers['Content-Type']))
            {
                switch($headers['Content-Type'])
                {
                    case 'application/json':
                        return json_decode($body, true);
                    case 'application/xml':
                        return wddx_deserialize($body);
                    default:
                        throw new Exception('Unknown Content-Type.');
                }
            }
            else
                throw new Exception('Missing Content-Type.');
        }
        curl_close($ch); // TODO Don't close until destructor!
    }
}
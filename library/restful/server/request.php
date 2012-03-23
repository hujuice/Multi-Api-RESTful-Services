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
 * Restful Server Request
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Request
{
    /**
     * Supported HTTP methods
     * @var array
     */
    public static $httpMethods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * Method
     * @var string
     */
    public $method;

    /**
     * Uri
     * @var string
     */
    public $uri;

    /**
     * Accept
     * @var array
     */
    public $accept;

    /**
     * Query string
     * @var array
     */
    public $query;

    /**
     * Body data
     * @var array
     */
    public $data = array();

    /**
     * If Modified Since
     * @var integer
     */
    public $ifModifiedSince = 0;

    /**
     * Etag
     * @var string
     */
    public $ifMatch = null;

    /**
     * Parse a generic 'accept' header
     *
     * @param string $header
     * @return array
     */
    protected function _parseAccept($header)
    {
        $accept = array();
        if ($header = explode(',', $header))
        {
            foreach ($header as $elem)
            {
                $elem = explode(';', $elem);
                if (isset($elem[1]))
                {
                    if (preg_match('/^q=([01])(\.\d+)?$/', $elem[1], $matches))
                        $elem[1] = (float) $matches[1] . '.' . ($matches[2] ? $matches[2] : '0');
                    else
                        $elem = null;
                }
                else
                    $elem[1] = (float) 1.0;

                $elem && ($accept[$elem[0]] = $elem[1]);
            }
        }

        arsort($accept);
        return array_keys($accept);
    }

    /**
     * Analyze the HTTP requeset and prepare the object
     *
     * @return void
     * @throw Exception
     */
    public function __construct()
    {
        // Method
        if (in_array($_SERVER['REQUEST_METHOD'], self::$httpMethods))
            $this->method = $_SERVER['REQUEST_METHOD'];
        else
            throw new \Exception('The HTTP method \'' . $_SERVER['REQUEST_METHOD'] . '\' is not implemented.', 405);

        // Uri
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Accept
        $this->accept = $this->_parseAccept($_SERVER['HTTP_ACCEPT']);

        // Query string
        $this->query = $_GET;

        // Data
        $this->data = array();
        if (!empty($_SERVER['CONTENT_TYPE']))
        {
            switch($_SERVER['CONTENT_TYPE'])
            {
                case 'application/x-www-form-urlencoded':
                    $this->data = $_POST;
                    break;
                case 'multipart/form-data':
                    // Array of couples mime, content
                    $this->data = array();
                    foreach($_FILES as $name => $file)
                    {
                        if (is_array($file['error']))
                        {
                            $this->data[$name] = array();
                            foreach($file['error'] as $key => $error)
                            {
                                if (UPLOAD_ERR_OK == $error)
                                    $this->data[$name][$key] = array('mime' => $file['type'][$key], 'content' => file_get_contents($file['tmp_name'][$key]));
                                else
                                    throw new \Exception('Error ' . $error . ' while uploading the file \'' . $name . '\'. See http://www.php.net/manual/en/features.file-upload.errors.php for the error codes.', 500);
                            }
                        }
                        else
                        {
                            if (UPLOAD_ERR_OK == $file['error'])
                                $this->data[$name] = array('mime' => $file['type'], 'content' => file_get_contents($file['tmp_name']));
                            else
                                throw new \Exception('Error ' . $file['error'] . ' while uploading the file \'' . $name . '\'. See http://www.php.net/manual/en/features.file-upload.errors.php for the error codes.', 500);
                        }
                    }
                    break;
                case 'application/json':
                    $this->data = json_decode(file_get_contents('php://input'), true);
                    break;
                case 'application/xml':
                    $this->data = (array) simplexml_load_file('php://input');
                    break;
                default:
                    throw new \Exception('Unsupported request Content-Type.', 415);
            }
        }

        // If-Modified-Since
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            $this->ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        // Etag
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH']))
            $this->ifMatch = $_SERVER['HTTP_IF_NONE_MATCH'];
    }
}
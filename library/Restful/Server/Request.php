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
 * Restful Server Request
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Server_Request
{
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
    public $accept = array('*/*' => '0.0');

    /**
     * Accept language
     * @var array
     */
    public $acceptLanguage = array('en_US' => '0.0');

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
    public $etag = null;

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

    public function __construct()
    {
        // Method
        $this->method = $_SERVER['REQUEST_METHOD'];

        // Uri
        $this->uri = $_SERVER['REQUEST_URI'];

        // Accept
        $this->accept = array_merge($this->accept, $this->_parseAccept($_SERVER['HTTP_ACCEPT']));

        // Accept-Language
        $this->acceptLanguage = array_merge($this->acceptLanguage, $this->_parseAccept($_SERVER['HTTP_ACCEPT_LANGUAGE']));

        // Query string
        $this->query = $_GET;

        // Data
        /*
        TODO Method other than GET are not implemented
        if (!empty($_SERVER['CONTENT_TYPE']))
        {
            switch($_SERVER['CONTENT_TYPE'])
            {
                case 'application/x-www-form-urlencoded':
                    $this->data = $_POST;
                    break;
                case 'multipart/form-data':
                    $this->data = $_FILES; // TODO Analyze the array and return more interesting data
                    break;
                case 'application/json':
                    $this->data = json_decode(file_get_contents('php://input'), true);
                    break;
                case 'application/xml':
                    $this->data = (array) simplexml_load_file('php://input');
                    break;
                default
                    throw new Exception('Unsupported request Content-Type.', 415);
            }
        }
        */

        // If-Modified-Since
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            $this->ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        // Etag
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH']))
            $this->etag = $_SERVER['HTTP_IF_NONE_MATCH'];
    }
}
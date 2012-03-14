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
 * Generic HTTP
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Http
{
    /**
     * Supported HTTP methods
     * @var array
     */
    public static $methods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * Status codes
     * @var array
     */
    public static $statuses = array(
                                '100' => 'HTTP/1.1 100 Continue',
                                '101' => 'HTTP/1.1 101 Switching Protocols',
                                '200' => 'HTTP/1.1 200 OK',
                                '201' => 'HTTP/1.1 201 Created',
                                '202' => 'HTTP/1.1 202 Accepted',
                                '203' => 'HTTP/1.1 203 Non-Authoritative Information',
                                '204' => 'HTTP/1.1 204 No Content',
                                '205' => 'HTTP/1.1 205 Reset Content',
                                '206' => 'HTTP/1.1 206 Partial Content',
                                '300' => 'HTTP/1.1 300 Multiple Choices',
                                '301' => 'HTTP/1.1 301 Moved Permanently',
                                '302' => 'HTTP/1.1 302 Found',
                                '303' => 'HTTP/1.1 303 See Other',
                                '304' => 'HTTP/1.1 304 Not Modified',
                                '305' => 'HTTP/1.1 305 Use Proxy',
                                '306' => 'HTTP/1.1 306 (Unused)',
                                '307' => 'HTTP/1.1 307 Temporary Redirect',
                                '400' => 'HTTP/1.1 400 Bad Request',
                                '401' => 'HTTP/1.1 401 Unauthorized',
                                '402' => 'HTTP/1.1 402 Payment Required',
                                '403' => 'HTTP/1.1 403 Forbidden',
                                '404' => 'HTTP/1.1 404 Not Found',
                                '405' => 'HTTP/1.1 405 Method Not Allowed',
                                '406' => 'HTTP/1.1 406 Not Acceptable',
                                '407' => 'HTTP/1.1 407 Proxy Authentication Required',
                                '408' => 'HTTP/1.1 408 Request Timeout',
                                '409' => 'HTTP/1.1 409 Conflict',
                                '410' => 'HTTP/1.1 410 Gone',
                                '411' => 'HTTP/1.1 411 Length Required',
                                '412' => 'HTTP/1.1 412 Precondition Failed',
                                '413' => 'HTTP/1.1 413 Request Entity Too Large',
                                '414' => 'HTTP/1.1 414 Request-URI Too Long',
                                '415' => 'HTTP/1.1 415 Unsupported Media Type',
                                '416' => 'HTTP/1.1 416 Requested Range Not Satisfiable',
                                '417' => 'HTTP/1.1 417 Expectation Failed',
                                '500' => 'HTTP/1.1 500 Internal Server Error',
                                '501' => 'HTTP/1.1 501 Not Implemented',
                                '502' => 'HTTP/1.1 502 Bad Gateway',
                                '503' => 'HTTP/1.1 503 Service Unavailable',
                                '504' => 'HTTP/1.1 504 Gateway Timeout',
                                '505' => 'HTTP/1.1 505 HTTP Version Not Supported',
                                );

    /**
     * Supported output content-types
     * @var array
     */
    public static $contentTypes = array(
                                    'json'  => 'application/json',
                                    'xml'   => 'application/xml',
                                    'html'  => 'text/html',
                                    'txt'   => 'text/plain',
                                    );
}
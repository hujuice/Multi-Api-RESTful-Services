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
 * Restful Server Response
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Response
{
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
    public static $contentTypes = array( // The order matter, see router.php
                                    'json'  => 'application/json',
                                    'xml'   => 'application/xml',
                                    'js'    => 'text/javascript',
                                    'html'  => 'text/html',
                                    'txt'   => 'text/plain',
                                    );

    /**
     * HTTP Status code
     * @var integer
     */
    protected $_status;

    /**
     * Content-Type
     * @var string
     */
    protected $_contentType;

    /**
     * Template path
     * @var string
     */
    protected $_template;

    /**
     * Generate immediately a http output
     *
     * @param integer $status
     * @param string $body
     * @param string $content_type
     * @param integer $max_age
     * @param string $last_modified
     * @param string $etag
     * @param array $extra_headers
     * @return void
     * @throw Exception
     */
    public static function raw($status, $body = null, $content_type = 'text/plain', $max_age = 0, $last_modified = null, $etag = null, $extra_headers = array())
    {
        // Headers
        $headers = array();

        // Status
        if (!isset(self::$statuses[$status]))
            throw new \Exception('Invalid status code (' . $status . ').');
        else
            $headers[] = self::$statuses[$status];

        // Body
        if (in_array($status, array(204, 205, 304)))
            $body = null;

        // Content-Type
        if (in_array($content_type, self::$contentTypes))
            $headers[] = 'Content-Type: ' . $content_type;
        else
            throw new \Exception('Invalid Content-Type (' . $content_type . ').');

        // Cache headers
        if ($max_age && ($max_age > 0))
        {
            if (!$last_modified)
                $last_modified = time();

            $headers[] = 'Last-Modified: ' . date(DATE_RFC850, $last_modified);
            $headers[] = 'Cache-Control: max-age=' . (integer) $max_age . ', must-revalidate';

            if ($etag)
                $headers[] = 'Etag: ' . $etag;
        }
        else
            $headers[] = 'Cache-Control: no-cache';

        // Custom headers
        if (is_array($extra_headers))
        {
            foreach($extra_headers as $header)
                $headers[] = $header;
        }

        // Go!
        foreach ($headers as $header)
            header($header);
        if ($body)
        {
            ob_start();
            if (self::$contentTypes['html'] == $content_type) // Dump debug info
            {
                $request_headers = apache_request_headers();
                $html = '';
                foreach ($request_headers as $label => $content)
                    $html .= $label . ': ' . $content . '<br />';
                $body = preg_replace('/<!-- \{request_headers\} -->/', $html, $body);

                flush();
                $response_headers = apache_response_headers();
                $html = '';
                foreach ($response_headers as $label => $content)
                    $html .= $label . ': ' . $content . '<br />';
                $body = preg_replace('/<!-- \{response_headers\} -->/', $html, $body);
            }

            echo $body;
        }
    }

    /**
     * Get a complete set of info and give an HTTP response
     *
     * @param array $info
     * @return void
     */
    public static function response($info)
    {
        $status = $info['status'];
        if (isset($info['route']['contentType']))
            $content_type = $info['route']['contentType'];
        else
            $content_type = self::$contentTypes['txt'];

        $etag = md5(serialize(array('data' => $info['data'], 'contentType' => $content_type)));

        switch($content_type)
        {
            case self::$contentTypes['json']:
                $body = json_encode($info['data']);
                break;
            case self::$contentTypes['js']:
                if (empty($info['route']['jsonp'])) // Plain JavaScript for ui resource
                    $body = $info['data'];
                else
                    $body = $info['route']['jsonp'] . '(' . json_encode($info['data']) . ');';
                break;
            case self::$contentTypes['xml']:
                $body = wddx_serialize_value($info['data']);
                break;
            case self::$contentTypes['html']:
                $html = Html\Html::create($info);
                $body = $html->get();
                break;
            case self::$contentTypes['txt']:
                $body = print_r($info['data'], true);
                break;
        }

        // Simply validate if cached
        if (!$info['debug'] &&
            ((200 == $status) || (4 == $status[0])) &&
            ((strtotime($info['request']->ifModifiedSince) >= $info['cache']['lastModified']) || ($info['request']->ifMatch == $etag)))
        {
            $status = 304;
            $body = null;
        }

        self::raw($status, $body, $content_type, $info['cache']['maxAge'], $info['cache']['lastModified'], $etag);
    }
}

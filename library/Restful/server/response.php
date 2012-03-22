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
 * @version
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
    public static $contentTypes = array(
                                    'json'  => 'application/json',
                                    'xml'   => 'application/xml',
                                    'html'  => 'text/html',
                                    'txt'   => 'text/plain',
                                    );

    /**
     * HTML response template
     * @var string
     */
    protected static $htmlTemplate = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Restful Services Discovery</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="Multi-API Restful Services - Server" />
</head>
<body style="margin: 0">
<h1 style="margin: 0; padding: 1em; background-color: #437"><a href="/" style="color: #fff; text-decoration: none">Restful Services Discovery</a></h1>
<!-- {dinamic} -->
</body>
</html>';

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
     * Format data in a simple HTML layout
     *
     * @param mixed $data
     * @return string
     */
    public static function data2html($data)
    {
        $html = '<div class="Restful_Data">'; // Avoid to write in body directly
        switch(gettype($data))
        {
            case 'unknown type':
            case 'resource':
                throw new \Exception('Unsupported data type');
            case 'NULL':
                $html .= 'NULL';
                break;
            case 'boolean':
                $html .= $data ? 'true' : 'false';
                break;
            case 'integer':
            case 'double':
            case 'string':
                $html .= $data;
                break;
            case 'object':
                $data = (array) $data;
                break;
            case 'array':
                $html .= '<dl style="border: 1px dotted #999; margin: 0.5em">';
                foreach ($data as $key => $value)
                {
                    $html.= '<dt style="float: left; font-weight: bold">' . htmlspecialchars($key) . '</dt>';
                    if ($value)
                    {
                        if (is_scalar($value))
                            $html .= '<dd style="padding-left: 4em">' . nl2br(htmlspecialchars($value)) . '</dd>';
                        else
                            $html .= '<dd style="clear: left">' . self::data2html((array) $value) . '</dd>';
                    }
                    else
                        $html .= '<dd>&nbsp;</dd>';
                }
                $html .= '</dl>';
                break;
            default:
                throw new \Exception('Unknown data type');
        }
        $html .= '</div>';
        return $html;
    }

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
     */
    public static function raw($status, $body = null, $content_type = 'text/plain', $max_age = 0, $last_modified = null, $etag = null, $extra_headers = array())
    {
        // Headers
        $headers = array();

        // Status
        if (!isset(self::$statuses[$status]))
            throw new \Exception('Internal status code inconcistency.');
        else
            $headers[] = self::$statuses[$status];

        // Body
        if (in_array($status, array(204, 205, 304)))
            $body = null;

        // Content-Type
        if (in_array($content_type, self::$contentTypes))
            $headers[] = 'Content-Type: ' . $content_type;
        else
            throw new \Exception('Internal Content-Type inconcistency.');

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
            if (!empty($_GET['debug']) && (self::$contentTypes['html'] == $content_type)) // Dump debug info
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
            $content_type = self::$statuses['txt'];

        $etag = md5(serialize(array('data' => $info['data'], 'contentType' => $content_type)));

        switch($content_type)
        {
            case 'application/json':
                $body = json_encode($info['data']);
                break;
            case 'application/xml':
                $body = wddx_serialize_value($info['data']);
                break;
            case 'text/html':
                $body = preg_replace('/<!-- \{dinamic\} -->/', self::data2html($info['data']), str_replace("\n", '', self::$htmlTemplate));
                break;
            case 'text/plain':
                $body = print_r($info['data'], true);
                break;
        }

        // Simply validate!
        if (!$info['debug'] &&
            ((200 == $status) || (4 == $status[0])) &&
            ((strtotime($info['request']->ifModifiedSince) >= $info['cache']['lastModified']) || ($info['request']->ifMatch == $etag)))
        {
            $status = 304;
            $body = null;
        }

        if (!empty($_GET['debug']) && (self::$contentTypes['html'] == $content_type)) // Dump debug info
        {
            $html  = '<div style="background-color: #eee; padding: 0.5em">';

            $html .= '<h2>Request</h2>';
            $html .= '<p style="font-size: 1.5em; font-weight: bold"><tt>' . $info['request']->method . ' ' . $info['request']->uri . '?' . htmlspecialchars(http_build_query($info['request']->query)) . '</tt></p>';
            $html .= '<h2>Request headers</h2>';
            $html .= '<div><tt><!-- {request_headers} --></tt></div>';
            $html .= '<p>POST data:</p><pre>' . print_r($info['request']->data, true) . '</pre>';

            $html .= '<h2>Routing</h2>';
            $html .= '<pre>' . print_r($info['route'], true) . '</pre>';

            $html .= '<h2>Status code</h2>';
            $html .= '<p style="font-size: 1.5em; font-weight: bold"><tt>' . $info['status'] . '</tt></p>';

            $html .= '<h2>Response headers</h2>';
            $html .= '<div><tt><!-- {response_headers} --></tt></div>';

            $html .= '</div>';

            $body = preg_replace('/<\/body>/', $html . '</body>', $body);
        }

        self::raw($status, $body, $content_type, $info['cache']['maxAge'], $info['cache']['lastModified'], $etag);
    }
}

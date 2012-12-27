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
namespace Restful\Server\Html;

/**
 * HTML response
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Response implements htmlInterface
{
    /**
     * Informations to display
     * @var array
     */
    protected $_info;

    /**
     * HTML template
     * @var string
     */
    protected $_html;

    /**
     * Format data in a simple HTML layout
     *
     * @param mixed $data
     * @return string
     */
    protected function _data2html($data)
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
            case 'array':
                $data = (array) $data;
                $html .= '<dl style="border: 1px dotted #999; margin: 0.5em">';
                foreach ($data as $key => $value)
                {
                    $html.= '<dt style="float: left; font-weight: bold">' . htmlspecialchars($key) . '</dt>';
                    if ($value)
                    {
                        if (is_scalar($value))
                            $html .= '<dd style="padding-left: 4em">' . nl2br(htmlspecialchars($value)) . '</dd>';
                        else
                            $html .= '<dd style="clear: left">' . $this->_data2html((array) $value) . '</dd>';
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
     * Get informations and template
     * @param array $info
     * @param string $html
     * @return void
     */
    public function __construct($info, $html)
    {
        $this->_info = (array) $info;
        $this->_html = (string) $html;
    }

    /**
     * Return HTML body
     * @return string
     */
    public function get()
    {
        $body = $this->_data2html($this->_info['data']);

        if (!empty($this->_info['debug'])) // Dump debug info
        {
            $body .= '<div style="background-color: #eee; padding: 0.5em">';

            $body .= '<h2>Request</h2>';
            $body .= '<p style="font-size: 1.5em; font-weight: bold"><tt>' . $this->_info['request']->method . ' ' . $this->_info['request']->uri . '?' . htmlspecialchars(http_build_query($this->_info['request']->query)) . '</tt></p>';
            $body .= '<h2>Request headers</h2>';
            $body .= '<div><tt><!-- {request_headers} --></tt></div>';
            $body .= '<p>POST data:</p><pre>' . print_r($this->_info['request']->data, true) . '</pre>';

            $body .= '<h2>Routing</h2>';
            $body .= '<pre>' . print_r($this->_info['route'], true) . '</pre>';

            $body .= '<h2>Status code</h2>';
            $body .= '<p style="font-size: 1.5em; font-weight: bold"><tt>' . $this->_info['status'] . '</tt></p>';

            $body .= '<h2>Response headers</h2>';
            $body .= '<div><tt><!-- {response_headers} --></tt></div>';

            $body .= '</div>';
        }

        return preg_replace('/<!-- \{dynamic\} -->/', $body, str_replace("\n", '', $this->_html));
    }
}
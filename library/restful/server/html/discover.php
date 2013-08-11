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
 * HTML discover
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Discover implements htmlInterface
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
     * Base url (including protocol, host, baseUrl)
     * @var string
     */
    protected $_base;

    /**
     * Format the params' list
     * @param array $params
     * @return string
     */
    protected function _params($params, $base = '', $http = 'GET')
    {
        $body = '<h5>Parameters</h5>';
        if ($params)
        {
            foreach ($params as $name => $param)
            {
                $body .= '<div>';
                $body .= '<div><strong>?' . htmlspecialchars($name) . '=</strong><em>' . $param['type'] . '</em><br /><span class="small">' . htmlspecialchars($param['desc']) . '</span></div>';
                $body .= '<div style="display: none">';
                $body .= '<h6>Type</h6><p class="small">' . $param['type'] . '</p>';
                $body .= '<h6>Defaults to</h6><pre class="small">' . var_export($param['defaults_to'], true) . '</pre>';
                $body .= '<h6>Optional</h6><p class="small">' . ($param['is_optional'] ? 'Yes' : 'No') . '</p>';
                $body .= '</div>';
                $body .= '</div>';
            }
        }
        else
            $body .= '<p class="small">No params</p>';
            
        $body .= '<h5>Try yourself</h5>';
        $body .= '<div class="sandbox"><form method="get">';
        $body .= '<p>Method: <span class="http">' . $http . '</span></p>';
        if ('POST' != $http)
        {
            $body .= '<div>';
            $body .= '<label class="qs">' . $base . '?</label>';
            $body .= '<input type="text" class="qs" />';
            $body .= '</div>';
        }
        else
            $body .= '<p class="qs">' . $base . '</p>';
        if (('POST' == $http) || ('PUT' == $http))
        {
            $body .= '<div>';
            $body .= '<label class="post" style="float: left">Post data: </label>';
            $body .= '<textarea class="post" style="float: left"></textarea>';
            $body .= '<p class="small">Hint: if you have complex data, try a json structure.<br />E.g. { param1: "value1"; param2: "value2" }</p>';
            $body .= '</div>';
        }
        $body .= '<div style="clear: both">';
        $body .= '<label class="accept" style="clear: both">Accept: </label>';
        $body .= '<select class="accept">';
        foreach (\Restful\Server\Response::$contentTypes as $label => $content_type)
            $body .= '<option value="' . htmlspecialchars($label) . '">' . htmlspecialchars($content_type) . '</option>';
        $body .= '</select>';
        $body .= '</div>';
        $body .= '<input type="submit" value="Go" />';
        $body .= '</form><pre class="status"></pre><pre class="message"></pre><pre class="dialog"></pre></div>';

        return $body;
    }

    /**
     * Format the methods' list
     * @param array $methods
     * @return string
     */
    protected function _methods($methods, $base = '', $http = 'GET')
    {
        $body = '<h4>Available methods</h4>';
        if ($methods)
        {
            foreach ($methods as $name => $method)
            {
                $body .= '<div>';
                $body .= '<div><strong>' . htmlspecialchars($name) . '</strong><br /><span class="small">' . htmlspecialchars($method['desc']) . '</span></div>';
                $body .= '<div style="display: none">';
                $body .= '<h5>Description</h5><p class="small">' . $method['purpose'] . '</p>';
                $body .= '<h5>Base url</h5><p class="small">' . $base . htmlspecialchars($name) . '</p>';
                $body .= $this->_params($method['params'], $base . htmlspecialchars($name), $http);
                $body .= '<h5>Discover</h5><p class="small"><a href="' . htmlspecialchars($method['discover']) . '">' . htmlspecialchars($method['discover']) . ' </a></p>';
                $body .= '</div>';
                $body .= '</div>';
            }
        }
        else
            $body .= '<p class="small">No methods</p>';

        return $body;
    }

    /**
     * Format the resources' list
     * @param array $resources
     * @return string
     */
    protected function _resources($resources, $base = '')
    {
        $body = '<h3>Available resources</h3>';
        if ($resources)
        {
            foreach ($resources as $name => $resource)
            {
                $body .= '<div>';
                $body .= '<div><strong>' . htmlspecialchars($name) . '</strong><br /><span class="small">' . htmlspecialchars($resource['desc']) . '</span></div>';
                $body .= '<div style="display: none">';
                $body .= '<h4>Description</h4><p class="small">' . $resource['purpose'] . '</p>';
                $body .= '<h4>Base url</h4><p class="small">' . $base . htmlspecialchars($name) . '</p>';
                $body .= '<h4>HTTP method</h4><p class="small">' . $resource['HTTP'] . '</p>';
                $body .= '<h4>Discover</h4><p class="small"><a href="' . htmlspecialchars($resource['discover']) . '">' . htmlspecialchars($resource['discover']) . ' </a></p>';
                $body .= $this->_methods($resource['methods'], $base . $name . '/', $resource['HTTP']);
                $body .= '</div>';
                $body .= '</div>';
            }
        }
        else
            $body .= '<p class="small">No resources</p>';

        return $body;
    }

    /**
     * Get informations and template
     * @param array $info
     * @param string $html
     * @return void
     */
    public function __construct($info, $html)
    {
        // JavaScript
        if (strpos($html, '</head>') === false)
            throw new Exception('Invalid HTML template. Please validate it with http://validator.w3.org/');
        $html = str_replace('</head>', '<script type="text/javascript" src="/ui/get"></script>' . PHP_EOL . '</head>', $html);

        $this->_info = (array) $info;
        $this->_html = (string) $html;
        $this->_base = 'http://' . $this->_info['data']['host'] . $this->_info['data']['baseUrl'];
    }

    /**
     * Return HTML body
     * @return string
     */
    public function get()
    {
        $url = $this->_base;
        $body = '<div class="info">';
        if (isset($this->_info['data']['resource']))
        {
            $resource = $this->_info['data']['resource'];
            $url .= $resource . '/';
            $body .= "<h3>Resource: '$resource'</u></h3>";
            if (isset($this->_info['data']['method']))
            {
                $method = $this->_info['data']['method'];
                $url .= $method;
                $body .= "<h3>Method: '$method'</h3>";
            }
            else
                $body .= '<h3>All methods</h3>';
        }
        else
        {
            $body = Html::intro() . $body; // Prepend!
            $body .= '<h3>All resources</h3>';
        }
        $body .= '<h3>Url: <a href="' . $url . '">' . $url . '</a></h3>';
        $body .= '</div>';
        $body .= '<div class="discovery">';
        if (isset($this->_info['data']['resources']))
            $body .= $this->_resources($this->_info['data']['resources'], $this->_base);
        else if (isset($this->_info['data']['methods']))
            $body .= $this->_methods($this->_info['data']['methods'], $this->_base . $this->_info['data']['resource'] . '/');
        else if (isset($this->_info['data']['params']))
            $body .= $this->_params($this->_info['data']['params'], $this->_base . $this->_info['data']['resource'] . '/' . $this->_info['data']['method']);
        $body .= '</div>';

        return preg_replace('/<!-- \{dynamic\} -->/', $body, $this->_html);
    }
}
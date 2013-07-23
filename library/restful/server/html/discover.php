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
    protected function _params($params)
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
                /*
                foreach ($param as $item => $value)
                    $body .= '<dd>' . htmlspecialchars($item) . '</dd><dt>' . htmlspecialchars($value) . '</dt>';
                */
                $body .= '</div>';
                $body .= '</div>';
            }
        }
        else
            $body .= '<p class="small">No params</p>';

        return $body;
    }

    /**
     * Format the methods' list
     * @param array $methods
     * @return string
     */
    protected function _methods($methods, $base = '')
    {
        $body = '<h4>Available methods</h4>';
        if ($methods)
        {
            foreach ($methods as $name => $method)
            {
                $body .= '<div>';
                $body .= '<div><strong>' . htmlspecialchars($name) . '</strong><br /><span class="small">' . htmlspecialchars($method['desc']) . '</span></div>';
                $body .= '<div style="display: none">';
                $body .= '<p class="small tool"><a href="javascript:">sandbox</a></p>';
                $body .= '<h5>Description</h5><p class="small">' . $method['purpose'] . '</p>';
                $body .= '<h4>Base url</h4><p class="small">' . $base . htmlspecialchars($name) . '</p>';
                $body .= $this->_params($method['params']);
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
                $body .= $this->_methods($resource['methods'], $base . $name . '/');
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
/*
header('Content-Type: text/plain');
print_r($this->_info);
exit;
*/
        $url = $this->_base;
        $body  = '<h2>Discovery service for <a href="' . $url . '">' . $url . '</a></h2>';
        $body .= '<div class="intro">';
        $body .= '<p>This website offers descriptions, discovery service for both human and machines and sandboxes for the webservices supplied by this host.<br />
                  It responds for every <tt>Accept: text/html</tt> http header (e.g.: your browser) or other unsupported content types. Supported content types are:</p>';
        $body .= '<dl>';
        $body .= '<dt><strong>JSON</strong></dt><dd><tt>application/json</tt></dd>';
        $body .= '<dt><strong>JSONp</strong></dt><dd><tt>text/javascript</tt></dd>';
        $body .= '<dt><strong>XML</strong>, <a href="http://it1.php.net/manual/en/intro.wddx.php">WDDX</a></dt><dd><tt>application/xml</tt></dd>';
        $body .= '<dt><strong>TEXT</strong>, useful for debugging</dt><dd><tt>text/plain</tt></dd>';
        $body .= '<dt><strong>HTML</strong>, discovery, documentation and sandbox tools</dt><dd><tt>text/html</tt></dd>';
        $body .= '</dl>';
        $body .= '<p>There\'s a lot of useful borowser plugins to easly manage the <tt>Accept</tt> header during consuming development. Our choice is <em><a href="http://www.garethhunt.com/modifyheaders/">Modify Headers</a></em> for <a href="http://www.mozilla.org/firefox">Firefox</a>.</p>';
        $body .= '</div>';
        $body .= '<div class="info">';
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
            $body .= '<h3>All resources</h3>';
        $body .= '<h3>Url: <a href="' . $url . '">' . $url . '</a></h3>';
        $body .= '</div>';
        $body .= '<div class="mars">';
        if (isset($this->_info['data']['resources']))
            $body .= $this->_resources($this->_info['data']['resources'], $this->_base);
        else if (isset($this->_info['data']['methods']))
            $body .= $this->_methods($this->_info['data']['methods'], $this->_base . $this->_info['data']['resource'] . '/');
        else if (isset($this->_info['data']['params']))
            $body .= $this->_params($this->_info['data']['params']);
        $body .= '</div>';
        $body .= '<div style="display: none" class="sandbox">';
        $body .= '<h3>Sandbox</h3>';
        $body .= '<form method="get"><fieldset>';
        $body .= '<legend></legend>';
        $body .= '<label for="accept" class="small">Accept</label><br />';
        $body .= '<select name="accept" id="accept">';
        foreach (\Restful\Server\Response::$contentTypes as $label => $content_type)
            $body .= '<option value="' . htmlspecialchars($label) . '">' . htmlspecialchars($content_type) . '</option>';
        $body .= '</select><br />';
        $body .= '<label for="qs" class="small">Query string</label><br />';
        $body .= '?<input type="text" name="qs" id="qs" value="" /><br />';
        $body .= '<button name="go">Go</button>';
        $body .= '</fieldset></form>';
        $body .= '<pre style="display: none" class="request"></pre>';
        $body .= '<pre style="display: none" class="accept"></pre>';
        $body .= '<pre style="display: none" class="headers"></pre>';
        $body .= '<pre style="display: none" class="body"></pre>';
        $body .= '</div>';

        return preg_replace('/<!-- \{dynamic\} -->/', $body, $this->_html);
    }
}
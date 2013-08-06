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
 * HTML output factory method
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Html
{
    /**
     * Select HTML class upon output type
     * @param array $info
     * @return Restful\Server\htmlInterface
     */
    public static function create($info)
    {
        // Prepare the template
        $html = file_get_contents($info['html'], true);
        unset($info['html']);
        if (strpos($html, '<!-- {dynamic} -->') === false)
        {
            if (strpos($html, '</body>') === false)
                throw new Exception('Invalid HTML template. Please validate it with http://validator.w3.org/');
            $html = str_replace('</body>', '<!-- {dynamic} -->' . PHP_EOL . '</body>', $html);
        }
        if (empty($info['debug']))
            $html = (string) $html;
        else
            $html = str_replace("\n", '', (string) $html);

        // Make a decision
        $resource = $info['resource'];
        unset($info['resource']);
        
        if ('discover' == $resource)
            return new discover($info, $html);
        else
            return new response($info, $html);
    }
    
    /**
     * Give a text intro
     */
    public static function intro()
    {
        $body = '<div class="intro">
<p>This automatically generated website offers descriptions, discovery service - for both human and machines - and sandboxes for the webservices supplied by this system.<br />
    It responds in this human oriented way for every <span class="console">Accept: text/html</span> http header (e.g.: your browser) or other unsupported content types. It responds as requested for the supported content types.<br />
    Supported content types are:</p>
<dl>
<dt><strong>JSON</strong></dt><dd><span class="console">application/json</span></dd>
<dt><strong>JSONp</strong></dt><dd><span class="console">text/javascript</span></dd>
<dt><strong>XML</strong>, <a href="http://it1.php.net/manual/en/intro.wddx.php">WDDX</a></dt><dd><span class="console">application/xml</span></dd>
<dt><strong>TEXT</strong>, useful for debugging</dt><dd><span class="console">text/plain</span></dd>
<dt><strong>HTML</strong>, discovery, documentation and sandbox tools</dt><dd><span class="console">text/html</span></dd>
</dl>
<p>You can request a specific supported content type by:</p>
<ol>
<li>the <span class="console">Accept:</span> HTTP header;</li>
<li>adding a <span class="console">content_type=value</span> query string parameter, where values should be <span class="console">json</span>, <span class="console">js</span>, <span class="console">xml</span>, <span class="console">txt</span> or <span class="console">html</span>;</li>
<li>adding an "extension" to the requested method; e.g., if you want that the <span class="console">members/getplanets</span> will output in XML, you can ask for <span class="console">members/getplanets<strong>.xml</strong>?yourquerystring</span>.</li>
</ol>
<p>The preferred way, in the HTTP paradigma, is the <span class="console">Accept:</span> way. There\'s a lot of useful borowser plugins to easly manage the <span class="console">Accept</span> header during consuming development. A good choice is <em><a href="http://www.garethhunt.com/modifyheaders/">Modify Headers</a></em> for <a href="http://www.mozilla.org/firefox">Firefox</a>.</p>
<p>Beside this, the <em>discovery</em> services help both human and machines to explore the services signature and behaviour.</p>
</div>';
        
        return $body;
    }

    /**
     * Avoid object instances
     */
    private function __construct()
    {
        /* Simply avoid direct creation and do nothing */
    }
}
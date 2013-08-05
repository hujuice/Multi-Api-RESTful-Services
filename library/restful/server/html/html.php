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
     * Avoid object instances
     */
    private function __construct()
    {
        /* Simply avoid direct creation and do nothing */
    }
}
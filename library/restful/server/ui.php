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
 * Provide Javascript in HTML outputs
 *
 * @package     Restful\Server
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Ui
{
    /**
     * JavaScript file
     * @var string
     */
    protected $_javascript = 'html/ui.js';
    
    /**
     * Base Url
     * @var string
     */
    protected $_baseUrl;
    
    /**
     * Store the base Url
     * @param string $baseUrl
     * @return void
     */
    public function __construct($baseUrl = '')
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * Output javascript
     */
    public function get()
    {
        $javascript = trim(file_get_contents($this->_javascript, true));
        $javascript = preg_replace('/\)\(\);$/', ")('" . $this->_baseUrl . "');", $javascript);
        ob_start();
        echo $javascript;
        
        //echo file_get_contents($this->_javascript, true);
    }

    /**
     * Short usage
     */
    public function __invoke()
    {
        $this->get();
    }
}
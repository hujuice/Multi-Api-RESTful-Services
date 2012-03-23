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
 * @package     Restful\Server\Resource
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 * @version
 */
namespace Restful\Server\Resource;

/**
 * Restful Server Resource User
 *
 * @package     Restful\Server\Resource
 * @subpackage  Server
 * @copyright   Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class User extends \Restful\Server\Resource
{
    /**
     * Default resource path
     * @var string
     */
    protected $_defaultPath = '../resources';

    /**
     * Create the resource
     *
     * @param string $name
     * @param \Restful\Config|null $config
     * @return void
     * @throw Exception
     */
    public function __construct($name, $config = null)
    {
        if ($config)
        {
            if (!$className = $config->class)
                $className = $name;

            $path = $config->path ? $config->path : $this->_defaultPath;

            if ($config->construct)
                $construct = $config->construct->toArray();
            else
                $construct = array();

            $httpMethod = $config->httpMethod;
            $max_age = $config->max_age;
        }
        else
        {
            $className = $name;
            $construct = array();
            $httpMethod = false;
            $max_age = null;
            $path = $this->_defaultPath;
        }

        require_once($path . DIRECTORY_SEPARATOR . $className . '.php');
        parent::__construct($className, $construct, $httpMethod, $max_age);
    }
}

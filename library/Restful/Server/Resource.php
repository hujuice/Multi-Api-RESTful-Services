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
 * Restful Server Resource
 *
 * @category   Restful
 * @package    Restful_Server
 * @copyright  Copyright (c) 2012 Sergio Vaccaro <hujuice@inservibile.org>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt     GPLv3
 */
class Restful_Server_Resource extends Restful_Server_ResourceAbstract
{
    /**
     * Create the resource
     *
     * @params string $name
     * @param array $config
     * @return void
     * @throw Exception
     */
    public function __construct($name, $config = null)
    {
        if ($config)
        {
            // Configuration file
            $config = parse_ini_file(API_PATH . $config, true);

            if (empty($config['resource']['class']))
                $className = ucfirst($name);
            else
                $className = $config['resource']['class'];

            if (!isset($config['construct']))
                $constructorParams = array();
            else
                $constructorParams = (array) $config['construct'];

            if (isset($config['resource']['path']))
                $classPath = $config['resource']['path'];
            else
                $classPath = false;

            if (isset($config['resource']['httpMethod']))
                $httpMethod = $config['resource']['httpMethod'];
            else
                $httpMethod = false;

            if (isset($config['resource']['maxAge']))
                $max_age = $config['resource']['maxAge'];
            else
                $max_age = null;
        }
        else
        {
            $className = ucfirst($name);
            $constructorParams = array();
            $classPath = false;
            $httpMethod = false;
            $max_age = null;
        }

        parent::__construct($className, $constructorParams, $classPath, $httpMethod, $max_age);
    }
}

<?php
class Restful_Server_Discover
{
    /**
     * Base URL
     * @var string
     */
    protected $_baseUrl;

    /**
     * Available resources
     * @var array
     */
    protected $_resources;

    /**
     * Build the discover
     *
     * @param array $resources
     * @return void
     */
    public function __construct($resources, $baseUrl = '')
    {
        $this->_baseUrl = '/' . trim($baseUrl, '/');
        $this->_resources = $resources;
    }

    /**
     * Give the resource catalogue
     *
     * @return array
     */
    public function resources()
    {
        $resources = array('resources' => array());
        foreach ($this->_resources as $name => $resource)
        {
            $resources['resources'][$name] = $resource->desc();
            $resources['resources'][$name]['HTTP'] = $resource->httpMethod();
            $resources['resources'][$name]['methods'] = $this->methods($name);
            $resources['resources'][$name]['discover'] = 'http://' . $_SERVER['SERVER_NAME'] . $this->_baseUrl . 'discover/methods?resource=' . $name;
        }

        return $resources;
    }

    /**
     * Give the methods for the resource
     *
     * @param string $resource
     * @return array
     */
    public function methods($resource)
    {
        $methods = array();
        if (isset($this->_resources[$resource]))
        {
            foreach ($this->_resources[$resource]->getMethods() as $method)
            {
                $methods[$method] = $this->_resources[$resource]->desc($method);
                $methods[$method]['params'] = $this->params($resource, $method);
                $methods[$method]['discover'] = 'http://' . $_SERVER['SERVER_NAME'] . $this->_baseUrl . 'discover/params?resource=' . $resource . '&method=' . $method;
            }

            return $methods;
        }
    }

    /**
     * Give the params for the method/resource
     *
     * @param string $resource
     * @param string $method
     * @return array
     */
    public function params ($resource, $method)
    {
        $params = array();
        if (isset($this->_resources[$resource]))
        {
            if ($method = $this->_resources[$resource]->checkMethod($method))
                return $this->_resources[$resource]->getParams($method);
        }
    }
}
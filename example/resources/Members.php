<?php
/**
 * Users for interplanetary website
 *
 * This is a stupid class where you have
 * human or computer users, from many planets
 */
class Members
{
    /**
     * Cosmic site members
     * @var array
     */
    protected $_members;

    /**
     * Read data from a file
     *
     * @param string $config
     * @return void
     */
    public function __construct($config)
    {
        //$this->_members = include($file);
        $handle = fopen(__DIR__ . '/' . $config['data'], 'r');
        while ($record = fgetcsv($handle, 256))
            $this->_members[] = $record;
        fclose($handle);
    }

    /**
     * Get contacted planet
     *
     * Only populated planets
     *
     * @return array
     */
    public function getPlanets()
    {
        $planets = array();
        foreach ($this->_members as $member)
        {
            if (!in_array($member[1], $planets))
                $planets[] = $member[1];
        }
        return $planets;
    }

    /**
     * Get users form a planet
     *
     * Try to give an
     * indication about the
     *                  people in a planet
     *
     * @param string $planet A planet
     * @return array
     */
    public function getFromPlanet($planet = 'Earth')
    {
        $people = array();
        foreach ($this->_members as $member)
        {
            if ($member[1] == $planet)
                $people[] = $member[0] . ', ' . $member[2];
        }
        return $people;
    }

    /**
     * How many humans?
     *
     * Humans are more important!
     *
     * @return integer
     */
    public function getHumans()
    {
        $count = 0;
        foreach ($this->_members as $member)
        {
            if ('Human' == $member[2])
                $count++;
        }
        return $count;
    }
}
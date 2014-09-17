<?php
$tom = new ToyRobot("Tom");
$jim = new ToyRobot("Jim");
$tom->writeName();
$jim->writeName();

class ToyRobot
{
    private $_name;
    /**
     * The construct.
     *
     * @param string $name
     *  Sets the name property upon class instantiation.
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }
    /**
     *   Writes the robot's name.
     */
    public function writeName()
    {
        echo 'My name is ', $this->_name, '.<br />';
    }
}
?>
<?php
declare(strict_types=1);


/**stores data of a distinct tag
*
 * very generic
*/
class TagData
{
    public $name;
    public $attr;
    public $cont;

    /**
     * @param string $name name of object
     * @param array $attr attributes of object
     */
    public function __construct($name, $attr)
    {
        $this->cont = [];
        if ($name != null)
            $this->name = $name;
        if ($attr != null)
            $this->attr = $attr;
    }
}
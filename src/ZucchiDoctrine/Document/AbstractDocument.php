<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\Document;

use Doctrine\Common\Collections\Collection;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;
use Zend\Form\Annotation AS Form;

/**
 * Abstract Document
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine 
 * @subpackage Document
 */
class AbstractDocument implements
    \JsonSerializable
{

    /**
     * @var integer
     * @ODM\Id
     */
    protected $id;

    /**
     * @var integer
     * @ODM\Int
     */
    protected $version;
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }


    /**
     * magic getter
     *
     * @param $property
     * @return mixed
     */
    public function __get($key)
    {
        return $this->{$key};
    }

    /**
     * Magic setter
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->{$key} = $value;
    }


    /**
     * return public and protected properties as an array
     * 
     * @param boolean|integer $deep include nested objects and collections to the specified depth
     * @param boolean $all include private 
     * @return array
     */
    public function toArray($deep = true, $all = false, $visited = array())
    {
        // disable recursion;
        $hash = spl_object_hash($this);
        if (in_array($hash, $visited)) {
            return '*RECURSION';
        }
        $visited[] = $hash;

        $getpublic = function($obj) { return get_object_vars($obj); };
        
        $data = ($all) 
              ? get_object_vars($this)
              : $getpublic($this);

        foreach ($data as $key => $value){
            if (strpos($key, '_') === 0) {
                unset($data[$key]);
            }
        }

        if (is_integer($deep)) $deep--;

        foreach ($data AS $key => $val) {
            if (is_object($val)) {
                if ($deep && $val instanceof Collection) {
                    $data[$key] = array();
                    foreach ($val->toArray() as $rel) {
                        $data[$key][] = $rel->toArray($deep, $all, $visited);
                    }
                } else if ($deep && method_exists($val, 'toArray')) {
                    $data[$key] = $val->toArray($deep, $all, $visited);

                } else if (!$deep) {
                    unset($data[$key]);
                }
            } 
        }
        return $data;
    }
    
    /**
     * (non-PHPdoc)
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toString()
    {
        return $this->__toString();
    }
    
    /**
     * get a string representation of an entity
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }

    /**
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public function fromArray($options)
    {
        if (!is_array($options) && !$options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            } else if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}

<?php
namespace  Flynax\Components;

class ObjectsContainer
{
    protected $_instances;
    public static $instance;
    
    /**
     * @var \reefless
     */
    public $reefless;
    
    /**
     * ObjectsContainer constructor.
     */
    public function __construct()
    {
        $this->reefless = $GLOBALS['reefless'];
        $this->_instances = new \stdClass();
    }
    
    public static function getIntance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    
    public static function i()
    {
        return self::getIntance();
    }
    
    private function resolve($className)
    {
    
    }
    
    
    
    public function make($className)
    {
        if (isset($this->_instances->{$className})) {
            return $this->_instances->{$className};
        }
        
        if(!$this->reefless) {
            return false;
        }
        
        if ($sanitizedClassName = $this->prepareReeflessClassName($className)) {
            if (is_object($GLOBALS[$className])) {
                return $this->_instances->{$className} = $GLOBALS[$className];
            }
    
            $this->reefless->loadClass($sanitizedClassName);
            return $this->_instances->{$className} = $GLOBALS[$className];
        }
    }
    
    private function prepareReeflessClassName($className)
    {
        if (strpos($className, 'rl') === 0) {
            return substr($className, 2);
        }
        
        return '';
    }
    
    public function makePlugin($className)
    {
    
    }
    
    public function makeStatic($className)
    {
//        if (!class_exists($className)) {
//            return false;
//        }
//
//        return $className::class;
    }
}

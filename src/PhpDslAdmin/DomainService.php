<?php
namespace PhpDslAdmin;

/**
 * Tracks registered domain objects(models) and resolves class and form names
 */
class DomainService
{
    protected $registeredObjects = array();
    
    public function __construct()
    {
        // $this->registeredObjects = $objects;
    }
    
    public function registerObject($object)
    {
        $this->registeredObjects[] = $object;
    }
    
    public function isRegistered($class)
    {
        return in_array($class, $this->registeredObjects);
    }
    
    public function resolveForm($name)
    {
        Assert::isString($name, 'domain object name');
        
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Form name must be a string');
        }
        $formName = str_replace(array('.', '\\'), '_', $name);
        
        if (!$this->isRegistered($formName)) {
            throw new \InvalidArgumentException('Form '.$formName.' does not exist or was not registered');
        }
    }
    
    public function resolveClass($name)
    {
        $class = str_replace('.', '\\', $name);
        if (!class_exists($class)) {
            throw new \InvalidArgumentException('Class '.$class.' does not exist');
        }
        return $class;
    }
}

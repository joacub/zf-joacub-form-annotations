<?php

namespace MnsFormAnnotation\Service;

use Zend\Cache\StorageFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DoctrineORMModule\Form\Annotation\AnnotationBuilder;
use Nette\Diagnostics\Debugger;

/**
 * Use ZF2 form annotations builder to generate a form
 *
 * @author nshankar
 */
class FormGenerator implements FactoryInterface {

    protected $className;
    protected $keyName;
    protected $cache;
    protected $em;

    public function setClass($className) {
        $this->className = $className;
        //remove special characters from class name in cache
        $this->keyName = str_replace('\\', '_', $className);
        return $this;   //fluent interface
    }

    public function getForm() {
        $builder = new AnnotationBuilder($this->em);
        if ($this->cache) {
            if (!($this->cache->hasItem($this->keyName))) {
                $form = $builder->createForm(new $this->className);
                $this->cache->setItem($this->keyName, $form);
            } else {
                //var_dump("Cache hit");
                $form = $this->cache->getItem($this->keyName);
            }
        } else {    //Do not use cache
            $form = $builder->createForm(new $this->className);
        }
        
        $elements = $form->getElements();
        
        foreach($elements as $element) {
            if(method_exists($element, 'getProxy')){
                $proxy = $element->getProxy();
                if(method_exists($proxy, 'setObjectManager')){
                    $proxy->setObjectManager($this->em);
                }
            }
        }
        
        $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->em, $this->className);
        $form->setHydrator($hydrator);
        return $form;
    }

    public function createService(ServiceLocatorInterface $serviceLocator) {
        $this->em = $serviceLocator->get('doctrine.entitymanager.orm_default');
        $config = $serviceLocator->get('config');
        if ($config['mns_cache_config']['caching'] === true) {
            $cacheConfig = $config['mns_cache_config'];
            //initialize cache
            $this->cache = StorageFactory::factory($cacheConfig);
        }
        return $this;  //fluent interface
    }
    

    public function __call($property, $value)
    {
        exit;
    }
}

?>

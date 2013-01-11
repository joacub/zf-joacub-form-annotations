<?php

namespace MnsFormAnnotation\Service;

use Zend\Cache\StorageFactory;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Use ZF2 form annotations builder to generate a form
 *
 * @author nshankar
 */
class FormGenerator implements FactoryInterface {

    protected $className;
    protected $keyName;
    protected $cache;

    public function setClass($className) {
        $this->className = $className;
        //remove special characters from class name in cache
        $this->keyName = str_replace('\\', '_', $className);
        return $this;   //fluent interface
    }

    public function getForm() {
        $builder = new AnnotationBuilder();
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
        return $form;
    }

    public function createService(ServiceLocatorInterface $serviceLocator) {
        $config = $serviceLocator->get('config');
        if ($config['mns_cache_config']['caching'] === true) {
            $cacheConfig = $config['mns_cache_config'];
            //initialize cache
            $this->cache = StorageFactory::factory($cacheConfig);
        }
        return $this;  //fluent interface
    }

}

?>

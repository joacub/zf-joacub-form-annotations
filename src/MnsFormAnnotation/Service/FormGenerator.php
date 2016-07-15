<?php

namespace MnsFormAnnotation\Service;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Cache\StorageFactory;
use Zend\Form\Element\Collection;
use Zend\Form\Fieldset;
use Zend\Form\InputFilterProviderFieldset;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
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
        $fieldsets = $form->getFieldsets();

        $this->settingElements($elements);
        $this->settingFieldsets($fieldsets);

        $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->em, $this->className);
        $form->setHydrator($hydrator);

        return $form;
    }

    protected function settingElements($elements)
    {
        foreach($elements as $element) {
            if (method_exists($element, 'getProxy')) {
                $proxy = $element->getProxy();
                if (method_exists($proxy, 'setObjectManager')) {
                    $proxy->setObjectManager($this->em);
                }
            }
        }
    }

    protected function settingFieldsets($fieldsets, $hydrator = null)
    {
        foreach($fieldsets as $fieldset) {

            if($fieldset instanceof Collection) {
                $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->em, $fieldset->getOption('target_class'));
                $fieldset->setHydrator($hydrator);
                $fieldset->setAllowedObjectBindingClass($fieldset->getOption('target_class'));
                $targetElement = $fieldset->getTargetElement();
                if($targetElement instanceof InputFilterProviderFieldset) {
                    $class = $fieldset->getOption('target_class');
                    $targetElement->setObject(new $class());
                    $targetElement->setHydrator($hydrator);
                    $targetElement->setAllowedObjectBindingClass($fieldset->getOption('target_class'));
                }

//                Debugger::$maxDepth = 5;
//                Debugger::dump($fieldset->setOption('hydrator', $hydrator)->getOptions());exit;
            } else {
                /**
                 * @var Fieldset $fieldset
                 */
                $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->em, $fieldset->getOption('target_class'));
                $fieldset->setHydrator($hydrator);

                $fieldset->setAllowedObjectBindingClass($fieldset->getOption('target_class'));
            }

            $this->settingElements($fieldset->getElements());


            $this->settingFieldsets($fieldset->getFieldsets(), $hydrator);
        }

    }

    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ModuleManager::class);
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->em = $container->get('doctrine.entitymanager.orm_default');
        $config = $container->get('config');
        if ($config['mns_cache_config']['caching'] === true) {
            $cacheConfig = $config['mns_cache_config'];
            //initialize cache
            $this->cache = StorageFactory::factory($cacheConfig);
        }
        return $this;  //fluent interface
    }

}

?>

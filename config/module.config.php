<?php

namespace MnsFormAnnotation;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
return array(
    'mns_cache_config' => array(
        'adapter' => array(
            'name' => 'filesystem',
            'options' => array(
                'ttl' => 100,
                'dir_permission' => 0755,
                'cache_dir' => 'data/cache',
            ),
        ),
        'plugins' => array(
            array(
                'name' => 'serializer',
                'exception_handler' => array('throw_exceptions' => false),
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'MnsFormAnnotation\Controller\MnsFormAnnotation' => 'MnsFormAnnotation\Controller\IndexController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'form' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/form[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'MnsFormAnnotation\Controller\MnsFormAnnotation',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'MnsFormAnnotation' => __DIR__ . '/../view',
        ),
    ),   
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host'     => 'localhost',
                    'port'     => '3306',
                    'user'     => 'username',
                    'password' => 'password',
                    'dbname'   => 'database_name',
                )
            )
        ),
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/' . __NAMESPACE__ . '/Entity')
            ),
            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ),
            ),
        ),
        
    ),
);
?>

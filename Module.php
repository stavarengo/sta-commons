<?php
namespace Sta\Commons;

class Module
{
    public function getConfig()
    {
        $var = include __DIR__ . '/config/module.config.php';

        return $var;
    }

    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
}

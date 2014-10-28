<?php

namespace SpeedUpEssentials;

class Module {

    protected $sm;

    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
        $app = $e->getTarget();
        $app->getEventManager()->attach('finish', array($this, 'minifyHtml'), 100);
    }

    public function minifyHtml(\Zend\Mvc\MvcEvent $e) {
        $response = $e->getResponse();
        $this->sm = $e->getApplication()->getServiceManager();
        $config = $this->sm->get('config');
        $SpeedUpEssentials = new SpeedUpEssentials($config['SpeedUpEssentials']);
        $response->setContent(
                $SpeedUpEssentials->render(
                        $response->getBody()
                )
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

}

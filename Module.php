<?php

namespace SpeedUpEssentials;

class Module {

    protected $sm;

    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
        $app = $e->getTarget();
        $app->getEventManager()->attach('finish', array($this, 'speedUp'), 100);
    }

    public function speedUp(\Zend\Mvc\MvcEvent $e) {
        $response = $e->getResponse();
        $this->sm = $e->getApplication()->getServiceManager();
        $config = $this->sm->get('config');
        $SpeedUpEssentials = new SpeedUpEssentials(isset($config['speed_up_essentials']) ? $config['speed_up_essentials'] : false, $this->sm->get('Request')->getBasePath() . '/');
        $html = $response->getBody();
        $response->setContent(
                $SpeedUpEssentials->render($html)
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

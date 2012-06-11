<?php
require_once 'ReposerverController.php';

class ReposerverPlugin extends OntoWiki_Plugin
{
    public function init() {
    }

    public function onPingReceived($event)
    {
        $owApp = OntoWiki::getInstance();
        $logger = $owApp->logger;

        $logger->debug('onPingReceived called');
        if ($event->p == ReposerverController::OW_CONFIG_NS.'registeredAt') {
            try{
                ReposerverController::addExtension($event->s, $this->_privateConfig->url);
                $logger->debug('Success');
            } catch ( Exception $e){
                $logger->debug('Error: '.$e);
            }
        }
    }
}

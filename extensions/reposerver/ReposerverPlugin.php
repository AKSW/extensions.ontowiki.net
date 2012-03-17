<?php
require_once 'OntoWiki/Plugin.php';

class ReposerverPlugin extends OntoWiki_Plugin
{
    public function init() {
    }

    public function onPingReceived($event)
    {
        if($event->p == ReposerverController::EXTENSION_NS.'registeredAt'){
            try{
                ReposerverController::addExtension($event->s, $this->_privateConfig->url);            
            } catch ( Exception $e){
                $this->_log('Error: '.$e);
            }
        }
    }
}

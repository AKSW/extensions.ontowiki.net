<?php
require_once 'OntoWiki/Plugin.php';

class ReposerverPlugin extends OntoWiki_Plugin
{
    public function init() {
    }

    public function onPingReceived($event)
    {
        if($event->p == ReposerverController::OW_CONFIG_NS.'registeredAt'){
            try{
                self::addExtension($event->s, $this->_privateConfig->url);            
            } catch ( Exception $e){
                $this->_log('Error: '.$e);
            }
            
            
        }
    }
}

<?php

/**
 * Controller for OntoWiki Filter Module
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_components_files
 * @author     Christoph Rieß <c.riess.dev@googlemail.com>
 * @author     Norman Heino <norman.heino@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: FilesController.php 4090 2009-08-19 22:10:54Z christian.wuerker $
 */
class ReposerverController extends OntoWiki_Controller_Component
{
    
    /**
     * Default action. Forwards to get action.
     */
    public function __call($action, $params)
    {
        $this->_forward('update');
    }
    
    
    public function updateAction()
    {
        if ($this->_request->isPost()) {
            $url = $this->_request->getParam('url');
            $ow = $this->_owApp;
            $store = $this->_erfurt->getStore();
            
            $repoGraphUrl = $this->_privateConfig->url;
            if($store->isModelAvailable($repoGraphUrl)){
                $repoGraph = $store->getModel($repoGraphUrl);
            } else {
                $repoGraph = $store->getNewModel($repoGraphUrl, '', Erfurt_Store::MODEL_TYPE_OWL,false);
            }
            
            //fill new model via linked data
            require_once $ow->extensionManager->getExtensionPath('datagathering') . DIRECTORY_SEPARATOR . 'DatagatheringController.php';
            $res = DatagatheringController::import($repoGraphUrl, $url, $url, false, array(), array(), 'linkeddata', 'none', 'update', false);

            if($res == DatagatheringController::IMPORT_OK){
                return $this->_sendResponse($res, 'The wrapper had an error.', OntoWiki_Message::ERROR);
            } else {
                if($res == DatagatheringController::IMPORT_WRAPPER_ERR){
                    return $this->_sendResponse($res, 'The wrapper had an error.', OntoWiki_Message::ERROR);
                } else if($res == DatagatheringController::IMPORT_NO_DATA){
                    return $this->_sendResponse($res, 'No statements were found.', OntoWiki_Message::ERROR);
                } else if($res == DatagatheringController::IMPORT_WRAPPER_INSTANCIATION_ERR){
                    return $this->_sendResponse($res, 'could not get wrapper instance.', OntoWiki_Message::ERROR);
                    //$this->_response->setException(new OntoWiki_Http_Exception(400));
                } else if($res == DatagatheringController::IMPORT_NOT_EDITABLE){
                    return $this->_sendResponse($res, 'you cannot write to this model.', OntoWiki_Message::ERROR);
                    //$this->_response->setException(new OntoWiki_Http_Exception(403));
                } else if($res == DatagatheringController::IMPORT_WRAPPER_EXCEPTION){
                    return $this->_sendResponse($res, 'the wrapper run threw an error.', OntoWiki_Message::ERROR);
                } else {
                    return $this->_sendResponse($res, 'unexpected return value.', OntoWiki_Message::ERROR);
                }
            }
        }
    }
    
    private function _sendResponse($returnValue, $message = null, $messageType = OntoWiki_Message::SUCCESS)
    {
        $this->_response->setHeader('Content-Type', 'application/json', true);
        $this->_response->setBody(json_encode(array("status"=>$returnValue==DatagatheringController::IMPORT_OK, "returnValue"=>$returnValue, "message"=>$message)));
        $this->_response->sendResponse();
        exit;
    }
}


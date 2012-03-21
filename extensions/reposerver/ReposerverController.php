<?php
/**
 * This file is part of the {@link http://erfurt-framework.org Erfurt} project.
 *
 * @copyright Copyright (c) 2011, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Controller for OntoWiki Repository Server
 *
 * @category   OntoWiki
 * @package    OntoWiki_extensions_reposerver
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 */
class ReposerverController extends OntoWiki_Controller_Component
{

    const OW_CONFIG_NS = 'http://ns.ontowiki.net/SysOnt/ExtensionConfig/';
    const FOAF_NS = 'http://xmlns.com/foaf/0.1/';
    const DOAP_NS = 'http://usefulinc.com/ns/doap#';

    /**
     * Default action. Forwards to get action.
     */
    public function __call($action, $params) {
        $this->_forward('update');
    }


    public function updateAction()
    {
        if ($this->_request->isPost()) {
            $url = $this->_request->getParam('url');

            $store = $this->_erfurt->getStore();

            $repoGraphUrl = $this->_privateConfig->url;
            if($store->isModelAvailable($repoGraphUrl)){
                $repoGraph = $store->getModel($repoGraphUrl);
            } else {
                $repoGraph = $store->getNewModel($repoGraphUrl, '', Erfurt_Store::MODEL_TYPE_OWL, false);
            }

            $res = self::addExtension($url, $repoGraphUrl);

            if($res == DatagatheringController::IMPORT_OK){
                return $this->_sendResponse($res, 'extension registered/updated.');
            } else {
                if($res == DatagatheringController::IMPORT_WRAPPER_ERR){
                    return $this->_sendResponse($res, 'The wrapper had an error.');
                } else if($res == DatagatheringController::IMPORT_NO_DATA){
                    return $this->_sendResponse($res, 'No new statements were found with linked data under this url.');
                } else if($res == DatagatheringController::IMPORT_WRAPPER_INSTANCIATION_ERR){
                    return $this->_sendResponse($res, 'could not get wrapper instance.');
                } else if($res == DatagatheringController::IMPORT_NOT_EDITABLE){
                    return $this->_sendResponse($res, 'you cannot write to the extension-repo model.');
                } else if($res == DatagatheringController::IMPORT_WRAPPER_EXCEPTION){
                    return $this->_sendResponse($res, 'the wrapper run threw an unexpected exception.');
                } else if($res == DatagatheringController::IMPORT_WRAPPER_NOT_AVAILABLE){
                    return $this->_sendResponse($res, 'the data is not available. is the url correct?');
                } else {
                    return $this->_sendResponse($res, 'unexpected return value.');
                }
            }
        }
    }

    private function _sendResponse($returnValue, $message = "")
    {
        $this->_response->setHeader('Content-Type', 'application/json', true);
        $this->_response->setBody(json_encode(array("status"=> $returnValue==DatagatheringController::IMPORT_OK, "returnValue"=>$returnValue, "message"=>$message)));
        $this->_response->sendResponse();
        exit;
    }

    public static function addExtension($extensionUrl, $repoGraphUrl){
        $ow = OntoWiki::getInstance();
        $store = Erfurt_App::getInstance()->getStore();

        //create repo graph if not exists
        if (!$store->isModelAvailable($repoGraphUrl)){
            // create model
            $store->getNewModel(
                $repoGraphUrl,
                '',
                Erfurt_Store::MODEL_TYPE_OWL,
                false
            );
        }

        //import each extension into its own model
        if (!$store->isModelAvailable($extensionUrl)){
            // create model
            $store->getNewModel(
                $extensionUrl,
                '',
                Erfurt_Store::MODEL_TYPE_OWL,
                false
            );

            //import
            $store->addStatement($repoGraphUrl, $repoGraphUrl, EF_OWL_IMPORTS, array('value'=>$extensionUrl, 'type'=>'uri'));


            //connect repo to that extension
            $store->addStatement($repoGraphUrl, $repoGraphUrl, 'hasExtension', array('value'=>$extensionUrl, 'type'=>'uri'));
        }

        //fill new model via linked data
        require_once $ow->extensionManager->getExtensionPath('datagathering') . DIRECTORY_SEPARATOR . 'DatagatheringController.php';
        $res = DatagatheringController::import($extensionUrl, $extensionUrl, $extensionUrl, true, array(), array(), 'linkeddata', 'none', 'update', true, array(__CLASS__, 'filter'));
        return $res;
    }

    static function filter($statements)
    {
        $model = new Erfurt_Rdf_MemoryModel($statements);
        $extensionUri = $model->getValue('', self::FOAF_NS.'primaryTopic');
        //$privateNS = $model->getValue($extensionUri, self::OW_CONFIG_NS.'privateNamespace');
        $allowedSubjects = array('', $extensionUri); //TODO @base is empty when parsing n3
        $releases = $model->getValues($extensionUri, self::DOAP_NS.'release');
        foreach ($releases as $value) {
            $allowedSubjects[] = $value['value']; //add release versions as allowed subjects
        }
        $logger = Erfurt_App::getInstance()->getLog('repo');
        $logger->log('$allowedSubjects: '. print_r($allowedSubjects, true), 1);

        $allowedPredicates = array(
            EF_RDF_TYPE,
            EF_RDFS_LABEL,
            self::DOAP_NS.'name',
            self::DOAP_NS.'description',
            self::DOAP_NS.'maintainer',
            self::OW_CONFIG_NS.'authorLabel',
            self::OW_CONFIG_NS.'latestZip',
            self::DOAP_NS.'release', //links to the versions
            self::DOAP_NS.'revision', //the following are properties of the versions
            self::DOAP_NS.'created',
            self::DOAP_NS.'file-release'
        );

        foreach ($model->getSubjects() as $subject) {
            if (!in_array($subject, $allowedSubjects)) {
                $model->removeS($subject);
            } else {
                foreach ($model->getPO($subject) as $predicate => $values) {
                    if (!in_array($predicate, $allowedPredicates)) {
                        $model->removeSP($subject, $predicate);
                    }
                }
            }
        }
        
        //generate latest version triple, if not present (the extensionlist cannot display the indirect property of the version)
        if($model->getValue($extensionUri, self::OW_CONFIG_NS.'latestZip') == null){
            $newestVersion = null;
            $newestVersionDate = null;
            foreach($releases as $release){
                $date = $model->getValue($release['value'], self::DOAP_NS.'revision');
                if($date == null){
                    continue;
                }
                if($newestVersion == null || version_compare($date['value'], $newestVersionDate, '>')){
                    $newestVersion = $release['value'];
                    $newestVersionDate = $date['value'];
                }
            }
            $newestFile = $model->getValue($newestVersion, self::DOAP_NS.'file-release');
            if($newestFile != null){
                $model->addRelation($extensionUri, self::OW_CONFIG_NS.'latestZip', $newestVersionDate);
            }
        }

        return $model->getStatements();
    }
}


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
                } elseif($res == DatagatheringController::IMPORT_CUSTOMFILTER_EXCEPTION){
                    return $this->_sendResponse($res, 'the doap data misses required properties');
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
        }
        //import
        $store->addStatement($repoGraphUrl, $repoGraphUrl, EF_OWL_IMPORTS, array('value'=>$extensionUrl, 'type'=>'uri'), false);

        //connect repo to that extension
        $store->addStatement($extensionUrl, $repoGraphUrl, self::OW_CONFIG_NS.'hasExtension', array('value'=>$extensionUrl, 'type'=>'uri'), false);
        

        //fill new model via linked data
        require_once $ow->extensionManager->getExtensionPath('datagathering') . DIRECTORY_SEPARATOR . 'DatagatheringController.php';
        $res = DatagatheringController::import($extensionUrl, $extensionUrl, $extensionUrl, true, array(), array(), 'linkeddata', 'none', 'update', true, array(__CLASS__, 'filter'));
        return $res;
    }

    /**
     * this method is passed as a callback to Datagathering::import
     * some checks are made
     * some information is infered
     * @param array $statements
     * @return array
     * @throws Exception 
     */
    static function filter($statements)
    {
        $model = new Erfurt_Rdf_MemoryModel($statements);
        $extensionUri = $model->getValue('', self::FOAF_NS.'primaryTopic');
        //$privateNS = $model->getValue($extensionUri, self::OW_CONFIG_NS.'privateNamespace');
        $releases = $model->getValues($extensionUri, self::DOAP_NS.'release');
        $maintainerUri = $model->getValue($extensionUri, self::DOAP_NS.'maintainer');

        $schema =array(
            $extensionUri => array(
                EF_RDF_TYPE=>false, //this booleans means "mandatory"
                EF_RDFS_LABEL=>false,
                self::DOAP_NS.'name'=>true,
                self::DOAP_NS.'description'=>true,
                self::DOAP_NS.'maintainer'=>true,
                self::DOAP_NS.'homepage'=>false,
                self::OW_CONFIG_NS.'latestZip'=>false,
                self::OW_CONFIG_NS.'registeredAt'=>false,
                self::OW_CONFIG_NS.'latestRevision'=>false,
                self::DOAP_NS.'release'=>true //links to the versions
            ),
            $maintainerUri => array(            
                self::FOAF_NS.'mbox'=>false,
                self::FOAF_NS.'name'=>true,
                self::FOAF_NS.'homepage'=>false,
                EF_RDF_TYPE=>false
            )
        );
        foreach($releases as $release){
            $schema[$release['value']] = array(
                self::DOAP_NS.'revision'=>true,
                self::DOAP_NS.'created'=>false,
                self::DOAP_NS.'file-release'=>true
            );
        }
        
        //check for missing properties
        foreach($schema as $s => $ps){
            foreach($ps as $p=>$mandatory){
                if($mandatory && !$model->hasSP($s, $p)){
                    echo "missing $s $p";
                    throw new Exception('missing property '.$property);
                }
            }
        }

        //check for forbidden subjects
        foreach ($model->getSubjects() as $subject) {
            if (!isset($schema[$subject])) {
                $model->removeS($subject);
            } else {
                //check for forbidden properties
                foreach ($model->getPO($subject) as $predicate => $values) {
                    if (!isset($schema[$subject][$predicate])) {
                        $model->removeSP($subject, $predicate);
                    }
                }
            }
        }
                
        //shortcut latest version triple (the extensionlist cannot display the indirect properties)
        //TODO implement indirect properties in OntoWiki_Model_Instances 
        if($model->getValue($extensionUri, self::OW_CONFIG_NS.'latestZip') == null){
            $newestVersion = null;
            $newestRevisionNumber = null;
            foreach($releases as $release){
                $revisionNumber = $model->getValue($release['value'], self::DOAP_NS.'revision');
                if($revisionNumber == null){
                    continue;
                }
                if($newestVersion == null || version_compare($revisionNumber, $newestRevisionNumber, '>')){
                    $newestVersion = $release['value'];
                    $newestRevisionNumber = $revisionNumber;
                }
            }
            $newestFile = $model->getValue($newestVersion, self::DOAP_NS.'file-release');
            if($newestFile != null){
                $model->addRelation($extensionUri, self::OW_CONFIG_NS.'latestZip', $newestFile);
                $model->addAttribute($extensionUri, self::OW_CONFIG_NS.'latestRevision', $newestRevisionNumber);
            }
        }
        //shortcut author info 
        if($model->getValue($extensionUri, self::OW_CONFIG_NS.'authorLabel') == null){
            $authorLabel = $model->getValue($maintainerUri, self::FOAF_NS.'name');
            if($newestFile != null){
                $model->addRelation($extensionUri, self::OW_CONFIG_NS.'authorLabel', $authorLabel);
            }
        }
        if($model->getValue($extensionUri, self::OW_CONFIG_NS.'authorPage') == null){
            $authorPage = $model->getValue($maintainerUri, self::FOAF_NS.'homepage');
            if($authorPage != null){
                $model->addRelation($extensionUri, self::OW_CONFIG_NS.'authorPage', $authorPage);
            }
        }
        if($model->getValue($extensionUri, self::OW_CONFIG_NS.'authorMail') == null){
            $authorMail = $model->getValue($maintainerUri, self::FOAF_NS.'mbox');
            if($authorMail != null){
                $model->addRelation($extensionUri, self::OW_CONFIG_NS.'authorMail', $authorMail);
            }
        }

        return $model->getStatements();
    }
}


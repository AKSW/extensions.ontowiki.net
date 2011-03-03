<?php
class OntoWiki_Controller_ActionHelper_List extends Zend_Controller_Action_Helper_Abstract{
    protected $_owApp;

    public function  __construct() {
        $this->_owApp = OntoWiki::getInstance();
        if(!isset($this->_owApp->session->managedLists)){
            $this->_owApp->session->managedLists = array();
        }
    }

    public function getLastList(){
        $name = $this->_owApp->session->lastList;
        if(isset($this->_owApp->session->managedLists)){
            $lists = $this->_owApp->session->managedLists;
            if (key_exists($name, $lists)) {
                return $lists[$name];
            }
        }
        return null;
    }
    public function getLastListName(){
        return $this->_owApp->session->lastList;
    }

    public function listExists($name){
        $lists = $this->_owApp->session->managedLists;
        if (key_exists($name, $lists)) {
            return true;
        }
        
        return false;
    }
    
    public function getList($name){
        $lists = $this->_owApp->session->managedLists;
        
        if (key_exists($name, $lists)) {
            //$lists[$name]->setStore($this->_owApp->erfurt->getStore()); //after serialization the store is compromized
            return $lists[$name];
        }
        
        throw new InvalidArgumentException("list was not found. check with listExists() first");
    }
    
    public function addListPermanently($name, OntoWiki_Model_Instances $list, Zend_View_Interface $view, $templateName = null){
        $this->updateList($name, $list, true);
        $this->addList($name, $list, $view, $templateName);
    }

    public function addList($listName, OntoWiki_Model_Instances $list, Zend_View_Interface $view, $templateName = null, $mainTemplate = 'list_std_main', $elementTemplate = 'list_std_element'){
        $this->getResponse()->append('default',
            $view->partial('partials/list.phtml',
                array(
                    'listName'              => $listName,
                    'instances'             => $list,
                    'additionalElementView' => $templateName,
                    'mainTemplate'          => $mainTemplate,
                    'elementTemplate'       => $elementTemplate
                )
             )
        );
        $this->_owApp->session->lastList = $listName;
        $this->getActionController()->addModuleContext('main.window.list');
    }

    public function updateList($name, OntoWiki_Model_Instances $list, $setLast = false){
        $lists = $this->_owApp->session->managedLists;
        $lists[$name] = $list;
        $this->_owApp->session->managedLists = $lists;
        if($setLast){
            $this->_owApp->session->lastList = $name;
        }
    }

    public function getAllLists(){
        return $this->_owApp->session->managedLists;
    }
}


?>

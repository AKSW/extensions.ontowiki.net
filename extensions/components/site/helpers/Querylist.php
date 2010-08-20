<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2010, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * OntoWiki Querylist view helper
 *
 * this helper executes a SPARQL query, renders each row with a given template
 * and outputs the resulting string
 *
 * @category OntoWiki
 * @package    OntoWiki_extensions_components_site
 * @copyright Copyright (c) 2010, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class Site_View_Helper_Querylist extends Zend_View_Helper_Abstract
{
    public function querylist($query, $template)
    {
        $owapp = OntoWiki::getInstance();
        $store = $owapp->erfurt->getStore();
        $view  = $owapp->view;
        $return = '';

        try {
            $result = $store->sparqlQuery($query);
        } catch (Exception $e) {
            // executions failed (return nothing)
            return $e->getMessage();
        }
 
        foreach($result as $row){
            $return .= $view->partial($template, $row);
        }
        return $return;
    }

}

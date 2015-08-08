<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;

use Piwik\Common;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\View;
use Piwik\ViewDataTable\Factory as ViewDataTableFactory;
use Piwik\Plugins\ServerMonitor\Reports\GetSoftwareHistory;
use Piwik\DataTable\Renderer\Json;

class Controller extends \Piwik\Plugin\ControllerAdmin 
{
    public function index()
    {
        $category = Common::getRequestVar('category', false);
        
        if ($category) {
            return $this->getCategoryGraphs($category);
        } else {
            // Render the Twig template templates/index.twig 
            return $this->renderTemplate('index', array(
                'server' => Common::getRequestVar('server', 'Server Monitor')
            ));            
        }

    }

    public function getCategories() {
        $server = Common::getRequestVar('server', false);

        if ($server) {
            Json::sendHeaderJSON();
            return json_encode(API::getInstance()->getCategories($server));
        }

        return null;
    }
    
    public function getCategoryGraphs($category) 
    {
        $view = new View('@ServerMonitor/getCategoryView');

        $this->setGeneralVariablesView($view);

        $categoryGraphs = array();
        $config = API::getInstance()->getConfig();
        
        $processed = array();
        foreach ($config as $domain => $servers) {
            foreach ($servers as $server => $names) { 
                foreach ($names as $name => $attributes) {
                    if (!in_array($name, $processed) && !empty($attributes['graph_category']) && $attributes['graph_category'] == $category) {
                        $processed[] = $name;
                        $_GET['name'] = $name;
                        $categoryGraphs[] = array('title'=>$attributes['graph_title'], 'graph'=>$this->getGraph(array(), array(), $name));
                    }
                }          
            }
        }
        
        $view->categoryGraphs = $categoryGraphs;
        
        return $view->render();
        
    }
    
    public function getGraph(array $columns = array(), array $defaultColumns = array())
    {

        if (empty($columns)) {
            $columns = Common::getRequestVar('columns', false);
            if (false !== $columns) {
                $columns = Piwik::getArrayFromApiParameter($columns);
            }
        }

        $name = Common::getRequestVar('name', false);
        $server = Common::getRequestVar('server', false); 
        $selectableColumns = API::getInstance()->getMetrics($name, $server);

        $view = $this->getLastUnitGraphAcrossPlugins($this->pluginName, __FUNCTION__, $columns,
            $selectableColumns, 'My documentation', 'ServerMonitor.getGraphEvolution');

        $view->requestConfig->disable_generic_filters=true;
    
        foreach ($view->config->columns_to_display as $key => $col) {
            if (!in_array($col, $selectableColumns)) {
                unset($view->config->columns_to_display[$key]);
            }
        }
        
        if (empty($view->config->columns_to_display) && !empty($defaultColumns)) {
            $view->config->columns_to_display = $defaultColumns;
        }

        return $this->renderView($view);
    }

}

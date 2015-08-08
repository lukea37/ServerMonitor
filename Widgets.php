<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;
use Piwik\Piwik;
use Piwik\View;
use Piwik\WidgetsList;

class Widgets extends \Piwik\Plugin\Widgets
{

    protected $category = 'Server';

    /**
     * Get Munin Widgets
     */
    protected function init()
    {
        if (!Piwik::hasUserSuperUserAccess()) return;
        
        $config = API::getInstance()->getConfig();
   
        // Add widgets
        foreach ($config as $domain => $servers) { 
            foreach ($servers as $server => $names) { 
                foreach ($names as $name => $attributes) {
                    if (!empty($attributes['graph_title']) && !empty($name)) $this->addWidget($attributes['graph_title'], $method = 'getGraph', $params = array('name' => $name));        
                }    
            }
        }

    }

}

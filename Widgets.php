<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;

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
        $plugins = API::getInstance()->getPlugins();
   
        // Add widgets
        foreach ($plugins as $server => $names) { 
            foreach ($names as $name => $attributes) {
                $this->addWidget($attributes['graph_title'], $method = 'getGraph', $params = array('name' => $name));        
            }    
        }

    }

}

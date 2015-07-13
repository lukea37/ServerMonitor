<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;

class ServerMonitor extends \Piwik\Plugin
{
    public function getListHooksRegistered()
    {
        return array(
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
        );
    }
    
    public function getJavaScriptFiles(&$files)
    {
        $files[] = "plugins/ServerMonitor/js/itsserver.js";
    }
    
    public function getStylesheetFiles(&$files)
    {
        $files[] = "plugins/ServerMonitor/css/its.css";
    }


}

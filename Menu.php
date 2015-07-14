<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;

use Piwik\Piwik;
use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuReporting;
use Piwik\Menu\MenuTop;
use Piwik\Menu\MenuUser;
use Piwik\Menu\Group;
use Piwik\Common;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureReportingMenu(MenuReporting $menu)
    {
        if (!Piwik::hasUserSuperUserAccess()) return;
        
         // Create custom Menu "Server"
         $menu->addItem('ServerMonitor_Server', '', $this->urlForAction('index'), $orderId = 30);
		 
         $servers = API::getInstance()->getServers();
         
         // Create menu dropdown for list of servers
         $group = new Group();
         
         foreach ($servers as $server) {
             $group->add($server, $this->urlForAction('index', array('server'=>$server)), 'Reports for server: '.$server);
         }
         
         $menu->addGroup('ServerMonitor_Server', 'ServerMonitor_ChooseServer', $group, $orderId = 10, $tooltip = false);
         
         $selectedServer = Common::getRequestVar('server', false);

         // Create Menu for list of categories
         $categories = API::getInstance()->getCategories();

         foreach ($categories as $category) {
            $menu->addItem('ServerMonitor_Server', ucwords(strtolower($category)), $this->urlForAction('index',array('category'=>$category)));
         }     

    }

}

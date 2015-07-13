<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;


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
        // or create a custom category 'UI Framework'
         $menu->addItem('Server', '', $this->urlForAction('index'), $orderId = 30);
         //$menu->addItem('Server', 'Overview', $this->urlForAction('overview'), $orderId = 0);
		 
         $servers = API::getInstance()->getServers();
         
         $group = new Group();
         
         foreach ($servers as $server) {
             $group->add($server, $this->urlForAction('index', array('server'=>$server)), 'Reports for server: '.$server);
         }
         
         $menu->addGroup('Server', 'Choose Server', $group, $orderId = 10, $tooltip = false);
         
         $selectedServer = Common::getRequestVar('server', false);

         $categories = API::getInstance()->getCategories();

         foreach ($categories as $category) {
            $menu->addItem('Server', ucwords(strtolower($category)), $this->urlForAction('index',array('category'=>$category)));
         }     

    }

    public function configureAdminMenu(MenuAdmin $menu)
    {
        // reuse an existing category
        // $menu->addSettingsItem('My Admin Item', $this->urlForDefaultAction(), $orderId = 30);
        // $menu->addPlatformItem('My Admin Item', $this->urlForDefaultAction(), $orderId = 30);

        // or create a custom category
        // $menu->addItem('General_Settings', 'My Admin Item', $this->urlForDefaultAction(), $orderId = 30);
    }

}

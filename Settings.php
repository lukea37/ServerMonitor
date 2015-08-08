<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;
use Piwik\Piwik;
use Piwik\Settings\SystemSetting;

/**
 * Defines Settings for ServerMonitor.
 */
class Settings extends \Piwik\Plugin\Settings
{

    /** @var SystemSetting */
    public $serverSettings;

    protected function init()
    {
        $this->setIntroduction(Piwik::translate('ServerMonitor_ServerSettingIntro'));

        $this->createServerSetting();
    }

    private function createServerSetting()
    {
        
        $this->serverSettings        = new SystemSetting('serverSettings', Piwik::translate('ServerMonitor_ServerSettingLabel') );
        $this->serverSettings->type  = static::TYPE_STRING;
        $this->serverSettings->uiControlType = static::CONTROL_TEXT;
        $this->serverSettings->description     = Piwik::translate('ServerMonitor_ServerSettingDescription');
        $this->serverSettings->inlineHelp      = Piwik::translate('ServerMonitor_ServerSettingHelp');
        $this->serverSettings->defaultValue    = '/var/lib/munin/';
        $this->serverSettings->validate = function ($value, $setting) {
            if (!file_exists($value.'datafile')) {
                throw new \Exception( Piwik::translate('ServerMonitor_FolderNotFound') );
            } 
            if (substr($value, -1) !== DIRECTORY_SEPARATOR) {
                throw new \Exception( Piwik::translate('ServerMonitor_FolderPathError') );
            }
        };

        $this->addSetting($this->serverSettings);
    }
}

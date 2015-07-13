<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;
use Exception;
use Piwik\Piwik;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Period\Range;
use Piwik\WidgetsList;
use Piwik\Common;
use Piwik\Site;
use Piwik\Plugins\ServerMonitor\libs\phpseclib\System\SSH;

/**
 * API for plugin ServerMonitor
 *
 * @method static \Piwik\Plugins\ServerMonitor\API getInstance()
 */

class API extends \Piwik\Plugin\API
{
    /**
     * @var $config
     * 
     *  Contains an array representing the Munin configuration in following format:
     * 
     *                  $config["domain1"] = array(
     *                               "server1" => array(
     *                                          "name1" => array(
     *                                                      "attribute1" => 'value1',
     *                                                      "attribute2" => 'value2'
     *                                                    ),
     *                                          "name2" => array(
     *                                                      "attribute1" => 'value1',
     *                                                      "attribute2" => 'value2'
     *                                                    )
     *                                          ),
     *                                          "name3" => array(
     *                                                      "attribute1" => 'value1',
     *                                                      "attribute2" => 'value2'
     *                                                    ),
     *                                          "name4" => array(
     *                                                      "attribute1" => 'value1',
     *                                                      "attribute2" => 'value2'
     *                                                    )
     *                                          )
     *                       );
     * 
     * The array is sorted ascending by server, category, name, attribute
     */
    private $config;
    private $settings;
    
    
    function __construct() 
    {
        $this->setSettings();
        $this->checkRequirements();
        $this->setConfig();
    }
    
    /**
     * Checks the requirements are met for ServerMonitor plugin to operate.
     *
     * @param int $idAlert
     *
     * @throws \Exception In case requirements are not met.
     *
     * @return bool
     */
    public function checkRequirements()
    {
        // Check PHP RRD functions exist
        if (!function_exists('rrd_fetch')) {
            throw new Exception( Piwik::translate('ServerMonitor_PhpRrdError') );
        }
        
        if (!file_exists($this->settings['dbdir'].'datafile')) {
            throw new Exception( Piwik::translate('ServerMonitor_DataFileNotFound') );
        }
        
        return true;
    }
    
    public function getSettings() {
        return $this->settings;
    }

    public function getConfig() {
        return $this->config;
    }
    
    private function setSettings()
    {
        // Get settings from Piwik admin page
        $settings = new Settings('ServerMonitor');
        $this->settings['dbdir'] = $settings->serverSettings->getValue();
    }
    
    private function setConfig()
    {
        $file = file($this->settings['dbdir'].'datafile');

        $config = array();
        
        // Pass over file to get an array of the Munin configuration
        foreach($file as $line) {
            
            // init variables
            $servers = array();
            $names = array();
            $attributes = array();
            
            // split using various delimiters
            $result0 = preg_match("/^([a-zA-Z_0-9.]+)\;/", $line, $domain);    
            $result1 = preg_match("/\;([a-zA-Z_0-9.]+)\:/", $line, $server);         
            $result2 = preg_match("/\:([a-zA-Z_0-9]+)/", $line, $name);
            $result3 = preg_match("/\:([A-Za-z_.0-9]+).*/", $line, $attribute);
            $result4 = preg_match("/ (.*)/", $line, $value);
            
            if ($result0 && $result1 && $result2 && $result3 && $result4) {
                $attr = str_replace($name[1].'.', '', $attribute[1]);
                
                if (!empty($config[$domain[1]]))                           $servers      = $config[$domain[1]];
                if (!empty($config[$domain[1]][$server[1]]))               $names      = $config[$domain[1]][$server[1]];
                if (!empty($config[$domain[1]][$server[1]][$name[1]]))     $attributes  = $config[$domain[1]][$server[1]][$name[1]];
                if (empty($attributes[$attr]))                              $attributes[$attr] = $value[1];
                
                ksort($attributes);
                
                $config[$domain[1]][$server[1]][$name[1]] = $attributes;
            }
            
        }

        // Perform sorting
        ksort($config);
        foreach ($config as $domain => $servers) {
            
            foreach ($servers as $server => $names) {
                ksort($names);
                $servers[$server] = $names;
            }
            
            ksort($servers);
            $config[$domain] = $servers;
        }

        $this->config = $config;
 
    }

    /**
     * Get domains monitored
     *
     * @return array
     */
    public function getDomains() {
        $domains = array();
        
        foreach ($this->config as $domain => $servers) {
            $domains[] = $domain;
        }
        
        return $domains;
    }

    /**
     * Get servers monitored
     *
     * @return array
     */
    public function getServers() {
        $return = array();
        
        foreach ($this->config as $domain => $servers) {
            foreach ($servers as $server => $names) {
                $return[] = $server;
            } 
        } 
        
        return $return;
    }
    
    /**
     * Get monitored categories eg. Bind, Disk, MySql, Network, System etc. 
     *
     * @param string $filterServer
     *
     * @return array
     */
    public function getCategories($filterServer = FALSE) {
        $categories = array();
        
        foreach ($this->config as $domain => $servers) {
            foreach ($servers as $server => $names) { 
                if ($filterServer == $server || $filterServer === FALSE) { 
                    foreach ($names as $name => $attributes) {
                        if (!empty($attributes['graph_category']) && !in_array($attributes['graph_category'], $categories))
                            $categories[] = $attributes['graph_category'];
                    }
                }            
            }
        } 
        
        sort($categories);

        return $categories;
    }
    
    /**
     * Get metrics monitored. 
     *
     * @param string $filterName
     * @param string $filterServer
     *
     * @return array
     */
    public function getMetrics($filterName, $filterServer = FALSE) {
        $metrics = array();
        
        foreach ($this->config as $domain => $servers) {
            foreach ($servers as $server => $names) {
                if ($filterServer == $server || $filterServer === FALSE) {
                    foreach ($names as $name => $attributes) {
                        if ($filterName == $name) {
                            foreach ($attributes as $attribute => $value) {
                                if ($this->endsWith($attribute, '.label'))
                                    $metrics[] = ($filterServer == FALSE) ? $server.' - '.$value : $value;
                            }   
                        }
                    }
                } 
            }
        }
        
        sort($metrics);

        return $metrics;
    }
    
    /**
     * Get RRD files relative to dbdir
     *
     * @param string $name
     * @param string $filterServer
     *
     * @return array
     */
    public function getFiles($name, $filterServer = FALSE) {
        $files = array();
        
        foreach ($this->config as $domain => $servers) {
            $dir = new \DirectoryIterator($this->settings['dbdir'].$domain.DIRECTORY_SEPARATOR);
    
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    
                    if (strpos($fileinfo->getFilename(), '-'.$name.'-')) {
                        if ($filterServer === FALSE || strpos($fileinfo->getFilename(), $filterServer.'-') !== FALSE) {
                            $files[] = $domain.DIRECTORY_SEPARATOR.$fileinfo->getFilename();
                        }
                    } 
                        
                }
            }   
        }

        sort($files);

        return $files;
    }
    
    public function getGraphEvolution($date, $period)
    {
        //TODO
        //$period = new Range($period, 'last30');
        //$period->setDefaultEndDate($date);
        
        $name = Common::getRequestVar('name', false);
        $serverFilter = Common::getRequestVar('server', false);
        $idSite = Common::getRequestVar('idSite', false);

        $files = $this->getFiles($name, $serverFilter);
        
        $website        = new Site($idSite);
        $timezone       = $website->getTimezone();
        
        $dates = explode(',',$date);

        if(Date::factory($dates[1])->isToday()) {
            $start = "-1d";
            $finish = "now";
        } else {
            $start = Date::factory($dates[1])->getTimestampUTC()-Date::factory($dates[1], 'UTC')->adjustForTimezone($dates[1],$timezone);
            $finish = "start+1d";
        }
        
        $data = array();

        /* 
        Build array like this from munin data
        
        $data = array(
            "2015-06-20 11:30:05"=>array("serverX - Metric 1"=>64,"serverY - Metric 1"=>123),
            "2015-06-20 11:35:05"=>array("serverX - Metric 2"=>79,"serverY - Metric 2"=>34),
            "2015-06-20 11:40:05"=>array("serverX - Metric 3"=>54,"serverY - Metric 3"=>73),
            "2015-06-20 11:45:05"=>array("serverX - Metric 4"=>64,"serverY - Metric 4"=>84),
            "2015-06-20 11:50:05"=>array("serverX - Metric 5"=>68,"serverY - Metric 5"=>99)
            ); */
        foreach ($files as $filename) {
            
            
            $parts0 = preg_match("/^([a-zA-Z_0-9.]+)\\".DIRECTORY_SEPARATOR."/", $filename, $domain);    
            $parts1 = preg_match("/\\".DIRECTORY_SEPARATOR."([a-zA-Z_0-9.]+)\-/", $filename, $server);         
            $parts3 = explode('-', $filename);

            if ($parts0 && $parts1) {
                
                $rrd_data = rrd_fetch($this->settings['dbdir'].$filename, array( "AVERAGE", "--resolution", "5", "--start", $start, "--end", $finish )); //TODO - change based on day/week/month period
                $rrd_values = array_values(($rrd_data["data"]));
    
                // Loop through array data
                foreach ( array_shift($rrd_values) as $timestamp => $value )
                { 
                    $contents = array();
                    if(!empty($this->config[$domain[1]][$server[1]][$name][$parts3[2].'.label'])) {
                        $metric = ($serverFilter == false) ? $server[1].' - '.$this->config[$domain[1]][$server[1]][$name][$parts3[2].'.label'] : $this->config[$domain[1]][$server[1]][$name][$parts3[2].'.label'];

                        // Each server is stored in different file so append results to array if necessary
                        if (!empty($data[Date::factory($timestamp, $timezone)->getDatetime()]) && is_array($data[Date::factory($timestamp, $timezone)->getDatetime()])) {
                            $contents = $data[Date::factory($timestamp, $timezone)->getDatetime()];
                            $contents[$metric] = round($value,2);
                        } else {
                            $contents[$metric] = round($value,2);  
                        }
                        
                        $data[Date::factory($timestamp, $timezone)->getDatetime()] = $contents;
                         
                    }
    
                }
            }

        }

        return DataTable::makeFromIndexedArray($data);
    }

    public function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }
    
    public function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }
    
}

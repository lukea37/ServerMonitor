<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ServerMonitor;
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
     * @var $plugins
     * 
     *  Contains an array representing the Munin configuration in following format:
     * 
     *                       $plugins["server1"] = array(
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
    private $settings;
    private $plugins;
    
    function __construct() 
    {
        $this->setSettings();
        $this->setPlugins();
    }
    
    public function get($idSite, $period, $date, $segment = false, $idGoal = false, $columns = array())
    {
        //Piwik::checkUserHasViewAccess($idSite);

        /** @var DataTable|DataTable\Map $table */
        $table = null;
          ini_set('error_reporting', E_ALL);
          ini_set('display_errors', true); 
        $name = Common::getRequestVar('name', false);
        $server = Common::getRequestVar('server', false);
        
        $_GET['name'] = 'processes';
        //return $this->getGraphEvolution(','.$date, $period);
          //var_dump($date); die();
        /*$segments = array(
            '' => false,
            '_new_visit' => 'visitorType%3D%3Dnew', // visitorType==new
            '_returning_visit' => VisitFrequencyAPI::RETURNING_VISITOR_SEGMENT
        );

        foreach ($segments as $appendToMetricName => $predefinedSegment) {
            $segmentToUse = $this->appendSegment($predefinedSegment, $segment);

            // @var DataTable|DataTable\Map $tableSegmented 
            $tableSegmented = Request::processRequest('Goals.getMetrics', array(
                'segment' => $segmentToUse,
                'idSite'  => $idSite,
                'period'  => $period,
                'date'    => $date,
                'idGoal'  => $idGoal,
                'columns' => $columns,
                'serialize' => '0'
            ));

            $tableSegmented->filter('Piwik\Plugins\Goals\DataTable\Filter\AppendNameToColumnNames',
                                    array($appendToMetricName));

            if (!isset($table)) {
                $table = $tableSegmented;
            } else {
                $merger = new MergeDataTables();
                $merger->mergeDataTables($table, $tableSegmented);
            }
        }*/

        //return $table;
    }
    
    public function getSettings() {
        return $this->settings;
    }
    
    public function getPlugins() {
        return $this->plugins;
    }
    
    public function setSettings()
    {
        // TODO - get settings from Piwik admin page, support remote file fetching
        $this->settings['datafile'] = '/var/lib/munin/datafile';
        $this->settings['rrd_dir'] = '/var/lib/munin/invisionit.com.au/';
    }
    
    public function setPlugins()
    {
        $file = file($this->settings['datafile']);

        $plugins = array();
        
        // Pass over file to get an array of the Munin plugins
        foreach($file as $line) {
            
            // init variables
            $names = array();
            $attributes = array();
            
            // split using various delimiters   
            $result1 = preg_match("/\;([a-zA-Z_0-9.]+)\:/", $line, $server);         
            $result2 = preg_match("/\:([a-zA-Z_0-9]+)/", $line, $name);
            $result3 = preg_match("/\:([A-Za-z_.0-9]+).*/", $line, $attribute);
            $result4 = preg_match("/ (.*)/", $line, $value);
            
            if ($result1 && $result2 && $result3 && $result4) {
                $attr = str_replace($name[1].'.', '', $attribute[1]);
                
                if (!empty($plugins[$server[1]]))               $names      = $plugins[$server[1]];
                if (!empty($plugins[$server[1]][$name[1]]))     $attributes  = $plugins[$server[1]][$name[1]];
                if (empty($attributes[$attr]))                  $attributes[$attr] = $value[1];
                
                ksort($attributes);
                
                $plugins[$server[1]][$name[1]] = $attributes;
                
            }
            
        }

        // Perform sorting
        ksort($plugins);
        foreach ($plugins as $server => $names) {
            ksort($names);
            $plugins[$server] = $names;
        }

        $this->plugins = $plugins;
 
    }

    public function getServers() {
        $servers = array();
        
        foreach ($this->plugins as $server => $names) {
            $servers[] = $server;
        } 
        
        sort($servers);
        
        return $servers;
    }

    public function getCategories($filterServer = FALSE) {
        $categories = array();
        
        foreach ($this->plugins as $server => $names) { 
            if ($filterServer == $server || $filterServer === FALSE) { 
                foreach ($names as $name => $attributes) {
                    if (!empty($attributes['graph_category']) && !in_array($attributes['graph_category'], $categories))
                        $categories[] = $attributes['graph_category'];
                }
            }            
        }
        
        sort($categories);

        return $categories;
    }
    
    public function getMetrics($filterName, $filterServer = FALSE) {
        $metrics = array();
        
        foreach ($this->plugins as $server => $names) {
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
        
        sort($metrics);

        return $metrics;
    }
    
    public function getFiles($name, $filterServer = FALSE) {
        $files = array();
        $dir = new \DirectoryIterator($this->settings['rrd_dir']);

        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                
                if (strpos($fileinfo->getFilename(), '-'.$name.'-')) {
                    if ($filterServer === FALSE || strpos($fileinfo->getFilename(), $filterServer.'-') !== FALSE) {
                        $files[] = $fileinfo->getFilename();
                    }
                } 
                    
            }
        }

        sort($files);

        return $files;
    }
    
    public function getGraphEvolution($date, $period)
    {
        
        //$period = new Range($period, 'last30');
        //$period->setDefaultEndDate($date);
        
        $name = Common::getRequestVar('name', false);
        $server = Common::getRequestVar('server', false);
        $idSite = Common::getRequestVar('idSite', false);

        $files = $this->getFiles($name, $server);
        
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
            "2015-06-20 11:30:05"=>array("server1.invisionit.com.au - Metric 1"=>64,"server2.invisionit.com.au - Metric 1"=>123),
            "2015-06-20 11:35:05"=>array("server1.invisionit.com.au"=>79,"server2.invisionit.com.au"=>34),
            "2015-06-20 11:40:05"=>array("server1.invisionit.com.au"=>54,"server2.invisionit.com.au"=>73),
            "2015-06-20 11:45:05"=>array("server1.invisionit.com.au"=>64,"server2.invisionit.com.au"=>84),
            "2015-06-20 11:50:05"=>array("server1.invisionit.com.au"=>68,"server2.invisionit.com.au"=>99)
            ); */
        foreach ($files as $filename) {
            $parts = explode('-', $filename);
            $serverArr = explode('/', $parts[0]);
            $rrd_data = rrd_fetch($this->settings['rrd_dir'].$filename, array( "AVERAGE", "--resolution", "5", "--start", $start, "--end", $finish )); //TODO , "--start", "-3d", "--end", "start+1h"
            $rrd_values = array_values(($rrd_data["data"]));

            // Loop through array data
            foreach ( array_shift($rrd_values) as $timestamp => $value )
            { 
                $contents = array();
                if(!empty($this->plugins[$serverArr[0]][$name][$parts[2].'.label'])) {
                    $metric = ($server == false) ? $serverArr[0].' - '.$this->plugins[$serverArr[0]][$name][$parts[2].'.label'] : $this->plugins[$serverArr[0]][$name][$parts[2].'.label'];
    
                    // Each server is stored in different file so append results to array if necessary
                    //if (!empty($data[date('Y-m-d H:i:s',$timestamp)]) && is_array($data[date('Y-m-d H:i:s',$timestamp)])) {
                    if (!empty($data[Date::factory($timestamp, $timezone)->getDatetime()]) && is_array($data[Date::factory($timestamp, $timezone)->getDatetime()])) {
                        $contents = $data[Date::factory($timestamp, $timezone)->getDatetime()];
                        $contents[$metric] = round($value,2);
                    } else {
                        $contents[$metric] = round($value,2);  
                    }
                    
                    //$data[date('Y-m-d H:i:s',$timestamp)] = $contents;
                    $data[Date::factory($timestamp, $timezone)->getDatetime()] = $contents;
                     
                }

            }
            
        }
//var_dump($data);die();

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

    
    /* Piwik did not expose this method via API, so created custom API to perform this to allow Wordpress dashboard access to all widgets */
    public function getAllWidgets()
    {
        return WidgetsList::get();
    }
    
	/*
    public function getSoftwareHistory($idSite, $period, $date, $segment = false)
    {
        //TODO apply segments/filter
        $data = array();
        $fh = fopen('/var/log/apt/history.log','r');
        while ($line = fgets($fh)) {
          
          if(strpos($line,'Start-Date: ') !== FALSE) {
            $entry = array();
            $entry['date'] = trim(str_replace('  ', ' ', str_replace('Start-Date: ', '', $line)));    
          } elseif(strpos($line,'Commandline: ') !== FALSE) {
            $entry['label'] = trim(str_replace('apt-get -y install ','',str_replace('apt-get install ', '', str_replace('Commandline: ', '', $line))));    
          } elseif(strpos($line,'Upgrade: ') !== FALSE) {
            $entry['type'] = 'Upgrade';
            */
           // preg_match("/.*\((.*)\).*/", $line, $match);
           // $versions = explode(',', $match[1]);
            
            //$entry['version_old'] = trim($versions[0]);
            //$entry['version_new'] = trim($versions[1]);
               
          //} elseif(strpos($line,'Install: ') !== FALSE) {
          //  $entry['type'] = 'Install'; 
          //  preg_match("/.*\((.*)\).*/", $line, $match);
          /*  $entry['version_new'] = trim($match[1]);
            
          } elseif(strpos($line,'End-Date: ') !== FALSE) {
              $data[] = $entry;
              $entry = array();
          }
              
        }
        fclose($fh);
        
        $table = DataTable::makeFromSimpleArray($data);

        return $table;
    } 

    public function getDNSServer($idSite, $period, $date, $segment = false) {
        //TODO apply segments/filter
        $data = array();
        $fh = fopen('/var/log/bind9/query.log','r');
        while ($line = fgets($fh)) {

          $parts = explode(" ", $line);

          $entry['label'] = $parts[0].' '.$parts[1];
          $entry['client'] = trim($parts[3],':');
          $entry['domain'] = $parts[5];
          $entry['type'] = $parts[7];
          $entry['response'] = $parts[8];

          $data[] = $entry;
        }
        fclose($fh);
        
        $table = DataTable::makeFromSimpleArray($data);

        return $table;
    }
    
    public function getMailServer($idSite, $period, $date, $segment = false) {
        //TODO apply segments/filter
        $data = array();
        $fh = fopen('/var/log/mail.info','r');
        
        // Get mail sent/received/bounced
        while ($line = fgets($fh)) {

          $parts = explode(" ", $line);

          $entry['label'] = $parts[0].' '.$parts[1];
          $entry['client'] = trim($parts[3],':');
          $entry['domain'] = $parts[5];
          $entry['type'] = $parts[7];
          $entry['response'] = $parts[8];

          $data[] = $entry;
        }
        fclose($fh);
        
        $table = DataTable::makeFromSimpleArray($data);

        return $table;
    } */
}

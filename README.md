# Piwik ServerMonitor Plugin

## Description

Use [Piwik](http://piwik.org/) as your analytics platform for statistics collected by [Munin server monitoring](http://munin-monitoring.org/) tool. 

This is a [Piwik](http://piwik.org/) plugin that reads the raw RRD files from your Munin master server. 

This enhances the Munin functionality by providing all the great benefits of Piwik, such as
 
* Real time data
* Customizable Dashboards
* Enhanced Graphing
* Access Munin data using Piwik API
* TODO: Access Munin data using Piwik Mobile
* TODO: Scheduled reports 
* TODO: Custom alerts for Server Monitoring - using [Piwik CustomAlerts plugin](https://github.com/piwik/plugin-CustomAlerts) 

## Screenshots

The following screenshots show how Munin is integrated with Piwik Analytics platform.

__Piwik Menu__
The Piwik Menu is automatically populated based on the Munin configuration.

![Piwik Menu](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/menu.png)

You can filter results based on server.
![Server Filter](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/serverfilter.png)

__Piwik Graphs__
![Bind monitoring](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/bind.png)
![Ngnix monitoring](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/nginx.png)
![CPU monitoring](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/cpu.png)
![Memory monitoring](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/memory.png)

__Piwik Dashboard Widgets__
![Custom Dashboard](https://raw.githubusercontent.com/Invision-Technology-Soultions/ServerMonitor/master/screenshots/dashboard.png)

## Requirements

* Requires [Piwik](http://piwik.org/) (Tested >=2.13) and [Munin server monitoring](http://guide.munin-monitoring.org/en/latest/master/) (Tested 1.4.6) installed on same server.
* [PHP RRD Functions](http://php.net/manual/en/book.rrd.php)
* Piwik requires read-only access to Munin datafile and RRD files located in: /var/lib/munin/ 

## Installation

* Ensure you meet the above requirements
* Install via Piwik Marketplace

## Roadmap

* Support Piwik Periods: Week, Month, Year
* Integration with Piwik Scheduled reports
* Integration with Piwik Custom Alerts
* Integration with Piwik Mobile App
* Support data collection from remote Munin master
* Store Munin RRD data in Piwik database for historical statistics 

## Changelog

* 0.1.0 - First beta

## License

GPL v3 or later

## Support

If you require proffesional services, please contact us at [support@invisionit.com.au](mailto:support@invisionit.com.au)
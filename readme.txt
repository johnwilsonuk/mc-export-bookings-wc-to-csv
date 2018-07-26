# MC Export Bookings WC to CSV

* Contributors: MarieComet
* Website link: https://mariecomet.fr/
* Tags: woocommerce
* Requires at least: 4.7
* Tested up to: 4.9.7
* Stable tag: 1.0.3
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export WooCommerce Bookings to CSV

## Description

MC Export Bookings WC to CSV provides user ability to Export WooCommerce Bookings to CSV.
After plugin installed and activated, you will see a sub menu page in WooCommerce menu called "Export bookings".
Here you can select the product for which to export reservations.
Click on "Export", be patient if you have a lot of bookings.

The plugin is still in its infant age and developers are welcome to extend.

## Installation

This section describes how to install the plugin and get it working.

* Download the zip archive and upload it to your WordPress site.
* Activate the plugin through the 'Plugins' menu in WordPress
* Refer to plugin description in regards to setting up how the plugins works

## Screenshots

* Step 1 : Select bookable product
![Select bookable product](https://github.com/MarieComet/mc-export-bookings-wc-to-csv/blob/master/screenshots/screenshot-1.png "Select bookable product")

* Step 2 : Start export process
![Start export process](https://github.com/MarieComet/mc-export-bookings-wc-to-csv/blob/master/screenshots/screenshot-2.png "Start export process")

* Step 3 : Download your .CSV file
![Download your .CSV file](https://github.com/MarieComet/mc-export-bookings-wc-to-csv/blob/master/screenshots/screenshot-3.png "Download your .CSV file")

## Changelog

### 1.0
* Initial Release.

### 23/02/2018
* Update the main function `generate_csv` :
* Use WC_Booking_Data_Store to get bookings id from order instead of previously 'Booking ID' meta value which was not a stable value.
* Display only bookable product in the products select.

### 23/02/2018 1.0.1
* Totally change the way to query bookings :
- Instead of looping on orders, query directly bookings for selected product ID.

### 26/02/2018 1.0.2
* Implement ajax booking search on product select
* Implement saving export file in custom uploads folder
* Implement list saved export files in plugin admin screen

### 26/07/2018 1.0.3
* Add start and end dates to filters bookings before export
* Add `mc_wcb_csv_delimiter` filter to allow third party change the CSV delimiter
<?php
/*
Plugin Name: hitwp.com NB Downloader
Description: A WordPress plugin that fetches JSON, saves the products into the database, and download later once per day using Cron.
Version: 1.0.0
Author: hitwp.com
*/

// global variables
$is_debug = true;
$is_scan = true;
$is_download = true;
$keywords2 = array(
	'2checkout',
	'a', 'australia', 'abstract', 'acf', 'access', 'ad', 'addon', 'addons', 'admin', 'advanced', 'advanced custom fields', 'affiliate', 'affiliatewp', 'all', 'amp', 'api', 'aws', 'anali', 'appthemes', 'array themes', 'astra', 'automatewoo', 'aweber', 'account', 'amazon', 'anti', 'ajax', 'auth', 'authorize',
	'b', 'builder', 'barn2media', 'bbpress', 'beaver', 'blog', 'buddy', 'buddyboss', 'bulk', 'book', 'backend', 'bundle', 'bill', 'billing', 'braintree',
	'c', 'canada', 'cache', 'card', 'cart', 'catalog', 'checkout', 'customer', 'credit', 'column', 'content', 'commision', 'city', 'cobalt', 'comment', 'constant', 'contact', 'css', 'cssigniter', 'create', 'csv', 'cyberchimps', 'campaign', 'crm', 'conditional', 'compose', 'custom', 'customize', 'course', 'calendar', 'crypto', 'currency',
	'd', 'dashboard', 'direct', 'detail', 'dessign', 'directory', 'dokan', 'download', 'duplicate', 'divi', 'debug', 'dropbox', 'drip', 'db', 'database', 'discount',
	'e', 'elementor', 'elementorism', 'elegant', 'email', 'easy', 'easy digital download', 'edd', 'elmastudio', 'envira', 'enhance', 'event', 'export', 'extension', 'end', 'erp',
	'f', 'facebook', 'facetwp', 'famethemes', 'feed', 'fedex', 'form', 'field', 'force', 'find', 'flow', 'file', 'filter', 'frontend', 'fraud',
	'g', 'give', 'gravity', 'gateway', 'google', 'ground', 'groundhogg', 'gallery', 'geodirectory', 'graph', 'gutenberg', 'gravityview', 'geo',
	'h', 'happythemes', 'history', 'hubspot',
	'i', 'info', 'import', 'ithemes', 'instagram',
	'j', 'js', 'java', 'json', 'job',
	'k', 'klarna',
	'l', 'learndash', 'lms', 'landing', 'link', 'leader', 'lifetime', 'layout', 'legal', 'limit', 'library', 'login',
	'm', 'map', 'mail', 'mailchimp', 'manage', 'maxmind', 'member', 'monitor', 'message', 'meter', 'media', 'mainwp',
	'n', 'ninja', 'newsletter', 'nobuna',
	'o', 'order', 'ocean', 'oceanwp', 'own', 'oboxtheme', 'optimize', 'oxygen',
	'p', 'pack', 'page', 'pay', 'payment', 'paypal', 'purchase', 'product', 'plugin', 'perk', 'premium', 'pro', 'plugin', 'pending', 'plus', 'post', 'password', 'pdf',  'pos', 'profile', 'polylang', 'player', 'project',
	'q',
	'r', 'referral', 'rate', 'rest', 'report', 'regis', 'registration', 'recur', 'review', 'restrict', 'royal', 'role', 'rock',
	's', 'sale', 's3', 'seo', 'search', 'searchwp', 'sensei', 'soflyy', 'soliloquy', 'sign', 'store', 'slug', 'stripe', 'shop', 'show', 'signup', 'social', 'sell', 'slack', 'studio', 'studiopress', 'swatch', 'ship', 'shipping', 'subscri', 'sms',
	't', 'theme', 'thrive', 'toolset', 'type', 'tabs', 'track', 'twitter', 'tool', 'the events calendar', 'themify', 'table', 'twilio',
	'u', 'user', 'ultimate', 'uncanny', 'updraft', 'ups', 'usps',
	'v', 'variation', 'vat', 'view', 'visual', 'visualmodo', 'vendor', 'video',
	'w', 'wc', 'wp', 'woocommerce', 'wordpress', 'wp erp', 'wpmu', 'wpmu dev', 'woo', 'widget', 'wish', 'wishlist', 'wallet',
	'x',
	'y', 'yith', 'yith woocommerce',
	'z', 'zapier', 'zigzagpress',
);
$table_name = $wpdb->prefix . 'wphit_nb_dl';
$d  = '';
$hl = 'en_US';
$v  = '2.6';
$ck = '';
$cs = '';
$url_search = '?hl='.$hl.'&v='.$v.'&d='.$d.'&ck='.$ck.'&cs='.$cs;
$url_download = '?hl='.$hl.'&v='.$v.'&d='.$d.'&ck='.$ck.'&cs='.$cs;

$keywords = array();
for ($i = 0; $i <= 9; $i++) {
	$keywords[] = $i;
}

foreach($keywords2 as $keyword2) {
	$keywords[] = $keyword2;
}

// Hook for activating the cron job when the plugin is activated
register_activation_hook(__FILE__, 'nb_downloader_activate');
function nb_downloader_activate() {
    // Schedule the cron event if not already scheduled
    if (!wp_next_scheduled('nb_downloader_event')) {
        wp_schedule_event(time(), 'daily', 'nb_downloader_event');
    }
	
    // Schedule the 7-day cron event if not already scheduled
    if (!wp_next_scheduled('nb_downloader_scan_event')) {
        wp_schedule_event(time(), 'weekly', 'nb_downloader_scan_event');
    }	
}

// Hook for deactivating the cron job when the plugin is deactivated
register_deactivation_hook(__FILE__, 'nb_downloader_deactivate');
function nb_downloader_deactivate() {
    // Clear the scheduled cron event
    wp_clear_scheduled_hook('nb_downloader_event');
	wp_clear_scheduled_hook('nb_downloader_scan_event');
}

// Hook for the actual function to run daily for download
add_action('nb_downloader_event', 'nb_downloader_function');
function nb_downloader_function() {
	set_time_limit(600);
	global $wpdb, $is_scan, $is_debug, $is_download, $keywords, $table_name, $d, $hl, $v, $ck, $cs, $url_search, $url_download;
	if($is_download) {
		// get from DB where downloaded=0
		// $wpdb->prepare("SELECT * FROM $table_name WHERE downloaded = %d ", 0)
		$download_pending_rows = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM $table_name WHERE downloaded = %d ORDER BY file_size desc LIMIT 3 ", 0)
		);
		if (!empty($download_pending_rows)) {
			foreach ($download_pending_rows as $row) {
				// verify local file first
				$pid = $row->product_id;
				//error_log('Working on PID '.$pid);
				$upload_dir = wp_upload_dir();
				$uploads_directory_path = $upload_dir['basedir'];
				//$dir_dl = $plugin_directory_path.'nb_dowloads/'.$pid;
				$dir_dl = $uploads_directory_path.'/nb_dowloads/'.$pid;
				//$is_debug && error_log('Checking on dir '.$dir_dl);
				if(is_dir($dir_dl)) {
					// Update downloaded status
					$wpdb->update(
						$table_name,
						array('downloaded' => 1),
						array('product_id' => $pid),
						array('%d'),
						array('%d')
					);
					$is_debug && error_log('Error, directory already exists for product ' . $pid);
				} else {
					// Download the file associated with the row and update the status
					$ret_download = file_get_contents($url_download.'&pid='.$pid);
					$json_decode_download = json_decode($ret_download, true);
					if (is_array($json_decode_download)) {
						$is_debug && error_log('Error, return array for product ' . $pid);
						$is_debug && error_log('Array contents: ' . print_r($json_decode_download, true));
					} else {
						// only mkdir when needed
						mkdir($dir_dl);
						if (file_put_contents($dir_dl . '/' . $row->file_name, $ret_download)) {
							// Update downloaded status
							$wpdb->update(
								$table_name,
								array('downloaded' => 1),
								array('product_id' => $pid),
								array('%d'),
								array('%d')
							);
							$is_debug && error_log("File downloaded and status updated for product " . $pid);
						} else {
							$is_debug && error_log("File downloading failed for product " . $pid);
						}
					}
					sleep(2);
				}
			}
		} else {
			$is_debug && error_log('No pending downloads.');
		}		
	}
}

// Hook for the weekly cron event
add_action('nb_downloader_scan_event', 'nb_downloader_scanner_function');
function nb_downloader_scanner_function() {
	set_time_limit(0);
	global $wpdb, $is_scan, $is_debug, $is_download, $keywords, $table_name, $d, $hl, $v, $ck, $cs, $url_search, $url_download;
    // Your code to be executed every 7 days goes here
	if($is_scan) {
		foreach($keywords as $keyword) {
			$url_search_final = $url_search.'&n='.$keyword;
			$data = json_decode(file_get_contents($url_search_final), true);
			if ($data) {
				foreach($data['products'] as $product) {
					$pid = $product['product_id'];
					// Check if product with the given product_id already exists
					$existing_product = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %s", $product['product_id'])
					);
					
					if (!$existing_product) {
						$wpdb->insert(
							$table_name,
							array(
								'product_id' => $product['product_id'],
								'product_name' => $product['product_name'],
								'last_version' => $product['last_version'],
								'file_name' => $product['file_name'],
								'file_size' => $product['file_size'],
								'downloaded' => '0',
							)
						);
						$is_debug && error_log('Product inserted: ' . $product['product_id']);
					} else {
						$is_debug && error_log('Product already exists: ' . $product['product_id']);
					}
				}
			} else {
				$is_debug && error_log('Failed to fetch JSON data or data is invalid.');
			}
			sleep(1);
		}
	}
}

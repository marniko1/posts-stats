<?php

/**
 * Plugin Name: POSTS STATS
 * Plugin URI:  
 * Description: Basic WordPress Plugin Header Comment
 * Version:     1.0.0
 * Author:      Marko
 * Author URI:  
 * License URI: 
 * Text Domain: wporg
 * Domain Path: /languages
 * License:     GPL2


	CRTA POSTS STATS is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.
	 
	CRTA POSTS STATS is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with CRTA POSTS STATS. If not, see {URI to Plugin License}.
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include "settings.php";


class PostsStats {
	

	public function __construct() {
		
		add_action('rest_api_init', array($this, 'cps_init_rest_api'));
	}

	public function cps_init_rest_api () {

		if (isset($_GET['callback'])) {
			$callback = $_GET['callback'];
			register_rest_route( 'posts-stats/v2', '/all_posts', array(
			    'methods' => WP_REST_Server::READABLE,
			    'callback' => array( $this, $callback),
			    'args'
			));
		}
	}

	public function cps_get_posts_stats ($request) {

		global $wpdb;

		$parameters = $request->get_query_params();
		$cats = $parameters['cats'];
		$start_date = $parameters['start_date'];
		$end_date = $parameters['end_date'];
		$hash_key = $parameters['hash_key'];

		if (!$this->cps_check_hash_key($hash_key)) {
			return '[{ permission: "denied"}]';
		}




		$sql = "SELECT count(*) as num, YEAR(wpp.post_date) as post_year, MONTHNAME(wpp.post_date) AS post_month, DATE_FORMAT(wpp.post_date, '%d-%m-%Y') as full_date, group_concat(wpp.post_title separator '----') as post_title, group_concat(wpp.guid separator '----') as post_url, group_concat(wpp.post_name separator '----') as post_name, wpt.slug as term_slug 




		 FROM $wpdb->posts as wpp 

		 left join $wpdb->term_relationships  as wptr 
		 on wptr.object_id = wpp.ID 

		 left join $wpdb->terms as wpt 
		 on wptr.term_taxonomy_id = wpt.term_id 


		 where post_status='publish' and post_type='post' and wpt.slug in ($cats) 

		 and post_date between '$start_date' and '$end_date' 

		 GROUP BY post_year,post_month, wpt.slug 

		 order by wpp.post_date";




		
		$posts = $wpdb->get_results($sql);
		return $posts;
	}

	public function cps_check_hash_key ($hash_key) {

		
		$key = 'secret-key';

		if ($hash_key == md5(sha1(md5($key)))) {
			return true;
		}
		return false;
	}
}

new PostsStats();
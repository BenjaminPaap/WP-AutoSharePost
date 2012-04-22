<?php
/*
Plugin Name: Wordpress-AutoSharePost
Plugin URI: http://www.checkdomain.de/wordpress/autosharepost
Description: Automatically posts published posts on social platforms with a predefined text per post
Version: 0.1.1
Author: Benjamin Paap
Author URI: http://www.benjaminpaap.de
License:
*/

define('WP_AUTOSHAREPOST_DIR', plugin_dir_path(__FILE__));
define('WP_AUTOSHAREPOST_URL', plugin_dir_url(__FILE__));
define('WP_AUTOSHAREPOST_DOMAIN', 'wordpress-autosharepost');

// Check if this request is from the admin area
if (is_admin() || defined('DOING_CRON')) {
    require_once WP_AUTOSHAREPOST_DIR . '/admin.php';
}

class WordpressAutoSharePost
{
    
    public function __construct()
    {
        $this->init();
    }
    
    public function init()
    {
        
    }
    
}

$wasp = new WordpressAutoSharePost();
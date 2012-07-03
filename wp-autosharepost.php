<?php
/*
Plugin Name: WP-AutoSharePost
Plugin URI: http://www.wordpress.org/extend/plugins/wordpress-autosharepost/
Description: Automatically posts published posts on social platforms with a predefined text per post
Version: 0.2.5
Author: Benjamin Paap
Author URI: http://blog.checkdomain.de/author/benjaminpaap/
License:
*/

define('WP_AUTOSHAREPOST_DIR', plugin_dir_path(__FILE__));
define('WP_AUTOSHAREPOST_URL', plugin_dir_url(__FILE__));
define('WP_AUTOSHAREPOST_DOMAIN', 'wordpress-autosharepost');
// define('WP_AUTOSHAREPOST_DEBUG', TRUE);

// Check if this request is from the admin area
require_once WP_AUTOSHAREPOST_DIR . '/admin.php';

class WordpressAutoSharePost
{
	
    public function __construct()
    {
        $this->init();
    }
    
    /**
     * Init the plugin
     */
    public function init()
    {
    	load_plugin_textdomain(WP_AUTOSHAREPOST_DOMAIN, false, basename(dirname(__FILE__)) . '/languages' );
		
    	register_activation_hook(__FILE__, array(&$this, 'activatePlugin'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivatePlugin'));
		
		add_filter('get_avatar_comment_types', array(&$this, 'filterAvatarCommentTypes'));
		add_filter('get_avatar', array(&$this, 'filterGetAvatar'), 10, 5);
		add_filter('comment_class', array(&$this, 'filterCommentClass'), 10, 4);
		
    	add_filter('cron_schedules', array(&$this, 'filterSchedulePluginInterval'));
    }

    /**
     * Filter for comment class
     *
     * @param array $classes
     * @param string $class
     * @param int $comment_id
     * @param int $post_id
     */
    public function filterCommentClass($classes, $class, $comment_id, $post_id)
    {
    	$comment = get_comment($comment_id);
    	
    	if ($comment->comment_type == 'facebook') {
    		array_unshift($classes, 'comment');
    	}
    	return $classes;
    }
    
    /**
     * Add a new allowed comment type
     *
     * This method adds a new comment type to the list of allowed comment types
     * for which avatars would be displayed
     *
     * @param array $types
     * @return array
     */
    public function filterAvatarCommentTypes($types)
    {
    	array_push($types, 'facebook');
    	
    	return $types;
    }
    
    /**
     * Filter for get_avatar
     *
     * @param string $avatar
     * @param object $id_or_email
     * @param int $size
     * @param string $default
     * @param string $alt
     */
    public function filterGetAvatar($avatar, $id_or_email, $size, $default, $alt = NULL)
    {
    	if (is_object($id_or_email)) {
    		if ($id_or_email->comment_type == 'facebook') {
    			$fb_user = get_comment_meta($id_or_email->comment_ID,
    										WordpressAutoSharePostAdmin::META_COMMENT_FACEBOOK_USER,
    										TRUE);
    			
    			if (!empty($fb_user)) {
    				$avatar = '<img class="avatar avatar-' . $size . ' photo avatar-default" src="http://graph.facebook.com/' . $fb_user . '/picture?type=square" alt="' . $alt . '" height="' . $size . '" width="' . $size . '" />';
    			}
    		}
    	}
    	
    	return $avatar;
    }
    
    /**
     * Add a new recurrence
     *
     * This method adds a new recurrence method for cronjobs
     *
     * @param array $schedules
     * @return array
     */
	public function filterSchedulePluginInterval($schedules)
	{
		$schedules['wp-autosharepost-interval'] = array(
    		'interval' => intval(get_option(WordpressAutoSharePostAdmin::OPTION_COMMENTGRABBER_INTERVAL, WordpressAutoSharePostAdmin::DEFAULT_COMMENTGRABBER_INTERVAL)) * 60,
    		'display' => __('WP-AutoSharePost Interval')
    	);
		
    	return $schedules;
    }
    
    /**
     * Activation of the plugin
     *
     * This methods installs the plugin on activation. We need a cronjob for
     * reading the facebook comments.
     */
    public function activatePlugin()
    {
    	if (!wp_next_scheduled('wp_autosharepost_comment_grabber')) {
    		wp_schedule_event(time(), 'wp-autosharepost-interval', 'wp_autosharepost_comment_grabber');
    	}
    	
    	$defaults = array(
    		WordpressAutoSharePostAdmin::OPTION_FACEBOOK_POSTINGTYPE => 'link',
    		WordpressAutoSharePostAdmin::OPTION_FACEBOOK_DESCRIPTION => 40,
    	);
    	
    	foreach ($defaults as $key => $default) {
    		update_option($key, get_option($key, $default));
    	}
    }
    
    /**
     * Deactivation of the plugin
     *
     * Just delete the cronjob we installed on activation
     */
    public function deactivatePlugin()
    {
    	if (wp_next_scheduled('wp_autosharepost_comment_grabber')) {
    		wp_clear_scheduled_hook('wp_autosharepost_comment_grabber');
    	}
    }
    
}

$wasp = new WordpressAutoSharePost();
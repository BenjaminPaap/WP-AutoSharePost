<?php

require_once(WP_AUTOSHAREPOST_DIR . '/lib/template.class.php');
require_once(WP_AUTOSHAREPOST_DIR . '/lib/cd-wordpress-base.php');

/**
 * WordpressAutoSharePostAdmin Class
 *
 * This class handles all admin stuff for the AutoSharePost plugin
 *
 * @author Benjamin
 */
class WordpressAutoSharePostAdmin extends CheckdomainWordpressBase
{

	const PLUGIN_STYLE_NAME_MAIN		  = 'wp-autosharepost-style-main';
	
    const OPTION_AUTOENABLED              = 'wp-autosharepost-autoenabled';
    
    const OPTION_FACEBOOK_APPID           = 'wp-autosharepost-fb-appid';
    const OPTION_FACEBOOK_APPSECRET       = 'wp-autosharepost-fb-appsecret';
    const OPTION_FACEBOOK_PAGEID          = 'wp-autosharepost-fb-pageid';
    const OPTION_FACEBOOK_APPNAME         = 'wp-autosharepost-fb-appname';
    const OPTION_FACEBOOK_TOKEN           = 'wp-autosharepost-fb-token';
    const OPTION_FACEBOOK_POSTINGTYPE	  = 'wp-autosharepost-fb-postingtype';
    const OPTION_FACEBOOK_DESCRIPTION	  = 'wp-autosharepost-fb-description';
    const OPTION_FACEBOOK_DISABLE_BITLY   = 'wp-autosharepost-fb-disablebitly';
    
    const OPTION_TWITTER_CONSUMER_KEY     = 'wp-autosharepost-twitter-consumer-key';
    const OPTION_TWITTER_CONSUMER_SECRET  = 'wp-autosharepost-twitter-consumer-secret';
    const OPTION_TWITTER_OAUTH_TOKEN      = 'wp-autosharepost-twitter-oauth-token';
    const OPTION_TWITTER_OAUTH_SECRET     = 'wp-autosharepost-twitter-oauth-secret';
    const OPTION_TWITTER_URL_SEPERATOR    = 'wp-autosharepost-twitter-oauth-url-seperator';
    
    const OPTION_BITLY_APIKEY             = 'wp-autosharepost-bitly-apikey';
    const OPTION_BITLY_LOGIN              = 'wp-autosharepost-bitly-login';
    
	const OPTION_COMMENTGRABBER_ENABLED   = 'wp-autosharepost-commentgrabber-enabled';
	const OPTION_COMMENTGRABBER_INTERVAL  = 'wp-autosharepost-commentgrabber-interval';
	const OPTION_COMMENTGRABBER_APPROVE	  = 'wp-autosharepost-commentgrabber-approve';
	
	const DEFAULT_COMMENTGRABBER_INTERVAL = 10;
	 
    const META_ENABLED                    = 'wp-autosharepost-enabled';
    const META_FACEBOOK_TEXT              = 'wp-autosharepost-fb-text';
    const META_FACEBOOK_POST			  = 'wp-autosharepost-fb-post-id';
    const META_COMMENT_FACEBOOK_ID		  = 'wp-autosharepost-fb-comment-id';
    const META_COMMENT_FACEBOOK_USER	  = 'wp-autosharepost-fb-comment-user';
    const META_TWITTER_TEXT               = 'wp-autosharepost-twitter-text';
    const META_TWITTER_POST				  = 'wp-autosharepost-twitter-post-id';
    const META_TWITTER_POST_USER		  = 'wp-autosharepost-twitter-post-user';
    const META_SHARED                     = 'wp-autosharepost-shared';
    const META_BITLY_URL                  = 'wp-autosharepost-bitly-url';
    
    /**
     * Holds the template class
     * @var Template
     */
    private $_tpl        = NULL;

    /**
     * Holds the facebook instance
     * @var Facebook
     */
    private $_facebook   = NULL;
    
    /**
     * Holds the twitter instance
     * @var TwitterOAuth
     */
    private $_twitter    = NULL;
    
    /**
     * Holds the Bit.ly instance
     * @var Bitly
     */
    private $_bitly      = NULL;
    
    /**
     * Holds the slug for this plugin
     * @var string
     */
    private $_slug       = 'wp-autosharepost';
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	if (is_admin() || defined('DOING_CRON')) {
	        add_action('init', array(&$this, 'actionInit'));
	        add_action('admin_init', array(&$this, 'actionAdminInit'));
    	}
    }

    /**
     * General Initialization
     */
    public function actionInit()
    {
        add_action('admin_menu',          array(&$this, 'hookAdminMenu'));
        add_action('publish_post',        array(&$this, 'hookPublishPost'));
        add_action('publish_future_post', array(&$this, 'hookPublishFuturePost'));
        
        add_action('wp_autosharepost_comment_grabber', array(&$this, 'cronCommentGrabber'));
    }
    
    /**
     * Admin Initialization
     */
    public function actionAdminInit()
    {
        $this->_tpl = new Template();
        $this->_tpl->wasp = $this;
        
        $this->_getFacebookInstance();
        
        wp_register_style(self::PLUGIN_STYLE_NAME_MAIN,
        				  plugins_url('/css/wp-autoshareposts.css', __FILE__));
        wp_enqueue_style(self::PLUGIN_STYLE_NAME_MAIN);
        
        add_action('add_meta_boxes',      array(&$this, 'hookAddMetaBoxes'));
        add_action('save_post',           array(&$this, 'hookSavePost'));
    }

    /**
     * Gets a Facebook instance
     *
     * This method creates a Facebook instance if none exists or returns an
     * existing one
     *
     * @return Facebook
     */
    protected function _getFacebookInstance()
    {
        if (!class_exists('Facebook')) {
            include_once(WP_AUTOSHAREPOST_DIR . 'lib/facebook/facebook.php');
        }

        if ($this->_facebook === NULL) {
            $appId     = get_option(self::OPTION_FACEBOOK_APPID, NULL);
            $appSecret = get_option(self::OPTION_FACEBOOK_APPSECRET, NULL);

            $this->_facebook = new Facebook(array(
                'appId'  => $appId,
                'secret' => $appSecret,
                'cookie' => TRUE,
            ));
        }

        return $this->_facebook;
    }
    
    /**
     * Gets a TwitterOAuth instance
     *
     * This method creates a TwitterOAuth instance if none exists or returns an
     * existing one
     *
     * @return TwitterOAuth
     */
    protected function _getTwitterInstance()
    {
        if (!class_exists('TwitterOAuth')) {
            include_once(WP_AUTOSHAREPOST_DIR . 'lib/twitter/twitter.php');
        }
        
        if ($this->_twitter === NULL) {
            $this->_twitter = new TwitterOAuth(
                get_option(self::OPTION_TWITTER_CONSUMER_KEY, ''),
                get_option(self::OPTION_TWITTER_CONSUMER_SECRET, ''),
                get_option(self::OPTION_TWITTER_OAUTH_TOKEN, ''),
                get_option(self::OPTION_TWITTER_OAUTH_SECRET, '')
            );
        }
        
        return $this->_twitter;
    }

    /**
     * Gets a Bitly instance
     *
     * This method creates a Bitly instance if none exists or returns an
     * existing one
     *
     * @return Bitly
     */
    protected function _getBitlyInstance()
    {
        if (!class_exists('Bitly')) {
            include_once(WP_AUTOSHAREPOST_DIR . 'lib/bitly/bitly.class.php');
        }
        
        if ($this->_bitly === NULL) {
        	$api_key = get_option(self::OPTION_BITLY_APIKEY, NULL);
        	$login   = get_option(self::OPTION_BITLY_LOGIN, NULL);
        	
        	if (empty($api_key)) $api_key = NULL;
        	if (empty($login))   $login = NULL;
        	
        	if ($api_key !== NULL && $login !== NULL) {
	            $this->_bitly = new Bitly();
	            $this->_bitly->setApiKey($api_key);
	            $this->_bitly->setLogin($login);
        	} else {
        		$this->_bitly = FALSE;
        	}
        }
        
        return $this->_bitly;
    }
    
    /**
     * Hook to add a meta box
     *
     * This hook adds a meta box to the "Edit Post" page to define the various
     * text messages for all configured social networks
     */
    public function hookAddMetaBoxes()
    {
        wp_enqueue_script($this->_slug, plugin_dir_url(__FILE__) . 'js/autosharepost.js');
        
        add_meta_box('wp-autosharepost-text',
	                 __('WP-AutoSharePost', WP_AUTOSHAREPOST_DOMAIN),
	                 array(&$this, 'hookMetaBoxText'),
	                 'post',
	                 'normal');
    }

    /**
     * AutoSharePost MetaBox with Textboxes
     *
     * This is the meta box which is displayed at the end of each post.
     *
     * @param object $post
     */
    public function hookMetaBoxText($post)
    {
        $this->_tpl->nonceField = wp_nonce_field('save-post', $this->_slug);
        
        $this->_tpl->enabled      = get_post_meta($post->ID, self::META_ENABLED, TRUE);
        $this->_tpl->facebookText = get_post_meta($post->ID, self::META_FACEBOOK_TEXT, TRUE);
        $this->_tpl->facebookApp  = get_option(self::OPTION_FACEBOOK_APPID, NULL);
        $this->_tpl->facebookPage = get_option(self::OPTION_FACEBOOK_PAGEID, NULL);
        $this->_tpl->twitterText  = get_post_meta($post->ID, self::META_TWITTER_TEXT, TRUE);
        $this->_tpl->shared       = get_post_meta($post->ID, self::META_SHARED, TRUE);
        
        if (strlen($this->_tpl->enabled) == 0) {
        	$this->_tpl->enabled = get_option(self::OPTION_AUTOENABLED, 0);
        }
        
        if (strtotime($this->_tpl->shared) !== FALSE) {
        	$this->_tpl->facebookPostId = get_post_meta($post->ID, self::META_FACEBOOK_POST, TRUE);
        	$this->_tpl->twitterTweetId = get_post_meta($post->ID, self::META_TWITTER_POST, TRUE);
        	$this->_tpl->twitterUser    = get_post_meta($post->ID, self::META_TWITTER_POST_USER, TRUE);
        }
        
        $this->_tpl->render('metabox/text');
    }

    /**
     * Hook to save a post
     *
     * This hook occurs when a user saves a post
     *
     * @param int $post_id
     */
    public function hookSavePost($post_id)
    {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (!wp_verify_nonce($_POST[$this->_slug], 'save-post')) {
            return;
        }
        
        if (!empty($_POST['autosharepost']['facebook']['text'])) {
            update_post_meta($post_id, self::META_FACEBOOK_TEXT, $_POST['autosharepost']['facebook']['text']);
        }
        if (!empty($_POST['autosharepost']['twitter']['text'])) {
            update_post_meta($post_id, self::META_TWITTER_TEXT, $_POST['autosharepost']['twitter']['text']);
        }
        if (!empty($_POST['autosharepost']['enabled'])) {
            update_post_meta($post_id, self::META_ENABLED, $_POST['autosharepost']['enabled']);
        }
        
    }
    
    /**
     * Hook to publish a post
     *
     * This hook will be called when a post gets published. It shares the
     * specified text messages for all configured social networks.
     *
     * @param int $post_id
     */
    public function hookPublishPost($post_id)
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        	return;
        }
        
        $enabled = get_post_meta($post_id, self::META_ENABLED, TRUE);
        $shared  = get_post_meta($post_id, self::META_SHARED, TRUE);
        
        if ($enabled == '1' && strtotime($shared) === FALSE || $_POST['autosharepost']['re-share'] == '1') {
            $this->_share($post_id);
        }
    }
    
    /**
     * Hook to publish a future post
     *
     * This hook will be called when a future post gets published. It shares the
     * specified text messages for all configured social networks.
     *
     * @param int $post_id
     */
    public function hookPublishFuturePost($post_id)
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        	return;
        }
        
        $enabled = get_post_meta($post_id, self::META_ENABLED, TRUE);
        $shared = get_post_meta($post_id, self::META_SHARED, TRUE);
        
        if ($enabled == '1' && strtotime($shared) === FALSE) {
            $this->_share($post_id);
        }
    }

    /**
     * Hook to add an admin menu
     *
     * This hook adds all needed menu entries to the admin menu
     */
    public function hookAdminMenu()
    {
    	// Add the main settings page
        $pageSettings 		= add_options_page(__('WP-AutoSharePost Settings', WP_AUTOSHAREPOST_DOMAIN),
        									   __('WP-AutoSharePost', WP_AUTOSHAREPOST_DOMAIN),
        									   TRUE,
        									   'wp-autosharepost-settings',
        									   array(&$this, 'actionAutoSharePostSettings'));
        
    	// Add the CommentGrabber settings page
        $pageSettings 		= add_options_page(__('CommentGrabber Settings', WP_AUTOSHAREPOST_DOMAIN),
        									   __('CommentGrabber', WP_AUTOSHAREPOST_DOMAIN),
        									   TRUE,
        									   'wp-autosharepost-commentgrabber',
        									   array(&$this, 'actionCommentGrabberSettings'));
    }

    /**
     * Action to handle settings
     *
     * This action handles the settings page.
     */
    public function actionAutoSharePostSettings()
    {
        wp_enqueue_script($this->_slug, plugin_dir_url(__FILE__) . 'js/autosharepost.js');
        
        if (isset($_POST['submit'])) {
        	if ($_POST['autosharepost']['twitter']['url_seperator'] == 'user-defined') {
        		$_POST['autosharepost']['twitter']['url_seperator'] = $_POST['autosharepost']['twitter']['url_seperator_text'];
        	}
        	
        	// Check if the appId or appSecret have changed ...
        	$appId 		 = get_option(self::OPTION_FACEBOOK_APPID,     '');
        	$appSecret   = get_option(self::OPTION_FACEBOOK_APPSECRET, '');
        	$accessToken = get_option(self::OPTION_FACEBOOK_TOKEN,     '');
        	
        	// ... If so reset the app name and the access token
        	if (!empty($accessToken) && ($appId != $_POST['autosharepost']['facebook']['app_id'] ||
        								 $appSecret != $_POST['autosharepost']['facebook']['app_secret'])) {
				update_option(self::OPTION_FACEBOOK_APPNAME, '');
                update_option(self::OPTION_FACEBOOK_TOKEN,   '');
        	}
        	
            // General options
            update_option(self::OPTION_AUTOENABLED,             $_POST['autosharepost']['enabled']);
            
            // Facebook options
            update_option(self::OPTION_FACEBOOK_APPID,          trim($_POST['autosharepost']['facebook']['app_id']));
            update_option(self::OPTION_FACEBOOK_APPSECRET,      trim($_POST['autosharepost']['facebook']['app_secret']));
            update_option(self::OPTION_FACEBOOK_PAGEID,         trim($_POST['autosharepost']['facebook']['page_id']));
            update_option(self::OPTION_FACEBOOK_POSTINGTYPE,    $_POST['autosharepost']['facebook']['type']);
            update_option(self::OPTION_FACEBOOK_DESCRIPTION,    intval($_POST['autosharepost']['facebook']['description']));
            update_option(self::OPTION_FACEBOOK_DISABLE_BITLY,  $_POST['autosharepost']['facebook']['disable_bitly']);
            
            // Twitter options
            update_option(self::OPTION_TWITTER_CONSUMER_KEY,    trim($_POST['autosharepost']['twitter']['consumer_key']));
            update_option(self::OPTION_TWITTER_CONSUMER_SECRET, trim($_POST['autosharepost']['twitter']['consumer_secret']));
            update_option(self::OPTION_TWITTER_OAUTH_TOKEN,     trim($_POST['autosharepost']['twitter']['oauth_token']));
            update_option(self::OPTION_TWITTER_OAUTH_SECRET,    trim($_POST['autosharepost']['twitter']['oauth_secret']));
            update_option(self::OPTION_TWITTER_URL_SEPERATOR,   $_POST['autosharepost']['twitter']['url_seperator']);
            
            // Bit.ly options
            update_option(self::OPTION_BITLY_APIKEY,       	    trim($_POST['autosharepost']['bitly']['api_key']));
            update_option(self::OPTION_BITLY_LOGIN,             trim($_POST['autosharepost']['bitly']['login']));
        }
        
        // These have to be loaded first, because we need them if we got back
        // from facebook and have to iterate over all pages/apps
        $appId          = get_option(self::OPTION_FACEBOOK_APPID,     '');
        $pageId         = get_option(self::OPTION_FACEBOOK_PAGEID,    '');
        $appSecret      = get_option(self::OPTION_FACEBOOK_APPSECRET, '');

        // Token was requested
        if (isset($_GET['code'])) {
            $session = $this->_facebook->getAccessToken();

            try {
                // Ask facebook for all apps and pages this user manages
                $result = $this->_facebook->api('/me/accounts');
                if (is_array($result['data'])) {
                    $found = FALSE;

                    $searchId = $appId;
                    if (!empty($pageId)) {
                        $searchId = $pageId;
                    }
                    
                    // Iterate over all retrieved pages and apps to get their access_token
                    foreach ($result['data'] as $app) {
                        if (trim($app['id']) == trim($searchId)) {
                            update_option(self::OPTION_FACEBOOK_APPNAME, $app['name']);
                            update_option(self::OPTION_FACEBOOK_TOKEN, $app['access_token']);
                            $found = TRUE;
                        }
                    }

                    if ($found === FALSE) {
                        $this->_tpl->facebookError = sprintf(__('Could not find any app associated with this user with appId "%1$s".', WP_AUTOSHAREPOST_DOMAIN), $appId);
                    }
                } else {
                    $this->_tpl->facebookError = __('"data" member in wrong format.', WP_AUTOSHAREPOST_DOMAIN);
                }
            } catch (Exception $e) {
                $this->_tpl->facebookError = $e->getMessage();
            }
        }
        
        // General options
        $this->_tpl->autoEnabled         = get_option(self::OPTION_AUTOENABLED, '');
        
        // Facebook options
        $this->_tpl->facebookAppId       = $appId;
        $this->_tpl->facebookAppSecret   = $appSecret;
        $this->_tpl->facebookPageId      = $pageId;
        $this->_tpl->facebookAppName     = get_option(self::OPTION_FACEBOOK_APPNAME, '');
        $this->_tpl->facebookAccessToken = get_option(self::OPTION_FACEBOOK_TOKEN, '');
        $this->_tpl->facebookLogin       = $this->_facebook->getLoginUrl(array(
            'scope'        => 'manage_pages',
            'display'      => 'page',
        	'redirect_uri' => ((!empty($_SERVER['HTTPS'])) ? "https://" : "http://")
        					  . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
        ));
        $this->_tpl->facebookPostingType	  = get_option(self::OPTION_FACEBOOK_POSTINGTYPE, 'link');
        $this->_tpl->facebookDescriptionWords = get_option(self::OPTION_FACEBOOK_DESCRIPTION, 40);
        $this->_tpl->facebookBitlyDisabled    = get_option(self::OPTION_FACEBOOK_DISABLE_BITLY, '');
        
        // Twitter options
        $this->_tpl->twitterConsumerKey     = get_option(self::OPTION_TWITTER_CONSUMER_KEY, '');
        $this->_tpl->twitterConsumerSecret  = get_option(self::OPTION_TWITTER_CONSUMER_SECRET, '');
        $this->_tpl->twitterOAuthToken      = get_option(self::OPTION_TWITTER_OAUTH_TOKEN, '');
        $this->_tpl->twitterOAuthSecret     = get_option(self::OPTION_TWITTER_OAUTH_SECRET, '');
        $this->_tpl->twitterUrlSeperator    = get_option(self::OPTION_TWITTER_URL_SEPERATOR, '');
        
        // Bit.ly options
        $this->_tpl->bitlyApiKey         = get_option(self::OPTION_BITLY_APIKEY, '');
        $this->_tpl->bitlyLogin          = get_option(self::OPTION_BITLY_LOGIN, '');

        $this->_tpl->render('settings/index');
    }
    
    /**
     * Action to handle CommentGrabber settings
     *
     * This methods handles the settings page for the Facebook.com CommentGrabber
     */
    public function actionCommentGrabberSettings()
    {
        wp_enqueue_script($this->_slug, plugin_dir_url(__FILE__) . 'js/autosharepost.js');
        
        if (isset($_POST['submit'])) {
            // CommentGrabber options
            update_option(self::OPTION_COMMENTGRABBER_ENABLED,      $_POST['commentgrabber']['enabled']);
            update_option(self::OPTION_COMMENTGRABBER_INTERVAL,		$_POST['commentgrabber']['interval']);
            update_option(self::OPTION_COMMENTGRABBER_APPROVE,      $_POST['commentgrabber']['approve']);
            
            if (intval($_POST['commentgrabber']['enabled']) == 0) {
	            if (wp_next_scheduled('wp_autosharepost_comment_grabber')) {
	            	wp_clear_scheduled_hook('wp_autosharepost_comment_grabber');
	            }
            }
            
            if (intval($_POST['commentgrabber']['enabled']) == 1) {
	            if (!wp_next_scheduled('wp_autosharepost_comment_grabber')) {
	            	wp_schedule_event(time(), 'wp-autosharepost-interval', 'wp_autosharepost_comment_grabber');
	            }
            }
        }
        
        // CommentGrabber options
        $this->_tpl->grabberEnabled		= get_option(self::OPTION_COMMENTGRABBER_ENABLED, 0);
        $this->_tpl->grabberInterval    = get_option(self::OPTION_COMMENTGRABBER_INTERVAL, self::DEFAULT_COMMENTGRABBER_INTERVAL);
        $this->_tpl->grabberApprove     = get_option(self::OPTION_COMMENTGRABBER_APPROVE, 0);

        $this->_tpl->render('settings/comment-grabber');
    }
    
    /**
     * Grabs all comments from Facebook
     *
     * This method reads the feed of a page and grabs all the comments written by
     * other users.
     *
     * @throws Exception
     */
    public function cronCommentGrabber()
    {
    	global $wpdb;
    	
    	$enabled 		= intval(get_option(self::OPTION_COMMENTGRABBER_ENABLED, 0));
    	$approve 		= intval(get_option(self::OPTION_COMMENTGRABBER_APPROVE, 0));
        $appId          = get_option(self::OPTION_FACEBOOK_APPID, '');
        $pageId         = get_option(self::OPTION_FACEBOOK_PAGEID, '');
        
        if ($enabled == 0) return;
        
        $fb = $this->_getFacebookInstance();
        $fb->setAccessToken(get_option(self::OPTION_FACEBOOK_TOKEN, ''));
        
        $fb_result = $fb->api('/' . $pageId . '/feed/');
        
        if (is_array($fb_result['data'])) {
        	// Iterate over all posts
	        foreach ($fb_result['data'] as $post) {
	        	if (!isset($post['comments']['count'])) continue;
	        	if ($post['comments']['count'] == 0) continue;
	        	
	        	$post_id = explode('_', $post['id']);
	        	
	        	// Try to find the attached blog post
			    $row = $wpdb->get_row("SELECT * "
			        				 ."FROM $wpdb->postmeta "
			        			 	 ."WHERE meta_key = '" . self::META_FACEBOOK_POST . "' "
			        				 ."AND   meta_value = '" . $post_id[1] . "'");
			    
			    // Only go on if we found the post for which this comment was
			    if ($row->post_id > 0) {
			    	$comment_post = get_post($row->post_id);
			    	
			    	// Iterate over all comments
			    	foreach ($post['comments']['data'] as $comment) {
			    		$comment_row = $wpdb->get_row("SELECT * "
				    						 		 ."FROM $wpdb->commentmeta "
				    						 		 ."WHERE meta_key = '" . self::META_COMMENT_FACEBOOK_ID . "' "
				    						 		 ."AND   meta_value = '" . $comment['id'] . "'");

			    		// Check if we already have this comment in our database
			    		if ($comment_row->comment_id > 0) continue;
			    		
			    		// Create a new comment
			    		$new_comment = array(
				    		'comment_post_ID' 		=> $row->post_id,
				    		'comment_author' 		=> $comment['from']['name'],
				    		'comment_author_email' 	=> '',
				    		'comment_author_url' 	=> '',
				    		'comment_content' 		=> $comment['message'],
				    		'comment_type' 			=> 'facebook',
				    		'comment_parent' 		=> 0,
				    		'user_id' 				=> 0,
				    		'comment_author_IP' 	=> '127.0.0.1',
				    		'comment_agent' 		=> 'FacebookCommentGrabber|' . $comment['id'],
				    		'comment_date' 			=> date('Y-m-d H:i:s', strtotime($comment['created_time'])),
				    		'comment_approved' 		=> $approve,
			    		);
			    		
			    		// Save the comment and the facebook comment id for reference
			    		
			    		$comment_id = wp_insert_comment($new_comment);
			    		
			    		update_comment_meta($comment_id, self::META_COMMENT_FACEBOOK_ID, 	$comment['id'], TRUE);
			    		update_comment_meta($comment_id, self::META_COMMENT_FACEBOOK_USER, 	$comment['from']['id'], TRUE);
			    		
			    		// Update the comment count for this post
			    		wp_update_comment_count($row->post_id);
			    	}
			    }
	        }
        } else {
        	throw new Exception(__('Could not load data from facebook.'));
        }
    }
    
    /**
     * Shares a post to social networks
     *
     * This method shares the specified text messages for a post to all
     * configured social networks
     *
     * @param int $post_id
     */
    protected function _share($post_id)
    {
        // Try to get the post
        $post = get_post($post_id);
        $error = '';
         
        // Share this now on all platforms
        if ($post->post_status == 'publish' && $post->post_type == 'post') {
            // Check if this post already has a shortened bitly url
            $bitlyUrl = get_post_meta($post_id, self::META_BITLY_URL, TRUE);
            $permalink = get_permalink($post_id);
            
            // Generate a new bitly url
            if (empty($bitlyUrl)) {
                if ($bitly = $this->_getBitlyInstance()) {
	                $bitly_result = $bitly->shorten(array(
	                    'longUrl' => $permalink
	                ));
	                
	                $bitlyUrl = $bitly_result->data->url;
	                update_post_meta($post_id, self::META_BITLY_URL, $bitlyUrl);
                } else {
                	$bitlyUrl = $permalink;
                }
            }
            
            $picture = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
            
            // Post on facebook.com
            $disableBitly = get_option(self::OPTION_FACEBOOK_DISABLE_BITLY, '');
            
            $words = preg_split('/[\s]+/', $post->post_content, NULL, PREG_SPLIT_DELIM_CAPTURE);
            $text = strip_tags(implode(' ', array_slice($words, 0, get_option(self::OPTION_FACEBOOK_DESCRIPTION, 20))));
            
            // Prepare the facebook parameters
            $facebook = array(
                'message'     => get_post_meta($post_id, self::META_FACEBOOK_TEXT, TRUE),
                'link'        => ($disableBitly == '1') ? $permalink : $bitlyUrl,
            	'name'	 	  => $post->post_title,
                'description' => rtrim($text, '.') . '...',
            );
            
            // Add the correct picture for this status message or link
            if (is_array($picture)) {
            	$facebook['picture'] = $picture[0];
            }
            
            $accessToken = get_option(self::OPTION_FACEBOOK_TOKEN, NULL);
            $postingType = get_option(self::OPTION_FACEBOOK_POSTINGTYPE, 'link');
            
            if (empty($error) && !empty($facebook['message'])) {
                if (!empty($accessToken)) {
                	// Get an instance and set the corresponding access token
                    $fb = $this->_getFacebookInstance();
                    $fb->setAccessToken($accessToken);
                    
                    $appId          = get_option(self::OPTION_FACEBOOK_APPID, '');
                    $pageId         = get_option(self::OPTION_FACEBOOK_PAGEID, '');
                    
                    $profileId = $appId;
                    if (!empty($pageId)) $profileId = $pageId;
                    
                    try {
                    	// Share this post and save the post id in a meta field
                    	switch ($postingType) {
                    		case 'link':
                        		$fb_result = $fb->api('/' . $profileId . '/links', 'POST', $facebook);
                        		break;
                    		case 'status':
                        		$fb_result = $fb->api('/' . $profileId . '/feed', 'POST', $facebook);
                    			break;
                    	}
                    	
                        update_post_meta($post_id, self::META_FACEBOOK_POST, $fb_result['id']);
                    } catch(Exception $e) {
                        $error = sprintf(__('Could not post on facebook.com. Reason: %1$s'), $e->getMessage());
                    }
                } else {
                    $error = __('Could not post on facebook.com "AccessToken" missing');
                }
            }
            
            $twitter = array(
            	'message' => get_post_meta($post_id, self::META_TWITTER_TEXT, TRUE)
            );
            
            // Tweet on twitter.com
            if (empty($error) && !empty($twitter['message'])) {
                $seperator = get_option(self::OPTION_TWITTER_URL_SEPERATOR, NULL);
                if (empty($seperator)) $seperator = ' ';
                
                // Tweet this on twitter
                $tw = $this->_getTwitterInstance();
                $tw_result = $tw->post('statuses/update', array(
                    'status' => $twitter['message'] . $seperator . $bitlyUrl
                ));
                
                // Check for any errors
                if (isset($tw_result->error)) {
                	$error = $tw_result->error;
                } else {
                	// Save TweetID and user
                	update_post_meta($post_id, self::META_TWITTER_POST, $tw_result->id_str);
                	update_post_meta($post_id, self::META_TWITTER_POST_USER, (!empty($tw_result->user->screen_name) ? $tw_result->user->screen_name : $tw_result->user->id));
                }
            }
            
            // Update the shared time
            if (empty($error)) {
                update_post_meta($post_id, self::META_SHARED, date('Y-m-d H:i:s'));
            } else {
            	echo '<div class="error">' . $error . '</div>';
            }
        }
    }

}

// Instantiate the admin class
$wasp_admin = new WordpressAutoSharePostAdmin();
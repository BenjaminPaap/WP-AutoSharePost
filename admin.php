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
    const OPTION_FACEBOOK_DEFAULT         = 'wp-autosharepost-fb-default';
    const OPTION_FACEBOOK_POSTINGTYPE	  = 'wp-autosharepost-fb-postingtype';
    const OPTION_FACEBOOK_ALBUM_CREATE	  = 'wp-autosharepost-fb-album-create';
    const OPTION_FACEBOOK_DESCRIPTION	  = 'wp-autosharepost-fb-description';
    const OPTION_FACEBOOK_DISABLE_BITLY   = 'wp-autosharepost-fb-disablebitly';
    
    const OPTION_TWITTER_CONSUMER_KEY     = 'wp-autosharepost-twitter-consumer-key';
    const OPTION_TWITTER_CONSUMER_SECRET  = 'wp-autosharepost-twitter-consumer-secret';
    const OPTION_TWITTER_OAUTH_TOKEN      = 'wp-autosharepost-twitter-oauth-token';
    const OPTION_TWITTER_OAUTH_SECRET     = 'wp-autosharepost-twitter-oauth-secret';
    const OPTION_TWITTER_URL_SEPERATOR    = 'wp-autosharepost-twitter-oauth-url-seperator';
    const OPTION_TWITTER_DEFAULT          = 'wp-autosharepost-twitter-default';
    
    const OPTION_BITLY_APIKEY             = 'wp-autosharepost-bitly-apikey';
    const OPTION_BITLY_LOGIN              = 'wp-autosharepost-bitly-login';
    
	const OPTION_COMMENTGRABBER_ENABLED   = 'wp-autosharepost-commentgrabber-enabled';
	const OPTION_COMMENTGRABBER_INTERVAL  = 'wp-autosharepost-commentgrabber-interval';
	const OPTION_COMMENTGRABBER_APPROVE	  = 'wp-autosharepost-commentgrabber-approve';
    const OPTION_COMMENTGRABBER_FB_PAGES  = 'wp-autosharepost-commentgrabber-fb-pages';
	
	const OPTION_SHARE_PICTURE			  = 'wp-autosharepost-share-picture';
	const OPTION_SHARE_PICTURE_SIZE		  = 'wp-autosharepost-share-picture-size';
	const OPTION_POST_TYPES               = 'wp-autosharepost-post-types';
	
	const DEFAULT_COMMENTGRABBER_INTERVAL = 10;
	const DEFAULT_SHARE_PICTURE_SIZE      = 'full';
	
    const META_ENABLED                    = 'wp-autosharepost-enabled';
    const META_FACEBOOK_TEXT              = 'wp-autosharepost-fb-text';
    const META_FACEBOOK_POST			  = 'wp-autosharepost-fb-post-id';
    const META_FACEBOOK_POST_TYPE		  = 'wp-autosharepost-fb-post-type';
    const META_COMMENT_FACEBOOK_ID		  = 'wp-autosharepost-fb-comment-id';
    const META_COMMENT_FACEBOOK_USER	  = 'wp-autosharepost-fb-comment-user';
    const META_TWITTER_TEXT               = 'wp-autosharepost-twitter-text';
    const META_TWITTER_POST				  = 'wp-autosharepost-twitter-post-id';
    const META_TWITTER_POST_USER		  = 'wp-autosharepost-twitter-post-user';
    const META_SHARED                     = 'wp-autosharepost-shared';
    const META_BITLY_URL                  = 'wp-autosharepost-bitly-url';
    
    const FACEBOOK_POSTING_TYPE_STATUS	  = 'status';
    const FACEBOOK_POSTING_TYPE_LINK	  = 'link';
    const FACEBOOK_POSTING_TYPE_PHOTO     = 'photo';
	const FACEBOOK_POSTING_TYPE_VIDEO 	  = 'video';
    
    const PICTURE_NONE 			= 'none';
    const PICTURE_THUMBNAIL 	= 'thumbnail';
    const PICTURE_ATTACHMENT 	= 'attachment';
    
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
    
    public static $facebookPostTypes = array(
    	self::FACEBOOK_POSTING_TYPE_LINK,
    	self::FACEBOOK_POSTING_TYPE_STATUS,
    	self::FACEBOOK_POSTING_TYPE_PHOTO,
    	// self::FACEBOOK_POSTING_TYPE_VIDEO,
    );
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_getFacebookInstance();
        
    	if (is_admin() || defined('DOING_CRON')) {
	        add_action('init', array(&$this, 'actionInit'));
	        add_action('admin_init', array(&$this, 'actionAdminInit'));
    	}
    	
        add_action('publish_post',        array(&$this, 'hookPublishPost'));
        add_action('publish page',        array(&$this, 'hookPublishPost'));
        add_action('publish_future_post', array(&$this, 'hookPublishFuturePost'));
    }

    /**
     * General Initialization
     */
    public function actionInit()
    {
        add_action('admin_menu',          array(&$this, 'hookAdminMenu'));
        
        add_action('wp_autosharepost_comment_grabber', array(&$this, 'cronCommentGrabber'));
    }
    
    /**
     * Admin Initialization
     */
    public function actionAdminInit()
    {
        $this->_tpl = new Template();
        $this->_tpl->wasp = $this;
        
        $this->_checkFacebookAccessToken();
        
        wp_register_style(self::PLUGIN_STYLE_NAME_MAIN,
        				  plugins_url('/css/wp-autoshareposts.css', __FILE__));
        wp_enqueue_style(self::PLUGIN_STYLE_NAME_MAIN);
        
        add_action('add_meta_boxes',      array(&$this, 'hookAddMetaBoxes'));
        add_action('save_post',           array(&$this, 'hookSavePost'));
    }
    
    protected function _checkFacebookAccessToken()
    {
        // These have to be loaded first, because we need them if we got back
        // from facebook and have to iterate over all pages/apps
        $pageId         = get_option(self::OPTION_FACEBOOK_PAGEID,    '');
        
    	// Token was requested
    	if (isset($_GET['code'])) {
    		$user = $this->_getFacebookInstance()->getUser();
    		 
    		if (!empty($user)) {
    			try {
    				// Ask facebook for all apps and pages this user manages
    				if (!empty($pageId)) {
    					$result = $this->_getFacebookInstance()->api('/me/accounts');
    	
    					if (is_array($result['data'])) {
    						$found = FALSE;
    	
    						// Iterate over all retrieved pages and apps to get their access_token
    						foreach ($result['data'] as $app) {
    							if (trim($app['id']) == trim($pageId)) {
    								update_option(self::OPTION_FACEBOOK_APPNAME, $app['name']);
    								update_option(self::OPTION_FACEBOOK_TOKEN, $app['access_token']);
    								$found = TRUE;
    	
    								header('Location: /wp-admin/options-general.php?page=wp-autosharepost-settings');
    								exit;
    							}
    						}
    	
    						if ($found === FALSE) {
    							$this->_tpl->facebookError = sprintf(__('Could not find any app associated with this user with appId "%1$s".', WP_AUTOSHAREPOST_DOMAIN), $appId);
    						}
    					} else {
    						$this->_tpl->facebookError = __('"data" member in wrong format.', WP_AUTOSHAREPOST_DOMAIN);
    					}
    				} else {
    					// User wants to share on his own wall
    					$result = $this->_getFacebookInstance()->api('/me');
    	
    					update_option(self::OPTION_FACEBOOK_APPNAME, $result['name']);
    					update_option(self::OPTION_FACEBOOK_TOKEN, $this->_getFacebookInstance()->getAccessToken());
    				}
    			} catch (Exception $e) {
    				$this->_tpl->facebookError = $e->getMessage();
    			}
    		} else {
    			$this->_tpl->facebookError = __('No facebook user supplied. Did you give all permissions?', WP_AUTOSHAREPOST_DOMAIN);
    		}
    	}
    }
    
    /**
     * Gets all allowed post types from the options
     *
     * This method returns all post types from the options which are allowed for
     * use with WP-AutoSharePost
     *
     * @return array
     */
    protected function _getPostTypes()
    {
        // Get the allowed post types from the options. If no options are set use
        // the default 'post' post type
        $post_types = get_option(self::OPTION_POST_TYPES, NULL);

        if ($post_types === NULL) {
            $post_types = array('post');
        }
        
        if (!is_array($post_types)) {
            $post_types = unserialize($post_types);
            if (!is_array($post_types)) {
                $post_types = array('post');
            }
        }
        
        return $post_types;
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

            $options = array(
                'appId'  => $appId,
                'secret' => $appSecret,
                'cookie' => TRUE,
            );
            
            $this->_facebook = new Facebook($options);
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
        
        $consumer_key    = get_option(self::OPTION_TWITTER_CONSUMER_KEY, '');
        $consumer_secret = get_option(self::OPTION_TWITTER_CONSUMER_SECRET, '');
        $oauth_token     = get_option(self::OPTION_TWITTER_OAUTH_TOKEN, '');
        $oauth_secret    = get_option(self::OPTION_TWITTER_OAUTH_SECRET, '');
        
        if ($this->_twitter === NULL) {
        	if (!empty($consumer_key) &&
        		!empty($consumer_secret) &&
        		!empty($oauth_token) &&
        		!empty($oauth_secret)) {
	            $this->_twitter = new TwitterOAuth(
	                $consumer_key,
	                $consumer_secret,
	                $oauth_token,
	                $oauth_secret
	            );
        	}
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
     * text messages for all configured social networks. Post types for which
     * this meta box will be added can be defined in the settings.
     */
    public function hookAddMetaBoxes()
    {
        wp_enqueue_script($this->_slug, plugin_dir_url(__FILE__) . 'js/autosharepost.js');
        
        $post_types = $this->_getPostTypes();
        
        foreach ($post_types as $post_type) {
            add_meta_box('wp-autosharepost-text',
    	                 __('WP-AutoSharePost', WP_AUTOSHAREPOST_DOMAIN),
    	                 array(&$this, 'hookMetaBoxText'),
    	                 $post_type,
    	                 'normal',
                         'low');
        }
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
        $this->_tpl->facebookType = get_post_meta($post->ID, self::META_FACEBOOK_POST_TYPE, TRUE);
        $this->_tpl->facebookApp  = get_option(self::OPTION_FACEBOOK_APPID, NULL);
        $this->_tpl->facebookPage = get_option(self::OPTION_FACEBOOK_PAGEID, NULL);
        $this->_tpl->twitterText  = get_post_meta($post->ID, self::META_TWITTER_TEXT, TRUE);
        $this->_tpl->shared       = get_post_meta($post->ID, self::META_SHARED, TRUE);
        
        if (strlen($this->_tpl->enabled) == 0) {
        	$this->_tpl->enabled = get_option(self::OPTION_AUTOENABLED, 0);
        }
        if (empty($this->_tpl->facebookType)) {
        	$this->_tpl->facebookType = get_option(self::OPTION_FACEBOOK_POSTINGTYPE, self::FACEBOOK_POSTING_TYPE_LINK);
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
        if (wp_is_post_revision($post_id))
        	$post_id = wp_is_post_revision($post_id);
        
        // Check if the current user can edit a post if this is a post
        if (!current_user_can('edit_post', $post_id) && !is_page($post_id)) {
            return;
        }
        // Check if the current user can edit a page if this is a page
        if (!current_user_can('edit_page', $post_id) && is_page($post_id)) {
            return;
        }
        
        if (!wp_verify_nonce($_POST[$this->_slug], 'save-post')) {
            return;
        }
        
        if ('page' == $_POST['post_type']) {
            $post = get_page($post_id);
        } else {
            $post = get_post($post_id);
        }
        
        if (!empty($_POST['autosharepost']['facebook']['text'])) {
            update_post_meta($post->ID, self::META_FACEBOOK_TEXT, $_POST['autosharepost']['facebook']['text']);
        }
        if (!empty($_POST['autosharepost']['facebook']['type'])) {
            update_post_meta($post->ID, self::META_FACEBOOK_POST_TYPE, $_POST['autosharepost']['facebook']['type']);
        }
        if (!empty($_POST['autosharepost']['twitter']['text'])) {
            update_post_meta($post->ID, self::META_TWITTER_TEXT, $_POST['autosharepost']['twitter']['text']);
        }
        if (!empty($_POST['autosharepost']['enabled'])) {
            update_post_meta($post->ID, self::META_ENABLED, $_POST['autosharepost']['enabled']);
        }
        
        // Special case for pages which are not getting published twice if only updated
        if ('page' == $_POST['post_type']) {
            $enabled = get_post_meta($post_id, self::META_ENABLED, TRUE);
            $shared  = get_post_meta($post_id, self::META_SHARED, TRUE);
            
            if ($enabled == '1' && strtotime($shared) === FALSE || $_POST['autosharepost']['re-share'] == '1') {
            	$this->_share($post_id);
            }
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
        $shared  = get_post_meta($post_id, self::META_SHARED, TRUE);
        
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
        $commentGrabber		= add_options_page(__('CommentGrabber Settings', WP_AUTOSHAREPOST_DOMAIN),
        									   __('CommentGrabber', WP_AUTOSHAREPOST_DOMAIN),
        									   TRUE,
        									   'wp-autosharepost-commentgrabber',
        									   array(&$this, 'actionCommentGrabberSettings'));
        
    	// Add the CommentGrabber settings page
    	if (defined('WP_AUTOSHAREPOST_DEBUG')) {
        	$debugPage 		= add_options_page(__('CommentGrabber Debug', WP_AUTOSHAREPOST_DOMAIN),
        			 						   __('CommentGrabber Debug', WP_AUTOSHAREPOST_DOMAIN),
        									   TRUE,
        									   'wp-autosharepost-commentgrabber-debug',
        									   array(&$this, 'actionCommentGrabberDebug'));
    	}
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
        	$pageId 	 = get_option(self::OPTION_FACEBOOK_PAGEID,    '');
        	$appSecret   = get_option(self::OPTION_FACEBOOK_APPSECRET, '');
        	$accessToken = get_option(self::OPTION_FACEBOOK_TOKEN,     '');
        	
        	// ... If so reset the app name and the access token
        	if (!empty($accessToken) && ($appId     != $_POST['autosharepost']['facebook']['app_id'] ||
        								 $pageId    != $_POST['autosharepost']['facebook']['page_id'] ||
        								 $appSecret != $_POST['autosharepost']['facebook']['app_secret'])) {
				update_option(self::OPTION_FACEBOOK_APPNAME, '');
                update_option(self::OPTION_FACEBOOK_TOKEN,   '');
        	}
        	
            // General options
            update_option(self::OPTION_AUTOENABLED,             $_POST['autosharepost']['enabled']);
            update_option(self::OPTION_SHARE_PICTURE,           $_POST['autosharepost']['picture']);
            update_option(self::OPTION_SHARE_PICTURE_SIZE,      $_POST['autosharepost']['picture_size']);
            
            // Facebook options
            update_option(self::OPTION_FACEBOOK_APPID,          trim($_POST['autosharepost']['facebook']['app_id']));
            update_option(self::OPTION_FACEBOOK_APPSECRET,      trim($_POST['autosharepost']['facebook']['app_secret']));
            update_option(self::OPTION_FACEBOOK_PAGEID,         trim($_POST['autosharepost']['facebook']['page_id']));
            update_option(self::OPTION_FACEBOOK_DEFAULT,    	trim($_POST['autosharepost']['facebook']['default']));
            update_option(self::OPTION_FACEBOOK_POSTINGTYPE,    $_POST['autosharepost']['facebook']['type']);
            update_option(self::OPTION_FACEBOOK_ALBUM_CREATE,   $_POST['autosharepost']['facebook']['album']);
            update_option(self::OPTION_FACEBOOK_DESCRIPTION,    intval($_POST['autosharepost']['facebook']['description']));
            update_option(self::OPTION_FACEBOOK_DISABLE_BITLY,  $_POST['autosharepost']['facebook']['disable_bitly']);
            
            // Twitter options
            update_option(self::OPTION_TWITTER_CONSUMER_KEY,    trim($_POST['autosharepost']['twitter']['consumer_key']));
            update_option(self::OPTION_TWITTER_CONSUMER_SECRET, trim($_POST['autosharepost']['twitter']['consumer_secret']));
            update_option(self::OPTION_TWITTER_OAUTH_TOKEN,     trim($_POST['autosharepost']['twitter']['oauth_token']));
            update_option(self::OPTION_TWITTER_OAUTH_SECRET,    trim($_POST['autosharepost']['twitter']['oauth_secret']));
            update_option(self::OPTION_TWITTER_URL_SEPERATOR,   $_POST['autosharepost']['twitter']['url_seperator']);
            update_option(self::OPTION_TWITTER_DEFAULT,    		trim($_POST['autosharepost']['twitter']['default']));
            
            // Bit.ly options
            update_option(self::OPTION_BITLY_APIKEY,       	    trim($_POST['autosharepost']['bitly']['api_key']));
            update_option(self::OPTION_BITLY_LOGIN,             trim($_POST['autosharepost']['bitly']['login']));
            
            if (!is_array($_POST['autosharepost']['post_types'])) {
                $_POST['autosharepost']['post_types'] = array($_POST['autosharepost']['post_types']);
            }
            
            $post_types = serialize($_POST['autosharepost']['post_types']);
            update_option(self::OPTION_POST_TYPES,              $post_types);
        }
        
        // General options
        $this->_tpl->autoEnabled         = get_option(self::OPTION_AUTOENABLED, '');
        $this->_tpl->picture    	     = get_option(self::OPTION_SHARE_PICTURE, 'none');
        $this->_tpl->pictureSize         = get_option(self::OPTION_SHARE_PICTURE_SIZE, 'large');
        
        // Facebook options
        $this->_tpl->facebookAppId       = get_option(self::OPTION_FACEBOOK_APPID, '');
        $this->_tpl->facebookAppSecret   = get_option(self::OPTION_FACEBOOK_APPSECRET, '');
        $this->_tpl->facebookPageId      = get_option(self::OPTION_FACEBOOK_PAGEID, '');
        $this->_tpl->facebookAppName     = get_option(self::OPTION_FACEBOOK_APPNAME, '');
        $this->_tpl->facebookAccessToken = get_option(self::OPTION_FACEBOOK_TOKEN, '');
        
        if (!empty($this->_tpl->facebookAppId) && !empty($this->_tpl->facebookAppSecret)) {
        	// Reset the facebook instance to get login url with correct app id and secret
        	if (isset($_POST['submit'])) {
        		$this->_facebook = NULL;
        	}
        	$fb = $this->_getFacebookInstance();
        	
	        $this->_tpl->facebookLogin       = $fb->getLoginUrl(array(
	            'scope'        => 'manage_pages,publish_stream,share_item',
	            'display'      => 'page',
	        	'redirect_uri' => ((!empty($_SERVER['HTTPS'])) ? "https://" : "http://")
	        					  . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
	        ));
	    }
	    
        $this->_tpl->facebookDefaultText      = get_option(self::OPTION_FACEBOOK_DEFAULT, '');
        $this->_tpl->facebookPostingType	  = get_option(self::OPTION_FACEBOOK_POSTINGTYPE, self::FACEBOOK_POSTING_TYPE_LINK);
        $this->_tpl->facebookAlbum			  = get_option(self::OPTION_FACEBOOK_ALBUM_CREATE, 0);
        $this->_tpl->facebookDescriptionWords = get_option(self::OPTION_FACEBOOK_DESCRIPTION, 40);
        $this->_tpl->facebookBitlyDisabled    = get_option(self::OPTION_FACEBOOK_DISABLE_BITLY, '');
        
        // Twitter options
        $this->_tpl->twitterConsumerKey     = get_option(self::OPTION_TWITTER_CONSUMER_KEY, '');
        $this->_tpl->twitterConsumerSecret  = get_option(self::OPTION_TWITTER_CONSUMER_SECRET, '');
        $this->_tpl->twitterOAuthToken      = get_option(self::OPTION_TWITTER_OAUTH_TOKEN, '');
        $this->_tpl->twitterOAuthSecret     = get_option(self::OPTION_TWITTER_OAUTH_SECRET, '');
        $this->_tpl->twitterUrlSeperator    = get_option(self::OPTION_TWITTER_URL_SEPERATOR, '');
        $this->_tpl->twitterDefaultText     = get_option(self::OPTION_TWITTER_DEFAULT, '');
        
        // Bit.ly options
        $this->_tpl->bitlyApiKey         = get_option(self::OPTION_BITLY_APIKEY, '');
        $this->_tpl->bitlyLogin          = get_option(self::OPTION_BITLY_LOGIN, '');

        $this->_tpl->postTypes           = $this->_getPostTypes();
        
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
            update_option(self::OPTION_COMMENTGRABBER_FB_PAGES,     intval($_POST['commentgrabber']['pages']));
            
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
        $this->_tpl->grabberPages       = intval(get_option(self::OPTION_COMMENTGRABBER_FB_PAGES, 1));

        $this->_tpl->render('settings/comment-grabber');
    }
    
    /**
     * Debug action for the comment grabber
     *
     * This method is only for debugging purposes
     */
    public function actionCommentGrabberDebug()
    {
    	$this->cronCommentGrabber();
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
        
        $posts = array();
        $pages = get_option(self::OPTION_COMMENTGRABBER_FB_PAGES, 1);
        $page_link = '/' . $pageId . '/feed/';
        
        // Iterate through all pages
        for ($page = 0; $page < $pages; $page ++) {
	        $fb_result = $fb->api($page_link);
        	if (is_array($fb_result['data']) && count($fb_result['data']) > 0) {
        		foreach ($fb_result['data'] as $post) {
        			$posts[] = $post;
        		}
        	} else {
        		break;
        	}
        	
        	if (isset($fb_result['paging']['next']) && !empty($fb_result['paging']['next'])) {
        		$page_link = $fb_result['paging']['next'];
        		$page_link = substr($page_link, strpos($page_link, '/', 9));
        	}
        }
        
        if (count($posts) > 0) {
        	// Iterate over all posts
	        foreach ($posts as $post) {
	        	if (!isset($post['comments']['count'])) continue;
	        	if ($post['comments']['count'] == 0) continue;
	        	
	        	if ($post['type'] == 'photo') {
	        		$post_id = $post['object_id'];
	        	} else {
	        		$parts = explode('_', $post['id']);
	        		$post_id = $parts[1];
	        	}
	        	        	
	        	// Try to find the attached blog post
			    $row = $wpdb->get_row("SELECT * "
			        				 ."FROM $wpdb->postmeta "
			        			 	 ."WHERE meta_key = '" . self::META_FACEBOOK_POST . "' "
			        				 ."AND   meta_value = '" . $post_id . "'");
			    
			    // Only go on if we found the post for which this comment was
			    if ($row->post_id > 0) {
			    	$comment_post = get_post($row->post_id);
			    	
			    	// Get all comments from facebook and iterate over all pages
			    	$comments = array();
			    	
			    	$page_link = '/' . $post_id . '/comments';
			    	do {
			    		$fb_result = $fb->api($page_link);
			    		$comments = array_merge($comments, $fb_result['data']);
			    	
			    		// Read all pages from facebook if any exists
			    		if (isset($fb_result['paging']['next'])) {
			    			$page_link = $fb_result['paging']['next'];
        		            $page_link = substr($page_link, strpos($page_link, '/', 9));
			    		}
			    	} while (count($fb_result['data']) > 0);
			    	
			    	// Iterate over all comments
			    	foreach ($comments as $comment) {
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
        if (isset($_POST['post_type']) && 'page' == $_POST['page_type']) {
            $post = get_page($post_id);
        } else {
            $post = get_post($post_id);
        }
        $errors = array();
		$success = array();
		
        if (intval(get_post_meta($post->ID, self::META_ENABLED, TRUE)) == '0') {
        	return;
        }
        
        // Share this now on all configured platforms
        if ($post->post_status == 'publish') {
        
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
            
            // Post on facebook.com
            $disableBitly = get_option(self::OPTION_FACEBOOK_DISABLE_BITLY, '');
            
            $words = preg_split('/[\s]+/', $post->post_content, NULL, PREG_SPLIT_DELIM_CAPTURE);
            $text = strip_tags(implode(' ', array_slice($words, 0, get_option(self::OPTION_FACEBOOK_DESCRIPTION, 20))));
            
            // Prepare the facebook parameters
            $facebook = array(
                'message'     => get_post_meta($post_id, self::META_FACEBOOK_TEXT, TRUE),
                'link'        => ($disableBitly == '1') ? $permalink : $bitlyUrl,
            	'name'	 	  => $post->post_title,
            );
            
            if (!empty($text)) {
            	$facebook['description'] = rtrim($text, '.') . '...';
            }
            
            $accessToken = get_option(self::OPTION_FACEBOOK_TOKEN, NULL);
            $postingType = get_post_meta($post_id, self::META_FACEBOOK_POST_TYPE, TRUE);
	    	
	    	// If no posting type was specified use the link type as default
	    	if (empty($postingType)) {
	    		$postingType = get_option(self::OPTION_FACEBOOK_POSTINGTYPE, self::FACEBOOK_POSTING_TYPE_LINK);
	    	}
            
	    	// Only support this when the posting type is set to 'status'
	    	if (in_array($postingType, array(self::FACEBOOK_POSTING_TYPE_PHOTO,
	    									 self::FACEBOOK_POSTING_TYPE_VIDEO))) {
	            // Add the correct picture for this status message or link
	            if ($picture = $this->_getPostPicture($post, $postingType)) {
	            	$facebook['source'] = '@' . $picture;
	            }
	    	}
	    	
	    	// Check if there is a default message
	    	if (empty($facebook['message'])) {
	    		$facebook['message'] = get_option(self::OPTION_FACEBOOK_DEFAULT, '');
	    	}
            
            if (!empty($facebook['message'])) {
                if (!empty($accessToken)) {
                	// Get an instance and set the corresponding access token
                    $fb = $this->_getFacebookInstance();
                    $fb->setAccessToken($accessToken);
                    
                    $appId          = get_option(self::OPTION_FACEBOOK_APPID, '');
                    $pageId         = get_option(self::OPTION_FACEBOOK_PAGEID, '');
                    
                    $profileId = 'me';
                    if (!empty($pageId)) $profileId = $pageId;
                    
                    try {
                    	// Share this post and save the post id in a meta field
                    	switch ($postingType) {
                    		case self::FACEBOOK_POSTING_TYPE_LINK:
                        		$fb_result = $fb->api('/' . $profileId . '/links', 'POST', $facebook);
                     			break;
                        		
                    		case self::FACEBOOK_POSTING_TYPE_STATUS:
                        		$fb_result = $fb->api('/' . $profileId . '/feed', 'POST', $facebook);
                    			break;
                    			
                    		case self::FACEBOOK_POSTING_TYPE_PHOTO:
                    			// Check if we shall create a new album for every image
                    			$create_album = get_option(self::OPTION_FACEBOOK_ALBUM_CREATE, 0);
                    			if (intval($create_album) == 1) {
                    				$fb_album_result = $fb->api('/' . $profileId . '/albums', 'POST', array(
                    					'message' => '',
                    					'name' => date('YmdHis'),
                    				));
                    				
                    				$endpoint = '/' . $fb_album_result['id'] . '/photos';
                    			} else {
                    				$endpoint = '/' . $profileId . '/photos';
                    			}
                    			
                    			$facebook['description'] = $facebook['message'];
								$facebook['name'] 		 = $facebook['message'];
								
                    			// Upload the new photo
                    			$fb->setFileUploadSupport(TRUE);
                        		$fb_result = $fb->api($endpoint, 'POST', $facebook);
                        		break;
                        		
                        	// Not yet supported
                    		case self::FACEBOOK_POSTING_TYPE_VIDEO:
                    			$fb->setFileUploadSupport(TRUE);
                        		$fb_result = $fb->api('/' . $profileId . '/videos', 'POST', $facebook);
                    			break;
                    	}
                    	
                    	$success['facebook'] = TRUE;
                        update_post_meta($post_id, self::META_FACEBOOK_POST, $fb_result['id']);
                    } catch(Exception $e) {
                        $errors['facebook'] = sprintf(__('Could not post on facebook.com. Reason: %1$s'), $e->getMessage());
                    }
                } else {
                    $errors['facebook'] = __('Could not post on facebook.com "AccessToken" missing');
                }
            }
            
            $twitter = array(
            	'message' => get_post_meta($post_id, self::META_TWITTER_TEXT, TRUE)
            );
            
            // Check if there is a default message
            if (empty($twitter['message'])) {
            	$twitter['message'] = get_option(self::OPTION_TWITTER_DEFAULT, '');
            }
            
            // Tweet on twitter.com
            if (!empty($twitter['message']) && $tw = $this->_getTwitterInstance()) {
                $seperator = get_option(self::OPTION_TWITTER_URL_SEPERATOR, NULL);
                if (empty($seperator)) $seperator = ' ';
                
                // Tweet this on twitter
                $tw_result = $tw->post('statuses/update', array(
                    'status' => $twitter['message'] . $seperator . $bitlyUrl
                ));
                
                // Check for any errors
                if (isset($tw_result->error)) {
                	$errors['twitter'] = $tw_result->error;
                } else {
                	// Save TweetID and user
                	$success['twitter'] = TRUE;
                	update_post_meta($post_id, self::META_TWITTER_POST, $tw_result->id_str);
                	update_post_meta($post_id, self::META_TWITTER_POST_USER, (!empty($tw_result->user->screen_name) ? $tw_result->user->screen_name : $tw_result->user->id));
                }
            }
            
            // Update the shared time
            if (count($errors) == 0) {
                update_post_meta($post_id, self::META_SHARED, date('Y-m-d H:i:s'));
            } else {
            	// If any did succeed mark this post as shared
            	if (count($success) > 0) {
                	update_post_meta($post_id, self::META_SHARED, date('Y-m-d H:i:s'));
            	}
            	
            	if (!defined('DOING_CRON')) {
	            	$this->_tpl->errors = $errors;
	            	$this->_tpl->success = $success;
	            	
	        		$this->_tpl->render('errors/share');
	            	exit;
            	}
            }
        }
    }
    
    /**
     * Retrieves a picture to share
     *
     * This method retrieves a picture to share to social networks for a
     * specified post
     *
     * @param object $post
     * @param string $type
     * @return string
     */
    protected function _getPostPicture($post, $type)
    {
        include_once(ABSPATH . 'wp-includes/post-thumbnail-template.php');
        
    	$picture = NULL;
    	$option_size = get_option(self::OPTION_SHARE_PICTURE_SIZE, self::DEFAULT_SHARE_PICTURE_SIZE);
    	
    	// Check which picture we should take
    	$option_share = get_option(self::OPTION_SHARE_PICTURE, self::PICTURE_THUMBNAIL);

    	if ($option_share == self::PICTURE_THUMBNAIL) {
    		$picture = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $option_size);
    	} elseif ($option_share == self::PICTURE_ATTACHMENT) {
    		// When set to 'attachment' we take the first available attachment
    		if (preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches)) {
	    		$image = parse_url($matches[1][0]);
	    		
	    		if (!empty($image['path'])) {
	    			$picture = array($image['path']);
	    		}
    		}
    	}
    	
    	if ($picture !== NULL) {
    		return $_SERVER['DOCUMENT_ROOT'] . $picture[0];
    	} else {
    		return FALSE;
    	}
    }

}

// Instantiate the admin class
$wasp_admin = new WordpressAutoSharePostAdmin();
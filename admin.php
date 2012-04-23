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

    const OPTION_AUTOENABLED             = 'wp-autosharepost-autoenabled';
    
    const OPTION_FACEBOOK_APPID          = 'wp-autosharepost-fb-appid';
    const OPTION_FACEBOOK_APPSECRET      = 'wp-autosharepost-fb-appsecret';
    const OPTION_FACEBOOK_PAGEID         = 'wp-autosharepost-fb-pageid';
    const OPTION_FACEBOOK_APPNAME        = 'wp-autosharepost-fb-appname';
    const OPTION_FACEBOOK_TOKEN          = 'wp-autosharepost-fb-token';
    const OPTION_FACEBOOK_DISABLE_BITLY  = 'wp-autosharepost-fb-disablebitly';
    
    const OPTION_TWITTER_CONSUMER_KEY    = 'wp-autosharepost-twitter-consumer-key';
    const OPTION_TWITTER_CONSUMER_SECRET = 'wp-autosharepost-twitter-consumer-secret';
    const OPTION_TWITTER_OAUTH_TOKEN     = 'wp-autosharepost-twitter-oauth-token';
    const OPTION_TWITTER_OAUTH_SECRET    = 'wp-autosharepost-twitter-oauth-secret';
    const OPTION_TWITTER_URL_SEPERATOR   = 'wp-autosharepost-twitter-oauth-url-seperator';
    
    const OPTION_BITLY_APIKEY            = 'wp-autosharepost-bitly-apikey';
    const OPTION_BITLY_LOGIN             = 'wp-autosharepost-bitly-login';
    
    const META_ENABLED                   = 'wp-autosharepost-enabled';
    const META_FACEBOOK_TEXT             = 'wp-autosharepost-fb-text';
    const META_TWITTER_TEXT              = 'wp-autosharepost-twitter-text';
    const META_SHARED                    = 'wp-autosharepost-shared';
    const META_BITLY_URL                 = 'wp-autosharepost-bitly-url';
    
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
        add_action('init', array(&$this, 'init'));
    }

    /**
     * Initialization
     */
    public function init()
    {
        $this->_tpl = new Template();
        $this->_tpl->wasp = $this;
        
        $this->_getFacebookInstance();

        add_action('admin_menu',          array(&$this, 'hookAdminMenu'));
        add_action('add_meta_boxes',      array(&$this, 'hookAddMetaBoxes'));
        add_action('save_post',           array(&$this, 'hookSavePost'));
        
        add_action('publish_post',        array(&$this, 'hookPublishPost'));
        add_action('publish_future_post', array(&$this, 'hookPublishFuturePost'));
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
            $this->_bitly = new Bitly();
            $this->_bitly->setApiKey(get_option(self::OPTION_BITLY_APIKEY, NULL));
            $this->_bitly->setLogin(get_option(self::OPTION_BITLY_LOGIN, NULL));
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
        add_meta_box('wp-autosharepost-text',
                __('AutoSharePost', WP_AUTOSHAREPOST_DOMAIN),
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
        $this->_tpl->twitterText  = get_post_meta($post->ID, self::META_TWITTER_TEXT, TRUE);
        $this->_tpl->shared       = get_post_meta($post->ID, self::META_SHARED, TRUE);
        
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
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
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
        /*
        add_menu_page('AutoSharePost', 'AutoSharePost', TRUE, 'wp-autosharepost', array(&$this, 'actionOverview'), NULL, 6);
        add_submenu_page('wp-autosharepost', 'Facebook Posts', 'Facebook Posts', TRUE, 'wp-autosharepost-facebook', array(&$this, 'actionFacebookPosts'));
        add_submenu_page('wp-autosharepost', 'Twitter Posts', 'Twitter Posts', TRUE, 'wp-autosharepost-facebook', array(&$this, 'actionTwitterPosts'));
        */
        
        add_options_page('AutoSharePost Settings', 'AutoSharePosts', TRUE, 'wp-autosharepost-settings', array(&$this, 'actionSettings'));

        /*
        global $menu;
        global $submenu;
        if ( isset($submenu['wp-autosharepost']) )
            $submenu['wp-autosharepost'][0][0] = __( 'Overview', WP_AUTOSHAREPOST_DOMAIN);
        */
    }

    /**
     * Action to handle settings
     *
     * This action handles the settings page.
     */
    public function actionSettings()
    {
        if (isset($_POST['submit'])) {
            // General options
            update_option(self::OPTION_AUTOENABLED,        $_POST['autosharepost']['enabled']);
            
            // Facebook options
            update_option(self::OPTION_FACEBOOK_APPID,         $_POST['autosharepost']['facebook']['app_id']);
            update_option(self::OPTION_FACEBOOK_APPSECRET,     $_POST['autosharepost']['facebook']['app_secret']);
            update_option(self::OPTION_FACEBOOK_PAGEID,        $_POST['autosharepost']['facebook']['page_id']);
            update_option(self::OPTION_FACEBOOK_DISABLE_BITLY, $_POST['autosharepost']['facebook']['disable_bitly']);
            
            // Twitter options
            update_option(self::OPTION_TWITTER_CONSUMER_KEY,    $_POST['autosharepost']['twitter']['consumer_key']);
            update_option(self::OPTION_TWITTER_CONSUMER_SECRET, $_POST['autosharepost']['twitter']['consumer_secret']);
            update_option(self::OPTION_TWITTER_OAUTH_TOKEN,     $_POST['autosharepost']['twitter']['oauth_token']);
            update_option(self::OPTION_TWITTER_OAUTH_SECRET,    $_POST['autosharepost']['twitter']['oauth_secret']);
            update_option(self::OPTION_TWITTER_URL_SEPERATOR,   $_POST['autosharepost']['twitter']['url_seperator']);
            
            // Bit.ly options
            update_option(self::OPTION_BITLY_APIKEY,       $_POST['autosharepost']['bitly']['api_key']);
            update_option(self::OPTION_BITLY_LOGIN,        $_POST['autosharepost']['bitly']['login']);
        }
        
        // These have to be loaded first, because we need them if we got back
        // from facebook and have to iterate over all pages/apps
        $appId          = get_option(self::OPTION_FACEBOOK_APPID, '');
        $pageId         = get_option(self::OPTION_FACEBOOK_PAGEID, '');
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
                        if ($app['id'] == $searchId) {
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
            'scope' => 'manage_pages',
            'display' => 'page'
        ));
        $this->_tpl->facebookBitlyDisabled = get_option(self::OPTION_FACEBOOK_DISABLE_BITLY, '');
        
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

    public function actionOverview()
    {
        $this->_tpl->render('overview/index');
    }

    public function actionFacebookPosts()
    {

    }

    public function actionTwitterPosts()
    {

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
                $bitly = $this->_getBitlyInstance();
                $bitly_result = $bitly->shorten(array(
                    'longUrl' => $permalink
                ));
                
                $bitlyUrl = $bitly_result->data->url;
                update_post_meta($post_id, self::META_BITLY_URL, $bitlyUrl);
            }
            
            $picture = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
            
            // Post on facebook.com
            $disableBitly = get_option(self::OPTION_FACEBOOK_DISABLE_BITLY, '');
            
            $params = array(
                'message'  => get_post_meta($post_id, self::META_FACEBOOK_TEXT, TRUE),
                'link'     => ($disableBitly == '1') ? $permalink : $bitlyUrl,
                'caption'  => $post->post_title,
                'picture'  => $picture[0]
            );
            
            if (empty($error)) {
                if (!empty($facebook['AccessToken'])) {
                    $fb = $this->_getFacebookInstance();
                    $fb->setAccessToken(get_option(self::OPTION_FACEBOOK_TOKEN, NULL));
                    
                    try {
                        $fb->api('/' . $appId . '/feed', 'POST', $params);
                    } catch(Exception $e) {
                        $error = sprintf(__('Could not post on facebook.com. Reason: %1$s'), $e->getMessage());
                    }
                } else {
                    $error = __('Could not post on facebook.com "AccessToken" missing');
                }
            }
            
            // Post on twitter.com
            if (empty($error)) {
                $seperator = get_option(self::OPTION_TWITTER_URL_SEPERATOR, NULL);
                if (empty($seperator)) $seperator = ' ';
                
                $tw = $this->_getTwitterInstance();
                $tw->post('statuses/update', array(
                    'status' => get_post_meta($post_id, self::META_TWITTER_TEXT, TRUE) . $seperator . $bitlyUrl
                ));
            }
            
            if (empty($error)) {
                update_post_meta($post_id, self::META_SHARED, date('Y-m-d H:i:s'));
            }
        }
    }

}

// Instantiate the admin class
$wasp_admin = new WordpressAutoSharePostAdmin();
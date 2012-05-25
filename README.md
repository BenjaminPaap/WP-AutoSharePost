WP-AutoSharePost
================
A wordpress plugin to automatically share posts via twitter and facebook with a
CommentGrabber to grab all comments posted on a facebook post which is connected
with a blog post.

Installation
------------
To install this plugin in your working wordpress installation just download all
files and put them in a new folder e.g.: /wp-content/plugins/wp-autosharepost/

Getting Started
---------------
Just follow these steps to get things working for each of these social networks

### Facebook
1. Visit https://developers.facebook.com/apps and create a new application
2. Visit http://[YOUR_DOMAIN]/wp-admin/options-general.php?page=wp-autosharepost-settings
3. Copy and paste the "App ID" and "App Secret" shown on facebook for your application 
   in the corresponding fields on the plugin settings page
4. If you want to administrate a page then you can also provide the plugin with 
   your your "Page ID"
5. Save the settings
6. After saving request a new "Access Token" with the "Request token" Link on
   the plugin settings page
7. Your Facebook App will now ask for access to manage your page. Grant it and 
   you're ready to share posts on Facebook.

#### CommentGrabber
1. To enable the Facebook CommentGrabber just visit the plugin settings page under
   http://[YOUR_DOMAIN]/wp-admin/options-general.php?page=wp-autosharepost-commentgrabber
2. Check "Enable CommentGrabber" to enable the CommentGrabber.
3. Specify an interval in which the CommentGrabber checks for new comments on
   Facebook.
4. Check the "Auto-Approve new Comments" Option to approve every comment which 
   comes from Facebook directly.
5. Because the CommentGrabber uses the Wordpress Cron it is highly recommended, 
   that you switch to a normal cron before using the CommentGrabber. If you do 
   not your users may be interupted while trying to access your page. Some 
   Facebook requests may take some time to finish.

### Twitter
1. Visit https://dev.twitter.com/apps/new and create a new app
2. Click on the "Settings"-Tab and locate "Application Type". Here you have to
   change the "Access"-Level to "Read and Write". Save these settings.
3. Go back to the "Details"-Tab and click "Create my access token" at the end
   of the page.
4. Visit http://[YOUR_DOMAIN]/wp-admin/options-general.php?page=wp-autosharepost-settings
5. Now copy and paste all needed settings. You need the "Consumer key", 
   "Consumer Secret", "Access token" and "Access token secret" to tweet your posts


Feedback
--------
Any feedback, comments or improvements are welcome. Feel free to contact me at
benjamin.paap@googlemail.com

If you have found any bugs or want to support this project with your bugfixes feel
free to fork this project and do a pull request. I will then review your changes
and merge them.

Future Plans
------------
As soon as Google releases their API for Google+ I will implement a possibility
to share posts to Google+ as well.

If you have any whishes for more social networks/platforms to be included feel 
free to contact me at benjamin.paap@googlemail.com

Plugin Homepage
---------------
The plugin can also be found under http://benjaminpaap.de/entwicklungen/wp-autosharepost/

Special Thanks
--------------
Special thanks go out to all employees at Checkdomain GmbH visit www.checkdomain.de
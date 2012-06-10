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

### Twitter
1. Visit https://dev.twitter.com/apps/new and create a new app
2. Click on the "Settings"-Tab and locate "Application Type". Here you have to
   change the "Access"-Level to "Read and Write". Save these settings.
3. Go back to the "Details"-Tab and click "Create my access token" at the end
   of the page.
4. Visit http://[YOUR_DOMAIN]/wp-admin/options-general.php?page=wp-autosharepost-settings
5. Now copy and paste all needed settings. You need the "Consumer key", 
   "Consumer Secret", "Access token" and "Access token secret" to tweet your posts

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
   
#### Bit.ly
In order to support URL shortening for your twitter links you should enable bit.ly 
by supporting the plugin with some valid login details.

1. Visit [bitly.com](https://bitly.com/) and sign up. If you are already registered
   log in now.
2. Enter your bit.ly username in the corresponing field in the plugin settings 
   page.
2. Visit http://bitly.com/a/account and scroll down until you reach the "**API Key**"
   headline. Copy and paste this code to the plugin settings page.

Feedback
--------
Any feedback, comments or improvements are welcome. Feel free to contact me at
b.paap@checkdomain.de

If you have found any bugs or want to support this project with your bugfixes feel
free to fork this project and do a pull request. I will then review your changes
and merge them.

Future Plans
------------
As soon as Google releases their API for Google+ I will implement a possibility
to share posts to Google+ as well.

If you have any whishes for more social networks/platforms to be included feel 
free to contact me at b.paap@checkdomain.de

Changelog
---------
This is the plugin changelog from the beginning with version 0.1.1

### 0.3 (development)
Would like to hear your thoughts about my plans developing this plugin further. 
Feel free to contact me and discuss any features with me.
 
 - PLANNED: Create a widget to show every facebook like for your shared post like
            other twitter widgets do. Maybe a twitter widget too, so that you don't
            need to install another plugin. 
 - PLANNED: Wordpress Comment Postback to Facebook. Users can request an access
            token in their user profile, so that if they answer with a comment to
            a post this comment is automatically posted to the shared facebook post.

### 0.2.4
 
 - ADDED: An option to read more pages with the comment grabber.
 - FIX: Login issue with facebook
 
### 0.2.3

 - ADDED: Default messages for facebook and twitter. If auto sharing is enabled in
          a wordpress post the plugin will look for default messages. If they are
          set the post will be shared.

### 0.2.2

 - ADDED: Support for different posting types on facebook.com. You can now choose
          between 'status', 'link', 'photo'. Currently unsure if 'video' will also
          be supported in the near future.
 - ADDED: Posting type can be set in options as default but can be changed in 
          every article you create.
 - ADDED: Options to choose from different picture types and sizes to share.
 - ADDED: Comments from shared photos will be grabbed as well.
 - ADDED: Option to create a new album with every shared picture. Be carefull with
          this option because I don't know what facebook will do if you create many
          albums. This is an option to prevent facebook cumulating your pictures.
 - FIX: Icons in post will only show up, if post was really shared on this social
        network. Before both icons were shown if the post was shared on any network.
 
### 0.2.1

 - ADDED: German language files
 - ADDED: Option to specify what type of message to share to facebook (link/status message)
 - FIX: When a Blogpost is created and directly published no messages were shared 
   to social networks. 

### 0.1.1

 - Initial Release of this plugin

Information for Theme Designers
-------------------------------
If you want to support this plugin in your theme and give facebook comments a 
special appeareance you can check with something like this, if the current 
comment is a facebook comment:

`<?php if ($comment->comment_type == 'facebook'): ?>`

Plugin Homepage
---------------
The plugin can also be found under http://blog.checkdomain.de/autosharepost-wordpress-plugin/

Special Thanks
--------------
I want to thank all my colleageus at http://www.checkdomain.de/ who
supported me to create this Wordpress-Plugin
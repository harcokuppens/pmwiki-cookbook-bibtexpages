<?php if (!defined('PmWiki'))
    exit();



#-----------------------------------------------
#  authentication
#-----------------------------------------------
# doc: http://www.pmwiki.org/wiki/PmWiki/AuthUser


# enable user authentication in pmwiki
# ------------------------------------
# Note: users and groups must be defined before including authuser.php because otherwise they are ignored!
# set fixed hidden user accounts 
$AuthUser['admin'] = crypt('admin', 'somesalt');
$AuthUser['testuser'] = crypt('testuser', 'somesalt'); #   
# add people to admin group as follow:
$AuthUser['@admins'][] = 'admin';  # local 'hidden' pmwiki account

# -> immediately authenticates user
#      * either checks if authentication with saml already succeeded
#      * or does local authentication with local user/passwd file
include_once("$FarmD/scripts/authuser.php");


#-----------------------------------------------
#  authorization
#-----------------------------------------------
# doc: https://www.pmwiki.org/wiki/PmWiki/PasswordsAdmin

# sets DEFAULT authorization rules TO:
#  * everone : read permission
#  * authenticated users: read+write permission
#  * people in admin group :  all permissions
$DefaultPasswords['read'] = ""; # empty means : everybody allowed
$DefaultPasswords['admin'] = array('@admins');
$DefaultPasswords['attr'] = array('@admins');
$DefaultPasswords['edit'] = 'id:*';         # special syntax to allow logged in users
$DefaultPasswords['upload'] = 'id:*';

#-----------------------------------------------
#  clean urls
#-----------------------------------------------
# https://www.pmwiki.org/wiki/Cookbook/CleanUrls#URL_rewriting
# A) we use cleanurls to change url to right file
# 1. htaccess files rewriting  (one in /.htaccess redirecting top /pmwiki/, and one in /pmwiki/.htaccess rewrite as pmwiki.php argument)
#     https://localhost/Group/Page to file .../pmwiki/pmwiki.php?n=Group/Page .
# B) then we use the following to change the generated urls in  html output
# 1. changing page url used in generated content from .../pmwiki.php?n=Main.Main  to .../pmwiki.php/Group/Page instead of 
#     https://www.pmwiki.org/wiki/PmWiki/LayoutVariables#EnablePathInfo
#       Changes the handling of the page URL. When set to 1 page URL will be .../pmwiki.php/Main/Main, when set to 0 (default) it will be .../pmwiki.php?n=Main.Main .
$EnablePathInfo = 1;
# 2. strip for url used in generated content the script name pmwiki.php and the subdir pmwiki
# changing the generated url from https://localhost/pmwiki/pmwiki.php/Group/Page  to url https://localhost/Group/Page
$ScriptUrl = dirname(dirname($ScriptUrl));


#-----------------------------------------------
#  debug/info 
#-----------------------------------------------
#
# http://www.pmwiki.org/wiki/PmWiki/DebugVariables
$EnableDiag = 1;  # disable(0) in production
## The following actions are available only if you set $EnableDiag = 1;
#   ?action=ruleset  : displays a list of all markups
#   ?action=phpinfo  : phpinfo()
#   ?action=diag     : displays a dump of all global vars

#-----------------------------------------------
#  images and uploads
#-----------------------------------------------

$EnableUpload = 1;

# note: I set upload max size by default to big value but most off the times
#       the config values in php.ini  will limit it down to a lower value ( see 
#       further below)
#$UploadMaxSize =  8388608; # limit upload file size to 8 megabyte
$UploadMaxSize = 31457280; # limit upload file size to 30 megabyte
#$UploadPrefixFmt = '/$Group/$Name'; 
$UploadPrefixFmt = '/$Group';

# to allow larger uploads set in php.ini 
#
#     upload_max_filesize = 30M
#     post_max_size = 31M
#
# src: https://www.a2hosting.com/kb/developer-corner/php/using-php.ini-directives/php-maximum-upload-file-size
# To ensure that file uploads work correctly, the post_max_size directive should
# be a little larger than the upload_max_filesize. 

# extra images which can be uploaded; IMPORTANT: also add to $ImgExtPattern for in web page viewing of image
$UploadExts['svg'] = 'image/svg+xml';
$UploadExts['svgz'] = 'image/svg+xml';


# following types are regarded as images
$ImgExtPattern = "\\.(?:svgz|svg|gif|jpg|jpeg|png|SVGZ|SVG|GIF|JPG|JPEG|PNG)";

#-----------------------------------------------
#  cookbook RecipeInfo
#-----------------------------------------------

# https://www.pmwiki.org/wiki/Cookbook/RecipeInfo
# 
# in page you need to define a wiki style:
#
#   %define=recipeinfo color=black background-color=#f7f7f7 border='1px solid #cccccc' padding=4px%
#
# in config you need to define:
Markup(
    '^Property:',
    'block',
    '/^([A-Z][-\\w]+):(\\s.*)?$/',
    '<:block,0><div class=\'property-$1\'>$0</div>'
);
# then you can use  markup for describing cookbooks with syntax 
//   >>recipeinfo<<
//   Summary: How to create a recipeinfo box like it is used in the cookbook
//   Version: 2007-02-26
//   Prerequisites: pmwiki
//   Status: stable
//   Maintainer:
//   Categories:
//   (:if exists {$Name}-Talk:)Discussion: [[{$Name}-Talk]](:if:)
//   >><<


#-----------------------------------------------
#  cookbook  
#-----------------------------------------------
@include_once("$FarmD/local/includecookbook.php");

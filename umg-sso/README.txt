Plugin name:	UMG SSO Plugin for WordPress v1.0.1

Written By: 	P.Fernihough, Push Entertainment Ltd., paul@pushentertainment.com
Date: 		2011-07-06

Tested on: 	WordPress 3+
Requires: 	cURL library 7.19.4+



Installation Instructions


1) Copy the "umg-sso" directory to your wordpress plugins directory


2) Activate the UMG-SSO plugin in the WP Admin backend - you will now have "UMG Single Sign On" menu option in the "Settings" menu


3) Enter the basic details:
		ClientID		- obtainable from
		Client Secret		- obtainable from 
		Opt-Ins Bundle		- obtainable from
		Profile Page 		- this should be a the page name where the Capture Profile page will be shown (relative URL)
		Logout Redirect		- this should the page you wish to redirect the user to on Logout (relative URL)


4) Place the following shortcode on any page you wish to have the Sign/Sign Out link:

		[umg-sso]

   Alternatively you can add the shortcode directly to your PHP theme template files by using the following:

		<?php echo do_shortcode('[umg-sso]'); ?>


5) Place the following div on the page you wish to show the profile (this can either go into the WordPress page editor or directly inside your PHP theme template files):

		<div id="captureProfile"></div>


6) Include the stylesheet and images inside your theme - these can be modified to suit your needs:

		your_theme_folder/umg_sso_styles.css
		your_theme_folder/images/bg_sso_loggedin.jpg
		your_theme_folder/images/bg_sso_loggedout.jpg
		your_theme_folder/images/sso_icons.png


	
That's it.
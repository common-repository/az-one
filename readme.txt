=== AZ-One ===
Contributors: 52lives
Donate link: http://www.52lives.com
Tags: admin, amazon, associate, affiliate, store, locator, geotargeted, ads, marketing
Requires at least: 2.1
Tested up to: 2.6
Stable tag: 1.1

AZ-One locates the Amazon store closest to your visitor and changes the associate links on your blog to direct the visitor to the right store.

== Description ==

AZ-One locates the Amazon store (com, ca, co.uk, de, fr, jp) closest to your visitor and changes the associate links on your site to direct the visitor to the right store. And that means more referral fees in your pocket!

The basic problem is that if the visitor clicks the associate link in the post, likes the product but orders it from another Amazon site, the associate won't get the referral fee. So it is beneficial to direct the visitor to the Amazon site that he would most likely buy from.

If you have for example created a link to Amazon.com in your post and you have a visitor from Europe, the plugin will change the Amazon.com link to Amazon.co.uk or some other European site (de, fr). The plugin supports directing visitor's to Amazon com, ca, fr, de, co.uk and jp. There are 234 countries in the plugin's database and you can choose which Amazon store is shown to a visitor from each of those countries.

== Installation ==

1. Upload AZ-One folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the Options menu and choose AZ-One.
1. Install country and IP address information to the database. It is recommended that you choose one file at a time and press the Update options button. After you have succesfully installed a file, it disappears from the install list.
1. Add your Amazon associate IDs. There are 6 stores available: com, co.uk, ca, de, fr and jp. Fill in your IDs. If you don’t have all the IDs, you can clear the field. Visitors from that store’s area will be shown the original link in the post. 
1. The last section is for country configuration. You can select the approriate store for every country listed in the database.

== Frequently Asked Questions ==

= Does AZ-One change the links I have in my blogroll? =

At the moment AZ-One only changes the links which are in your posts, i.e. are affected by the filter 'get_content'.

= The only country showing in the Store Configuration section was AFGHANISTAN. Any ideas? =

Yes :) There was an extra quotation mark in the HTML code. It's removed in the version 1.1.


== How does it work? ==

Once AZ-One is installed and configured, it works pretty much automatically. It changes the links which are identified to be Amazon associate links.
1. Every link with www.amazon.com|co.uk|ca|de|fr|jp is changed. The country domain is changed according to the visitor's country and the settings the admin has made in the AZ-One's option page.
1. Every link found in the previous line is searched for 'tag' parameter. This parameter tells the associate id. AZ-One uses the associate id which is related to the store it changed on the previous line. If the associate id for the store is left blank, the link is not modified and the user sees the original link which was written to the post.
1. AZ-One also handles redirect.html in the links.
1. Image links (img) which have assoc-amazon.com|co.uk|ca|de|fr|jp in them are changed to the correct country domain.
1. Image links might contain parameter 'o'. This parameter controls the language used in the image. AZ-One changes this one based on the store.
	
== More Info ==

For more info, please visit [AZ-One's info page at 52Lives.com](http://www.52lives.com/downloads).

For feedback/comments, please send an e-mail to: harri dot lammi at 52lives dot com
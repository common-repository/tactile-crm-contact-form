=== Tactile CRM Contact Form ===
Contributors: paulmbain
Donate link: http://www.paulbain.co.uk/development/donate/
Tags: CRM, contact, form, Tactile

Requires at least: 1.5
Tested up to: 2.8.2
Stable tag: 1.3.3

This plugin allows you to easily push contact form information from Wordpress into your Tactile CRM account. Tactile CRM is a simple, easy to use [CRM for small businesses](http://www.tactilecrm.com/a-small-business-crm/) and you can signup for free at www.tactilecrm.com using the code WORDPRESS. 

The new version tries to identify existing organisations and contacts before creating new ones.

== Installation ==

1. Copy the entier tactile folder into your wordpress plugins folder, normally located
   in /wp-content/plugins/

2. Login to Wordpress Admin and activate the plugin

3. Login to your Tactile CRM account as an admin, go to the admin link at the top right of the page and select "Toggle API access". In preferences click Show "API, Calendar, & Email Dropbox Access" and copy the API key.

4. Go to settings and enter your Tactile CRM API token and your site address.

A contact form can be added by placing the following tag in an post of page:

{tactile_contact}

You can also add tags to the items inserted into Tactile CRM using the following tag format:

{tactile_contact tag1,tag2,tag3}

== Screenshots ==
1. Example contact form.

== Support ==
For support or customization please email support@paulbain.co.uk and I will try to help.

== Changelog ==

= 1.3 =
* Added basic spam protection
* Added the ability to tag posts using the format {tactile_contact tag1,tag2,tag3}
* Included Person's name in activity name
* Included page/post title in note body

= 1.3.1 =
* Changed directory structure to stop the auto install process breaking

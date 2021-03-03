# RgpdTools GLPI plugin

This plugin offer tools to manage RGPD user informations.
With this plugin you can :

* Export user information and information about linked elements
* Delete Link between one user and it's linked elements
* Purge User information on history logs

## Installation

Before installing the plugin, check that you have the php zip extension installed

* Copy the rgpdtools folder to the glpi plugin folder
On console : 
* Run "php composer.phar update" in the plugin tree
* Run "php bin/console glpi:plugin:install rgpdtools" in the glpi tree
* Run "php bin/console glpi:plugin:activate rgpdtools" in the glpi tree

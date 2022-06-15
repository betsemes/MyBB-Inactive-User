# Inactive User
Version: 0.1.1-alpha.1

A plugin for MyBB 1.8.30 to identify and manage users that have gone inactive.

The Inactive User plugin version number conforms with [Semantic Versioning 2.0.0](https://semver.org/) standard. It is being developed on PHP 7.3.7, which may cause issues to users runnning MyBB on PHP 8.

### Features
* Identifies users that have gone inactive. The amount of days after last visit can be configured.
* Inactive users are easily recognized across the forum through the username color and the "Inactive" usertitle.
* The user can instantly become active again by just logging back in.

### Installation
* Upload the contents of the **/inc** folder to your forum's **/inc** folder.
* Go to **Configuration \>\> Plugins**	and click on **Install & Activate**.

### Changes for version 0.1.1-alpha.1
* Documentation included with the plugin distribution.
* A few unreported bugs were caught and fixed.

### Changes for version 0.1.1-alpha
* Backslash bug fixed
* The "Keep Inactive Users Data" setting was set to **no** by default so that the inactive users table is deleted on uninstall.

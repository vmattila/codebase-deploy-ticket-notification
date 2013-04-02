codebase-deploy-ticket-notification
===================================

This is simple PHP script adding notifications to [Codebase](http://codebasehq.com/) tickets when a commit is deployed to the server with Deploy HQ. The script is originally just an internal tool in my company [Eventio Oy](http://www.eventio.fi/). You are completely free to edit, update and use this script. If you do enchancements or fix some bugs, feel free to feed some pull requests to share your code with us and others.

Setup & Usage
-------------

* Copy `configuration.inc.php.sample` to `configuration.inc.php`
* Copy the configuration file and `deploy-notification.php` to your web directory so the deploy-notification.php is accessible with an HTTP call. Protect them as you like, by example limiting the access only to Deploy HQ servers and authentication.
* Make a clone of your Codebase repository to the same server (bare repository is enough). Ensure that you can do `git fetch` to update the local repository with the changes in codebase.
* Modify the variables in the configuration file, each variable has a short comment explaining what it does.
* [Add an HTTP notification](http://support.deployhq.com/kb/advanced-settings/setting-up-notifications) to your Deploy HQ project to call the `deploy-notification.php` in your server.

How does the script work?
-------------------------

* The script retrieves an HTTP POST request from Deploy HQ, with `payload` including json-formatted details about the deployment.
* `payload` includes details `start_revision`, `end_revision` and `servers`, these details are extracted from the json
* The script updates the local git repository with the latest revisions from Codebase HQ. This is done with `git fetch` command, so proper public keys should be set up.
* After the local git repository is up-to-date, the script gets all commit messages between `start_revision` and `end_revision`
* String `[completed:1234]` is searched from the commit messages for linked tickets. 1234 is the ticket id in Codebase. [Read more](http://support.codebasehq.com/articles/tips-tricks/linking-commits-with-tickets)
* Each matched ticket is updated with a configurable message and optionally a new status via Codebase API. [Read more about Codebase Ticket API](http://support.codebasehq.com/kb/tickets-and-milestones/updating-tickets)

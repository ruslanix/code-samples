EdmMailChimpBundle
============

mailchimp.com - is an EDM tool. Email marketing tool that helps to subscribe users, create/send personalized emails and get advanced analytics and reports.

EdmMailChimpBundle is an integration with mailchimp.com REST API.
I uploaded only extracted part of the bundle with key files.

As an entry point you may look at EdmMailChimpBundle\Command\SyncSiteUsersCommand.php - this command synchronize newly registered users from our db with mailchimp db.
Approximate workflow:
- user registered on our website
- EdmMailChimpBundle\Event\Listener\UserListener.php intercept registration and add user to queue for synchronization (EdmMailChimpBundle\Entity\EdmSyncUserQueue.php)
- EdmMailChimpBundle\Command\SyncSiteUsersCommand.php executed by cron and call EdmMailChimpBundle\Service\Workflow\SyncUserQueue::processSiteQueue that select users for synchronization and synchronize them using wrappers above REST API (EdmMailChimpBundle\Service\Api).

Interesting things:
- EdmMailChimpBundle\Event\Listener\UserListener.php - doctrine event listener
- EdmMailChimpBundle\Validator - some custom validators
- EdmMailChimpBundle\Monolog\Processor\WorkflowDataProcessor - custom Monolog processor that add extra data that will be logged

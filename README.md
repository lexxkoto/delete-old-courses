# Delete Old Courses Script

## Introduction

This is a PHP command line script that uses Moodle web services to find and delete old courses from a Moodle site. It has several safety checks to make sure it doesn't accidentally delete a course that people are still using. 

To run this script, you will need:

* A Moodle site with Web Services enabled, and a web service and token set up for the script to use. Instructions are below.
* An administrator account that will be used to run the script. It's best to use a dedicated manual account rather than a real person's account.
* A way to generate a CSV of courses you want to consider as candidates for deletion. You can usually query the `mdl_course` database table to get this.
* A computer with the command-line PHP installed. On Linux, you usually want to install the `php-cli` package. On MacOS, you can use [HomeBrew](https://brew.sh/) to install PHP. This script was tested in PHP 8 but any recent version should work.

## Configuring Your Site

First, you want to enable web services. Log in to Moodle as an administrator, and go to **Site Administration**. Under the  **General** tab, click **Advanced Features** and check the **Enable web services** box.

Now, we need to set up a web service that the script can use to talk to Moodle. On the **Server** tab, click **External Services**. This will show a list of any web services you have already set up.

Click the **Add** button at the bottom of the page. Give your new web service a useful name and shortname - I usually use something like "Old Course Deletion" and "old_courses". Make sure the **Enabled** and **Authorised users only** boxes are checked, then click **Add service**.

Now, you'll see a page that lets us choose which web service functions our new service is allowed to use. Click the **Add Functions** link, then use the search box to add these functions:

* `core_course_delete_courses`
* `core_course_get_courses`
* `core_course_get_updates_since`
* `core_enrol_get_enrolled_users_with_capability`

Once you're done, click the **Add Functions** button.

Now we have set up our web service, we'll give our admin account access to use the web service.

Click the **Server** tab, and go to **External Services** again. Click the **Authorised Users** link. Use the search box to find your user, and add them to the list of authorised users.

Now our user has permission to use the web service, we'll generate a token for them. A token is used instead of a password to call web services, so it's important to keep your token a secret.

Click the **Server** tab at the top of the page, then click **Manage Tokens**. Click the **Create token** button at the top of the page. Use the search box to find your user, then make sure your service is chosen in the next box. If you want, you can set an expiry date on the token or limit it to certain IPs. This can be useful for security. Click the **Save changes** button, and your token will be shown in the list of tokens.

## Getting a List of Courses

This script works by going through a CSV of courses. If you have database access, you can export a list of courses from the `mdl_course` database table. You can use whatever criteria you want to try and identify old courses - the start date, end date, or a year in the shortname.

This script only needs the ID number of the course you're interested in - you can include other fields if you want and the script will just ingore them.

## Preparing the Script

When you download the script, you will get three PHP files. `rest.php` and `functions.php` just contain behind-the-scenes code that helps the script talk to Moodle, and you shouldn't need to touch them.

The important code is in `delete-old-courses.php`. Open it in a text editor.

At the top of the file, you'll find a few settings you can change. Below these settings, you'll find the code that actually finds, checks and deletes the courses.

At the top of the file, you'll find space to put your Moodle site address and the token you generated earlier:

```
$config['site']     = 'https://moodle.something.ac.uk';
$config['token']    = '74833e911605ee374986da0e41874371';
```

There are two other config options you can set - whether you want the script to continue or stop if it encounters an error, and whether you want to enable test mode. When test mode is enabled, the script will tell you which courses it wants to delete, but it won't actually delete them.

## Enabling Safety Checks

The script has built-in safety checks. If you disable all of them, it will delete every course in the CSV. If you enable them, the script will perform extra checks before it deletes a course. You can disable these checks by setting any of them to `false`.

```
$config['check-time-created']  = true;
$config['check-time-modified'] = true;
$config['check-start-date']    = true;
$config['check-end-date']      = true;
$config['check-for-students']  = true;
$config['check-for-changes']   = true;
```

Below the rules, you will find two dates called the **safety date** and the **modified date**. These are used by the safety checks to check the age of a course, and when it was last changed.

```
$safetyDate = strtotime("2016-08-01 00:00:00");
$modifyDate = strtotime("2021-08-01 00:00:00");
```

The course creation time, start date and end date are checked against `safetyDate`. The date the course was last modified, and whether there have been any recent changes, are checked against `modifyDate`.

In the example above, the script will only delete courses that were created before August 2016, and whose start date and end date are both before August 2016. It will not delete any course if someone has changed a setting or added an activity or resource after August 2021.

## Setting up the  CSV

Get your CSV of courses, and put it in the same folder as the PHP scripts. Make sure it is called `courses.php`.

Now, we need to tell our script which fields of the CSV it is interested in. Inside the script, you will find this setting:

```
$idField = 0;
```

This setting tells the script which field of the CSV file contains the course ID number. Note that the numbers start with 0 and not 1. So if your course ID is the fifth field of your CSV file, you should put 4 here, not 5.

## Running the Script

Now, we're ready to run the script. If you're running it for the first time, you should make sure ``$config['test']`` is set to ``true``. This will make the script tell you which courses it wants to delete, but it won't actually delete them.

Open a terminal and change to the folder where your scripts are located. Type this to run the script:

``php delete-old-courses.php``
README - ScaleXtreme Cloud Portal V1

Copyright (c) 2012, ScaleXtreme, Inc.
All rights reserved.

OVERVIEW

This code distribution contains a sample cloud portal written in PHP that uses the ScaleXtreme REST API's. It can be used as-is if you are looking for a quick and simple portal and can incrementally modify and change it. Of course, you can also use this as a guide to use another language to build your own portal or desktop application to access your information.

The best way to setup and use this is to:

- Register for a free ScaleXtreme account (just go to http://www.scalextreme.com/free)
- Setup a few servers under management - these servers can be anywhere - inside your enterprise or in the public cloud at Amazon EC2 or other providers.
- Setup a web server (Apache recommended) and drop the PHP code in the DocumentRoot directory
- Get some pieces of information from your ScaleXtreme account (you'll just need a few) and update a few files
- You're done - you have all your servers being monitored and managed, and have a running cloud portal that you can modify and change as you please.

INSTALLATION INSTRUCTIONS - EASY VERSION TO GET THINGS UP AND RUNNING QUICKLY

1. The easiest way to get the code running is to download and put the code in the "root" directory of your web server. For Apache, this is the "DocumentRoot" directory.

Note - Make sure that you have downloaded all the files and created all the directories. In some cases you might overlook certain files like ".htaccess" which might not show up in your ftp or file browser. The best way to ensure this on Linux is to run a "ls -la" command.

2. Make sure that you set the right permissions on the directories - in particular, the "data" and "temp" directories need to be writable. On Linux, the command would be:
prompt> chmod 0777 data temp

3. If you have your web server set up correctly to run PHP, you should be able to just type in the URL to the root of the web server and the Dashboard of the portal should show up. If it does, proceed to step 4. If not, then you will have to troubleshoot and make sure that your web server is setup correctly, your web server can run PHP, and that your .htaccess and other files are set up correctly. You might want to skip to the next section for more Advanced Installation.

4. If the web server is running and you are able to get to the first page of the portal, then click on "Settings" in the top navbar where we will enter some information that will allow us to connect to the ScaleXtreme cloud service and your account. Alternatively, you can edit the "data/config.txt" file with your ScaleXtreme account information.

Let's go through each item and what to do here. We will also list the names of the entries in the config file that you can update if you like.

Company ID (company_id in the config file): This is the ID of your company's ScaleXtreme account. You can find this information by logging into your ScaleXtreme account, and find the icon that looks like a person in the top right side of the navbar (it will be the second icon from the right). Click that icon and look for "Account Details". That will open up a new window with your account information on it - the panel should say "My Account" on the left. On the left navbar, click on "My Organizations", which will list the organizations you have created and have under management. Most customers will just have one. Click on the organization, and in the URL, you will find the ID at the end of the URL:
	https://store.scalextreme.com/actmgnt/useraccounts/organization/index/id/xxyyzz/
Just type this company_id into the config file.

- Company Name (company_name in the config file): This is the name of the organization you created when you signed up for your account. This will also be the name that you see listed when you click on "My Organizations". The name should match exactly.

- Role (role in the config file): This is typically "Admin", unless you have created other roles or want the API to specifically authenticate to the service with another role.

- API Key (client_id in the config file): This value will be listed on the "My Account"/"Overview" page under "API Codes". client_id is the value listed with "Api Key".

- Secret Password (client_secret in the config file): This value will be listed on the "My Account"/"Overview" page under "API Codes". client_id is the value listed with "Secret Password".

- Company Name (branding_company_name in the config file): You can pretty much type any company name you like here which will show up in the portal UI. Does not have to match the company_name.

- Company Logo (branding_company_logo in the config file): You can upload a logo that will be used for the portal. You should put your logo into the directory "ui/assets/logos". It will show up in the dropdown 

- Title (branding_company_title in the config file): You can change the title of the portal page.

There are two variables in the config file that are not shown through the UI that you might want to ignore unless you are doing more advanced setups:

- base_dir: The default value that we included here is "/". You should leave this alone for now.

- domain: The default value that we included here is "manage.scalextreme.com". You should leave this alone for now.

INSTALLATION INSTRUCTIONS - ADVANCED

If, on the other hand, you put the files in a subdirectory, there are a couple of them that you will need to modify to reflect that:
1) data/config.txt
2) .htaccess
 
For the data/config.txt file:
This is a series of configuration options.  The first one is the base_dir value.  This needs to be set to the directory you're working in, with a trailing slash.  For instance, if you installed in the /template-launch directory, the first line of data/config.txt should look like this:
base_dir:/template-launch/
Notice that there's a slash after the directory name and that there's no space after the colon.  Also, if there's anything still on that line (including a comment), remove it.
 
For the .htaccess file:
Set the RewriteBase directory to the same as the base_dir parameter.  For example:
RewriteBase /template-launch/
 
Also, make sure that the data and temp directories and data/config.txt file have permissions of 0777.
 
In rare circumstances, you may find that the links from the home page to the settings or other pages don't work.  A possible reason for this is that the Web server is ignoring your .htaccess file.  You can verify whether this is happening by editing the .htaccess file and adding something like "Hello World" at the top.  If you can still load the home page without getting a server error, then the server appears to be ignoring the .htaccess file.  If this happens, your server administrator will need to intervene.

GETTING YOUR HANDS DIRTY - IF APACHE CONFIGURATION NEEDS ADJUSTING

What the server administrator needs to do:

Note that whoever does this will probably have to have root access.

Open the Apache configuration file (httpd.conf), and make sure the server is configured to process .htaccess.  Pay attention to two directives in particular: AccessFileName and AllowOverride.  NOTE:  These two directives will probably not appear near one another in the configuration file.  Also, there will probably be multiple instances of the string AllowOverride in the file.  The one to change should have a comment above it indicating that it pertains to .htaccess files.  (For instance, the first line of the comment might say, "AllowOverride controls what directives may be placed in .htaccess files.")

A possible reason for the .htaccess file being ignored is that AllowOverride is set to None.  If this is the case, simply change the setting to All.  Here's what you want the directive settings to look like:

AccessFileName .htaccess

AllowOverride All

If you needed to adjust either of these settings, you will need to save the configuration file and restart Apache.

DETAILS YOU NEED TO KNOW TO DO THE ABOVE

Possible location of the Apache configuration file:  /etc/apache/conf/httpd.conf

Command to restart Apache:  apachectl graceful

ONLINE DOCUMENTATION FOR APACHE .HTACCESS PROCESSING

The online documentation for Apache includes instructions for configuring the server to process the .htaccess file (http://httpd.apache.org/docs/2.2/howto/htaccess.html).  
Documentation specifically for AllowOverride can be found at http://httpd.apache.org/docs/2.2/mod/core.html#allowoverride.

As the documentation indicates, the default value for AllowOverride is All.  If it is set instead to None, the .htaccess file will be ignored.  Change the setting to All, and restart Apache.


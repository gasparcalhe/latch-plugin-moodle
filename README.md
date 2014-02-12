### LATCH MOODLE PLUGIN -- INSTALLATION GUIDE ###


#### PREREQUISITES ####

* Moodle version 2.6+ (http://download.moodle.org/).

* To get the "Application ID" and "Secret", (fundamental values for integrating Latch in any application), it’s necessary to register a developer account in Latch's website: https://latch.elevenpaths.com. On the upper right side, click on "Developer area".


#### INSTALLING THE MODULE IN MOODLE ####

1. Unzip the downloaded plugin and place the whole content "mlatch" inside the moodle subfolder "mod".

2. Reinitialize Moodle and actualize database. The module "mlatch" will then be installed automatically. 

3. Go to "Site Administration" in the left side menu and access to "Plugins Overview". Settings menu for Latch module "mlatch" is accessed from there.

4. On the Settings menu, introduce "Application ID" and "Secret" data, generated before in the "Latch config" menu.

5. The module is not yet visible. To make it visible first create a new course and second include the module "mlatch" on that course. Moodle provides several roles to do that (teacher, manager, etc...).

6. Once the module is visible, access to pairing menu by accessing to the module. Introduce pairing token to pair Latch account.

7. The Moodle account is now under Latch protection, that means access to Moodle accounts is "latched" but not access to modules or courses.

8. Permissions: enable access to users (students) to this course for they can access to the "mlatch" module and pair their Moodle accounts.

#### UNINSTALLING THE MODULE IN MOODLE ####

1. Go to "Site Administration" in the left side menu and access to "Plugins Overview". 

2. Then find the "mlatch" plugin and press "Uninstall".
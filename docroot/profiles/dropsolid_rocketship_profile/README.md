# Dropsolid Rocketship Profile

This module contains patches. For these patches to apply, your project
should require [`cweagans/composer-patches`](https://github.com/cweagans/composer-patches).
Read that project's README to set up your project to work with dependency 
patching.

One bug that sometimes crops up with dependency patches, is that composer
doesn't pick them up immediately (if, say, a new release has an extra patch).
Either check the composer log or the composer.lock to make sure all patches
are applied properly, or run your update command twice.

-------

Many thanks to Varbase for providing a large amount of the code and way of
doing things. If you like parts of this profile, chances are it's those parts
so give them a look: https://www.drupal.org/project/varbase

- Dropsolid Rocketship installer
- has option to select extra languages during install
- has option to enable some of our own features during install
- has option to select the theme to use, default is olivero
- creates admin and webadmin roles and users
- sets up permissions for webadmin
    - protects superadmin role from editing by webadmin
    - see config/install webadmin.yml for all permissions
- has extra permissions related modules:
    - userprotect
    - role_delegation
    - taxonomy_access_fix
- has config ignore and splits for each environment ready to go. Don't forget
 to run "drush d-set" after installation to set it up AND that the split 
 folders exist.

* When multiple languages are selected, sets language switcher block in header_top
* When multiple languages are selected, makes language selection visible for 
ONLY menu links. Other content uses the "You are currently doing X in 
language Y" and follows selected interface language. See rocketship_core.

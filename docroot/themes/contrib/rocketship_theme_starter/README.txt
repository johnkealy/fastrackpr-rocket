# Rocketship theme: Rocketship Starter

Component-based Drupal theme for use with Dropsolid Rocketship install profile, modules and other components.

## This theme was generated using the preset: `starter`

**Starter preset**
Comes with some basic styling and Sass/Twig files to get you started.<br />
Contains pre-defined styles.

## Dependencies

### Local development

- Node: version mentioned in package.json (under "engines")
- NPM: whatever version comes with the Node version
- a recent version of gulp-cli

We don't recommend downgrading to node 8, due to lack of support.
[https://blog.risingstack.com/update-nodejs-8-end-of-life-no-support/](Version 8 dropped maintenance support in December 2019)
Version 10 should work but support will stop in April 2021.
At the time of writing, version 14 is the latest stable release.

If you need to upgrade (or downgrade) node/npm, here is some info:
- Install and switch to the node version you will be using (we recommend using NVM)
- throw away the package.lock files
- throw away the node_modules folder
- change the version check in `package.json`, as well as in `.nvmrc` with the version you will be working on
- DO NOT USE AN UNEVEN NODE VERSION!!! Only the even ones are stable.
- run `npm install`
- rebuild node-sass: `npm rebuild node-sass`

We try to enforce a specific node version using a `.nvmrc` file (works if you use NVM), as well as a check in package.json which should give you a warning during `npm install`

**Example in package.json:**
Support 1 version:
  ```
  "node": ">=14.0.0 <15.0.0"
  ```
Support multiple versions (not recommended):
  ```
  "node": ">=10.1.0 <11.0.0 || >=12.1.0 <13.0.0"
  ```

### Setup

Before you do anything else, uninstall (disable) the front-end themes you are NOT using.
Having multiple themes enabled, seems to break some the inheritance rules (eg. libraries or components of same name get picked up from the wrong theme)
This issue describes the problem and provides a patch if you need it: https://www.drupal.org/project/components/issues/3027672


**INSTALL NVM TO MANAGE YOUR NODE VERSIONS!**

The basis of our local development, is Node + NPM. We need it to run Gulp tasks to make our life easier.
Depending on how old your project is, you will need to quickly and easily switch Node versions.
This is because some versions of Gulp (and dependencies like sass-build) are not compatible or need updated builds.

Long story short: you need to install NVM so you can switch using a simple command and so we can enforce locked versions of NPM.
Follow the instructions set here:
* https://redmine.dropsolid.com/issues/84063
* Or here: https://michael-kuehnel.de/node.js/2015/09/08/using-vm-to-switch-node-versions.html

Refer to the theme's readme file to see what version you need to run in order to work with that theme.
Unfortunately, most projects pre-06/2019 don't have this info, but these pointers might help you:
- if it contains Gulp 3 and no info in the readme, it is very likely you'll need to switch to NPM 6
- if it contains Gulp 4 and no info in the readme, either NPM 6 and NPM 8 should work but you might need to change some things:
  - Pay attention to the output when running `npm install`. If it gives you a warning about node-sass, you need to rebuild it. The output should contain the right command to do so.
- themes after 06/2019 should contain a version lock for NPM + info in the 'Requirements'-section of the theme's readme file on what NPM version to use.

### Drupal stuff (possibly already included by way of Rocketship Core)

- [Drupal Components Library](https://www.drupal.org/project/components)
- [Unified Twig Extentions](https://github.com/drupal-pattern-lab/unified-twig-extensions)
- responsive_image module (part of Drupal core)

## What the theme does

- Templates, CSS and JS is component-based
- Converts Sass (with globbing) into CSS
- Can provide Sourcemaps for CSS and JS
- Generates favicons
- Generates custom icon font or icon sprite
- has better responsive tables
- can override colors for the Rocketship Sections background-color palette
- adds styling for Rocketship content types
- has a component-based styleguide (uses Storybook)

## Settings

Your active theme has settings over at `admin/appearance/settings/themename`
Be sure to check those out before searching the Sass and JS files.
E.g. there is a setting that can switch the language menu from a dropdown to an inline list
E.g. there is a setting to make a fixed header

## Libraries and other third party stuff

- **Styleguide:** Storybook-based styleguide that is component-based
- **Fontawesome:** an iconfont, integrated with a theme mixin and variables
  - info in the Styleguide on how it is used (see Base/Fonts and Atoms/Images)

## Setup

- **Don't change the name of this theme after it has been generated!** It will break things.
- Check if your npm and node are up-to-date. In your command line, run `npm -v` and `node -v`. The versions should match the requirements.
  - if you have NVM installed, first running `nvm use` should switch to the correct versions (or tell you what to install)
- You will need the Gulp CLI installed globally as well: `npm install --global gulp-cli`
- In your command line, navigate to your theme folder (*/docroot/sites/all/themes/custom/[theme]*) and run `npm install`. You only need to run this command once, when you install the theme. It will install the node modules for gulp to work.
- if your project has a docroot-folder (and uses git), a pre-install script will run and try to add a git pre-commit hook that helps detect linting issues
- run `gulp prod` to compile your css and js files
- In your Drupal settings, make sure your child theme is activated and set as default.

Your theme is now ready to be used!

## Technology

- **Gulp** is used as a task manager (eg. compile CSS, make favicons, …)
- **npm** is used to manager the node modules for Gulp, and if needed, run
pre or post scripts (see package.json)
- **Sass** + linting is used to write better CSS.
- **JS and jQuery**: used for all manner of interactivity, such as the mobile
navigation.
 The base theme replaces the default jQuery version in the front-end with 3.3.x
- **Modernizer**: Useful for feature-detection (css or js properties support in
order to load fallbacks). The base theme replaces the default with version 3.6.x
and loads it for anonymous users as well.


## Anatomy the theme

- .theme file
- yml-files
- config
- gulp
- components folder
- styleguide
- css and js
- fonts, images, favicons, icons, …

### rocketship_theme_starter.theme

Various theme functions (hooks). The most important ones being to:

- **Add theme settings:** `HOOK_form_system_theme_settings_alter` and
`HOOK_preprocess_page`
  Theme settings are defined and saved in drupal settings so they are
  available in your JS files (using the drupalSettings object) and in your
  theme's settings page. An example of this being used, would be the language
  menu and fixed header (Demo only), but also a way to enable or disable animated scrolling on anchor links. <br />
  In order for theme settings to work, you need to check them/fill them in at: `/admin/appearance/settings/`
- **Customize background-colors for color picker:**
  `_rocketship_layout_bg_color_options_list`
  Normally, you use the config form in `/admin/config/system/rocketship-core` set up the bg color options of layouts.
  But you can brute-force override the colors or functionality to do with this, using that theme function.
  You can also use `00-theme/05-pages/page--layout-builder/_layout-builder.scss` to override styling for the color option previews.
- **Load libraries:**
  `HOOK_preprocess_page`
  For conditionally loading libraries. DO NOT LOAD ALL YOUR LIBRARIES HERE!!!
  Only for exeptional cases. Eg. loading libraries for new content types could happen here.

Don't forget that the child theme inherits theme functions defined in the
parent 'Base' theme.

Eg. for turning responsive tables on or off (selectively):
`$variables['table']['#responsive'] = FALSE;`

But also, html viewport settings, classes on body-tag, various block classes,
theme suggestions, …

### YML

- **info:**
  - Aliasses: These are used in Twig when you need to include another file.
  Makes it really easy to simplify a path so you don't need to change it in
  a 100 different places if something is renamed
  - global libraries: libraries that are used (almost) everywhere
  - requirements: the modules this theme depends on
  - regions: regions made available for block layout. Beware there are several
  locations to put you navigations in. **Primary and secondary nav are bundled
  together in the mobile navigation.** The rest is not.

- **libraries:**
  - bundles of js (and sometimes css as well). Don't forget you still need to
  attach these to your theme if you want to use them. Either in *.info, in
  *.theme or in a twig file

- **layouts:**
  Templates for Layout Builder (we no longer use DS or Page Manager). At least 1 template in here:
  an example of a custom layout, to use on sections.
  Other Layouts you find in Layout Builder Section configuration, are
  inherited from the rocketship_core module and are in use everywhere Layout Builder is used

- **breakpoints:**
By default, we mostly use the breakpoints defined in rocketship_core. But you can make your own in here


### config folder

This is where default values are defined for the theme functions created in
rocketship_theme_starter.theme. You don't need to touch these

### Gulp

We use Gulp as our task manager. This will compile all the things we need to
make the front-end work.

#### + Browser sync

First of all: if you work with Dropsolid's launchpad, gulp can use Browsersync to
automagically update your browser when you change your Sass or JS
This requires you to pass 2 arguments to your gulp tasks:
- projectUrl: a string, the url of your project, including http(s) and the port!
- projectDelay: a boolean, default is FALSE,  if you have a slow sync, you'll
have to tell the system to wait and reload again after the sync has finished.

Example:
`gulp --projectUrl http://rocketshipdemo.docker.localhost:81/ --projectDelay`

Your browser will use localhost:3000 to render the site and keep listening to
updates.
It wil **NOT** work if you go to your default project url.

**Extra options:**
If you change the port to 3001, you will get extra development options.
Eg.
- sync across devices: you also get a proxy url so you can surf to your
machine with any device. Very handy if you want to debug on Android or iPhone

**Fixes**:
- If you get a blank page in Chrome or other browsers, chances are localhost isn't getting resolved in Launchpad. [This Firefox/Safari workaround also fixes this issue for Chrome.](https://gitlab.com/dropsolid/launchpad#i-cant-browse-to-a-launchpad-project-in-firefox-or-safari)

#### Linting

It is important that you write your CSS in a structured and clear way.
To enforce this, we've added linting that follows idiomatic CSS and a couple of best-practices.

One of these aspects, is the enforcement of a loose ordering of the properties.
We order the CSS properties by group, but inside those groups you can order the properties in any way you like.

Most properties fall into these groups and thus should be orderened that way:
  - positioning
  - display
  - box model
  - list
  - font
  - background
  - box-shadow
  - transform
  - transition
  - animate

Eg. "font-size" and "font-weight" are part of the same group ('font') so it doesn't matter what goes first.

Eg. "width" is part of the dimensions and box model, so should come before "font-size".

Eg. "border" is part of box model so should go before "box-shadow" and after "font-size".

You can see the full list of properties in `gulp/config.js`, under the section `order/properties-order`

#### Tasks

We have some `npm` commands in place that trigger Gulp to perform the tasks needed to theme. You can also call the Gulp tasks directly.
For example: `npm run css:dev` would be the same as `gulp css:dev`
This is because we want to have the freedom to change task runners if needed while still use the same commands to do stuff.

Knowing this, you can use all the following tasks.
Enter `gulp` or `npm run` followed by one of the following task names:

**The basic commands:**

- default (`gulp`): will compile CSS and JS for development
(with linting), try to create favicon and trigger a 'watch' task. 90% of the
time, you only need this task.
- `dev`: same as the default task, but without a `watch`
- `prod`: compile CSS and JS for production (no map files nor linting nor watch).
   You need to run this task before committing any css and js files for your project,
   because we don't want any sourceMap references to get on the server
- `watch`: included in ‘default’, this task will keep running in the background
to keep compiling the Sass, JS and images
- `favicon`: This will generate the favicons (if you place an image in
/favicons/source folder).  <b>Don't forget to add these images!!!</b>

**What is running in the back of these:**

- `setup`: included in ‘default’, this will check for the favicons but also
try to provide a copy of Fontawesome locally
- `css:dev` and `css:prod`: this will compile the Sass (inside a component
folder) to CSS (and provide sourcemaps if dev is used). The produced files end
up in the `css`-folder
- `js:dev` and `js:prod`: this will concatinate any JS-files inside a
component folder (and provide sourcemaps if dev is used). The produced files
are located in the `js/dest`-folder
- `css:mail`: runs after the css-task, produces css from the style.mail.scss
file
- `css:editor`: runs after the css-task, produces css from the
style.editor.scss file
- `js:lint`: included in ‘js:dev’, this will provide notes for you to write
better code
- `css:lint`: included in ‘css:dev’, the same but for Sass

**The extras:**

- `icons:font`: not included in ‘default’, using gulp iconfont this will
generate a font based on svg’s in the 'icons' folder. It will also add
mixins to use them.
- `icons:sprite`: not included in ‘default’, using gulp sprite this will
generate a sprite based on svg’s in the 'icons' folder. It will also add sass
variables and mixins to use the
- `css:lint --lintingOrder=strict`: use this to enforce a very strict order of the properties. By default, the properties are loosely ordered by category only. You can find the groups and their exact properties in `gulp/config.js`
- `css:critical --criticalUrl https://my-site-page-url --criticalName my_name`: This allows you to generate a critical css file based on the provided url (use a dev or staging version WITHOUT htaccess!!!) and named `css/critical-my_name`
The files do not get automatically loaded, read the info in the Styleguide on how to use them.

#### Compile Sass/JS

To simply watch and compile your Sass/JS, you must navigate to your theme
folder (*/docroot/sites/all/themes/custom/dropsolid_base/[child-theme]*) in
your command line and run `gulp`.

#### Favicon Generator

This theme has an automatic favicon generator. Information on how to use it, can be found in the readme file in the theme's `favicons/source` folder
The command is: `gulp favicon`

#### Custom icon-font or sprite Generator

This theme also has an automatic icon-font generator. Info on how to use it, can be found in the Styleguide Atoms/Images documentation.
The command is: `gulp icons:font`

#### Critical

You'll have to add subtasks per path you want to render critical CSS for.
Make sure these pages have content in them, otherwise it's pretty pointless.
Also, the critical CSS files are useless if you don't inline it in the HEAD
of your site. You can use a module like critical_css to do this.

If you need to inline one yourself, you can do so like this:
(this example replaces relative paths with absolute ones)

```
{{ source( base_path ~ directory ~ '/css/' ~ value)|replace({'../images': base_path ~ directory ~ '/images'})|replace({'../fonts': base_path ~ directory ~ '/fonts'})|raw }}
```

or maybe like this:

```
<style media="screen">
  {% include base_path ~ directory ~ '/css/critical/filename.css'|replace({'../images': base_path ~ directory ~ '/images'})|replace({'../fonts': base_path ~ directory ~ '/fonts'}) ignore missing %}
</style>
```

### Components

The theme is built using a component-based (or Atomic) method.
More detailed info can be found in the Styleguide, but it is recommended you get familiar with the idea and
practice behind this. To do so, here are some resources:

- http://bradfrost.com/blog/post/atomic-web-design/
  - More in depth: http://atomicdesign.bradfrost.com/chapter-2/
- Implementation example: https://patternlab.io/
  - Demo: http://demo.patternlab.io/

### Styleguide

As mentioned before, there is a component-based styleguide included, using Storybook.
In order to see it, you need to run npm commands:

- `npm run-script storybook`: runs in watch-mode and gives you a url to surf to on localhost
- `npm run-script build-storybook`: (optional) builds an actual folder with the styleguide in it (located in docroot), so you can put it on a remote server to share with others

In the Storybook styleguide, info is written out about the styleguide itself, as well as per category and per component.


### Fonts

There's a fonts folder to hold self-hosted fonts. Info on how to handle fonts (self-hosted or external), can be found in the Styleguide (more on that later)


### Twig

Twig is a powerful templating engine, which allows us to write html around the
Drupal data in an easy way. These files are the basic building blocks of our components.

Some of the things they use are:
- [**filters**](https://twig.symfony.com/doc/2.x/): so you can manipulate
strings (eg. translate, find/replace stuff, make lowercase, …), split arrays, …
- [**include**](https://twig.symfony.com/doc/2.x/tags/include.html): include a
twig file inside another one + pass variables to it using `with {}`
- [**extends**](https://twig.symfony.com/doc/2.x/tags/extends.html): override
blocks from a master template
- [**embed**](https://twig.symfony.com/doc/2.x/tags/embed.html): combines both
include and extends
- [**macro**](https://twig.symfony.com/doc/2.x/tags/macro.html): kind of like
a function. You reference a macro and pass variables to it.

For Drupal's theme suggestions to work, it needs to have its templates located
in a folder called `templates`. For this reason, we put all our suggested
.html.twig files inside its own `_drupal/templates`-folders BUT do an
`@include` to whatever twig file we control the naming of.

The aliases used with these includes, are defined in rocketship_theme_starter.theme. The
make it easier to build a path.
Combine this with underscores and clear naming for better readability and use
in future pattern styleguide.

There are also some extra twig functions, most notably a BEM function.
This lets you create classes following the BEM-structure for better writing of
CSS (extended Twig-function, thanks to unified twig module)

**Examples:**
- `components/example` naming, embed, aliasses, BEM, …
- `molecules/menus/menu` for include/embed, macro, BEM, …


### Sass

As mentioned before, the styling is combined in the components folder with the
twig and JS. It is also split for Features (new content types), Content Blocks and Theme.

- 00-theme
- 01-content-blocks
- 02-features (the resulting CSS is NOT loaded by default)

And inside each of those:

- **base**: config and vars
- **atoms**: styling the simplest elements, eg. fields
- **molecules**: styling groups of atoms, eg. most blocks
- **organisms**: styling groups of molecules, eg. site regions like header, footer, …
- **pages**: styling Drupal pages (eg. 404, user, maintenance, …)
- **layouts**: styling for (custom) layout templates

All theme files combine into style files (with @import) for specidic perposes:
admin, editor, fonts, mail, print, …

Only compiled CSS-files loaded in a library (eg. in .info file, a hook or a
twig-file)

### JS

Folowing the same atomic structure, you will find javascript files for
specific functionality added to specific components. Most of these are **loaded
selectively**.
Eg. language menu JS is set in `molecules/menus/menu--language` (makes a
dropdown menu or shortcodes) or dropdown menu and mobile menu JS is set in
`molecules/menus/menu`).

There are also 2 special JS files, located inside `00-base`, these give you
some helper functions and global functionality. These are **loaded globally**,
alongside jQuery, jQuery Once, drupal JS, drupalSettings, ….

All js-files located in the atoms/molecules/organisms/… folders will compile
into `js/dest`.

If you make a new component:
1. add your original files to your new component's folder
2. include the generated file and not the original source to your library
3. don't forget to add your dependencies (eg. jQuery, …). Even if it is already
added globally, this makes sure they are loaded BEFORE your JS is run
4. attach your library to your component's twig-file if possible. If you don't
have a relevant twig-file to add it to, there are other ways to load the libary
(see previously in the readme)

!!! DO NOT ADD YOUR JS-FILES TO THE 00-BASE FOLDER !!!

**Namespaces:**
We use a namespace (basically a global variable) to save all our functions in.
This is to make sure that we can easily reference functions/variables defined
in a separate file and make such references readable. This would not be a big
problem with aggregated files but would be during development.
See the various JS-files to see what I'm talking about.

**Behaviors**
Drupal behaviors are great. Look them up sometime.
Bundle your js triggers inside a behavior to ensure they are loaded after Ajax
calls.
You can split off your actual JS functions and put them outside the behaviors.

**Bigpipe**
There is a link to documentation about Bigpipe in the Sources at the end. In
short: Bigpipe is a system that loads chunks of the drupal site separately for
better loading efficiency. The language navigation is one of those elements
that is handled by Ajax in that way. That's why you don't see the active class
or property printed out in the Twig file.

**jQuery Once**
Examples of how to use this are in the top comments of all JS files.
These help to avoid loading JS (eg. event handlers) multiple times.

**WARNINGS:**

- There is a bug in Drupal 8.6 that breaks the 'context' property when you try
to get an element, in a library that is not loaded globally in your info file.
E.g. `$('.myclass', context)` gives back an empty object (length 0), but
`$('.myclass')` works.

## Breakpoints

See Styleguide for info

## Special components

### Forms

See Styleguide for info

### Header and navigation

See Styleguide for info

## Layout 01: Twig

Look at a drupal site as nested dolls:

- **block layout**: the main frame of our site. These contain all the regions
set in the Drupal html twig file
- **panels**: [no longer used] The making of sub-regions inside the 'Content' region of the 'block layout'.
You could make layout templates and define those sub-regions using
**Page Manager** [no longer used]. You could do so for different types of pages (eg. for different
content types like Landing Page, Blog detail, …). But as said, is no longer in use since we use Layout Builder.
- **view-modes**: the collection of fields and stuff that builds up a node (Block, Paragraph or Content Type) of a certain type (eg. Blog Content Type). There are view-modes created per context. Eg. a teaser may not need to contain all the same fields as the full node.
You can use Layout Builder to make a structure using rows (Sections) combined with layout templates. Inside those layouts, are Regions, which are basically the columns that contain the fields (and optionally, custom blocks)
All this can be found under the `manage view modes` tab of a Block, Paragraph or CT
**Display Suite**. [no longer used] We used to use this instead of Layout Builder. You could also use layout templates (via a dropdown at the bottom of the view-mode page) but also make groups via the UI (html-tag with optional
classes).
- **node variants**: When Layout Builder is active on a Content type, you can actually specify to use a different layout ONLY on a specific node instead of on ALL the nodes of that Content Type. This way you can make landing pages. The homepage is a current example of this. It's a basic page that has been changed.
- **theme twig files**: all entities (fields, blocks, ct's, paragraphs, …)
have corresponsing Twig-files (you can see their names and paths when theme
suggestions are visible in html). The twig-files for view modes (only in blocks,
ct's and paragraphs) can be overridden with layout templates, if Layout Builder has applied one.

**Fields:**

field don't HAVE view-modes, they are rendered INSIDE view modes. You do have
some tricks to change their output without dealing with Twig though. Look at the
formatters and options in the various view modes. Formatters can also be custom
made, but that's back-end dev stuff and outside the scope of this documentation.

**Layout Builder vs default twig files:**

So for blocks/ct's/paragraphs, you have a choice to either use the default twig
templates and have full control of the output inside those (no predefined
subregions, no UI to help you). Or you can avoid this manual work by using
layout templates where regions can be pre-defined.

**Naming files:**

When making your own layout templates, keep in mind that **the machine name
has to be the same as the twig file name**. The machine name uses underscores
(_), the twig file uses dashes (-).


### Example: Homepage

Rocketship content types can use Layout Builder to build up Sections (rows) and regions in 1 to 3 columns, to output node content. The Page CT has this, for example.
For the homepage (which is a customized Page node), we recommend adding Sections using Rocketship's layout templates.
But you could add your own if you want. An example is provided in the theme/04-templates folder.

- `00-theme/04-templates`: where any extra custom layout templates should be located.
Add your regions and html here and special Section/Region styling here.
- `rocketship_theme_starter.layouts.yml`: The regions are defined here for custom layouts
- `go to homepage > layout tab`: See a list of all current Sections, where you can add and configurate your own. You will need to name your section but also add a custom class in order to differentiate the sections from each other.
Please not that you can not switch layouts once a Section has been created. So if you want to use another layout instead, you need to add a new Section, apply your preferred layout, and name it.

### Example: Blog content type

When using new content types:

It's recommended for most contenttypes built in Rocketship to have at least 2 view-modes:
- teaser: for the main overview
- full content: for the node detail content

When click the Layout Builder button on the full content node, you will can add sections to make up different parts of the node.
At the very least, you should add sections for the 'head' of your node and one for the 'content'. Both should have custom classes in order to better style them.
For consistency with the rest of the theme/Drupal, we recommend naming follows the BEM rules for CSS.


## Features

In previous versions of Rocketship, several Content Types could be automatically installed. For several technical reasons, they are no longer part of the installation at this point (but this functionality might come back later as separate modules or a generator).
However, we do still have Sass & JS files for these content types. So you can use those files to theme CT's you make yourself, eg. Blog.

Important to know for theming, if you use Layout Builder, is that these contenttypes should have view modes with extra classes on the Sections for you to theme them better.

The example styling is located in a separate folder of the components: 02-features

### Layout Builder

- Explained earlier, with example from the homepage

### Overviews

See the info in the 'development' readme of your Rocketship project, for more info on making your own views, how to add them to the homepage or adding them to a Page.
But using Layout Builder, it should be pretty self-explanatory.

### View modes

As mentioned before, 2 view modes are recommended, One to load the entity content's
'full content' and one for the 'teaser'.
Using Layout Builder on the full view mode will be needed if you are going to open up content management for the webadmin (or 'content editor').

### Libraries and styling

The `02-features` folder contains example styling for new Content Types (eg. Blog) but the libraries (defined in your_theme.libraries.yml) are NOT loaded by default.
This is because we no longer automatically install Content Types as part of Rocketship, in the current version (we might offer this again using a new module later, eg. as a CT generator).

You can load any new or custom libraries yourself if you make new Content Types.
This can be done by attaching a library via the theme's `rocketship_theme_starter.theme` file's `HOOK_preprocess_page` or in a Twig template, for example.

The example styling can be changed in the `component/01-content-blocks` files


## Content Sections and Content Blocks

### Background color and image

- **rocketship_theme_starter.theme**:
  `_rocketship_layout_bg_color_options_list`
  This is where you need to add the extra colors that the layouts use for the front-facing theme.
- `00-theme/admin.style.scss`
  You don't need to touch this file to work on content blocks
- `components/01-content-blocks/03-organisms/00-section/_00-section.scss`
  define the background colors and related styling for the Sections in the front-facing theme
- `components/01-content-blocks/03-organisms/section--simple-header/_simple-header.scss`
  define the background colors and related styling for the headers,
  as used in Page content type …

In order for the background-colors preview to work in the admin theme, you need to fill in your color variants at `admin/config/system/rocketship-settings/settings`.

**Note:**
- Saving this form will also generate a default colors CSS-file that is loaded if you check the `Enable default CSS` options. Dropsolid sites DO NOT USE the default CSS, so you still need to add your front-facing colors and other theming in the `02-content-blocks` folder of your theme.

### Libraries and styling

The Content Blocks has libraries inside the Rocketship Core module, these contain
functional JS (eg. FAQ block needs to open and close with a click).
They also load a structural CSS-file.

If you have a rocketship theme enabled, Content Blocks
loads styling from this theme, when Rocketship Core module is enabled.

This is done by attaching a library via the theme's `rocketship_theme_starter.theme` file's `HOOK_preprocess_page`

## CKE

### Webfonts
In order to use Google (or Typekit) fonts in the editor, you will need to
@import your font into `style.editor.scss`.
This is because the admin-theme does not use Webfont loader at this point.

DO NOT USE @IMPORT IN THE FRONT-END THEME !!! Always use the webloader in
05-pages/html/_html.twig for the front-end theme.

Example for using Open Sans and Roboto Condensed:
<code>@import url(https://fonts.googleapis.com/css?family=Open+Sans:300,400,700|Roboto+Condensed:400,700);
</code>

### Styles
Just like in D7, we can add Styles. This is done in the UI:
`/admin/config/content/formats`

For each of your editors, you can scroll to the Styles dropdown section
and add your custom styles to it.
Always start with a tag-name and add the class separated by a dot (.)
For multiple variants, you can add all possible classes to your tag, separated
by a pipe (|)
There is a bug though. When you turn on 'limit html tags', the Styles dropdown
breaks.



## Sources

- [Vardumper](https://www.drupal.org/project/twig_vardumper)
- [Twig xdebug](https://www.drupal.org/project/twig_xdebug)
- [Devel: webprofiler](https://www.drupal.org/project/devel)
- [Atomic design](http://bradfrost.com/blog/post/atomic-web-design/)
- [Atomic design: book](http://atomicdesign.bradfrost.com/table-of-contents/)
- [Pattern lab demo](http://demo.patternlab.io/)
- [Drupal 8 documentation (incomplete)](https://www.drupal.org/docs/8)
- [Adding libaries to a theme (CSS, JS)](https://www.drupal.org/docs/8/theming/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-theme)
- [Behaviors](https://sqndr.github.io/d8-theming-guide/javascript/behaviors.html)
- [Behaviors [Bigpipe]](https://www.drupal.org/docs/8/core/modules/bigpipe/overview)
- [API overview](https://www.drupal.org/docs/8/api/javascript-api/javascript-api-overview)
- [Powerful templating engine](https://twig.symfony.com/doc/2.x/)


## Common issues

### Theme suggestions don't work with Layouts

For some reason, the theme suggestions don't work properly with the layouts if the template file name (using dashes) is not the same as the template machine (using underscores) name in `x.layouts.yml`.
So, rename the template file name to match.

### Unsupported platform for fsevents (only on Linux)

Some older versions of the themes included the node module `fsevents` and it may still be a dependency of some other node modules. Problem is, this is made to work with MacOS.
Fortunately, `fsevents` is not needed for our nmp scripts or Gulp tasks to work, so we can remove or bypass it.
- Check your `package.json` file for a (dev)dependency of `fsevents`. If it is in there, remove it and reinstall your node modules (remove the old ones first)
- Check your gulp tasks for `fsevents` and remove any requires or functions using this module
- Rerun the task that gave you an error. If it's no longer there, you're good to go
- If you still get an error, run `npm rebuild` and then rerun the task

## Maintenance

As long as the project is used internally only (Dropsolid), merges with the master must be approved by R&D. Use our ticket system to report issues and propose solutions.
If new types of favicons are introduced in the future, they need to be added to the base theme's html twig file + the favicon gulp task

At the time this project moves to drupal.org, issues and patches can be added there.

- Git repos: see the git repo for rocketship_theme_generator module

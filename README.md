# Tribe Extensions

Tribe Extensions are small plugins that add features to the official Tribe Plugins. They are designed to be installed and activated alongside the Tribe Plugin that they extend. 

### Features

The extension framework has an ever growing list of benefits for the code that runs atop it. Instead of writing a simple code snippet and pasting it in your theme's functions.php, often it will make sense to write a simple extension instead. The benefits of writing an extension:

* Specify which Tribe plugins need to be present for your code to work. Most snippets require one or more plugins to be active, or else errors will be generated. These can be easily specified within the framework. If they are not present the admin may automatically receive a notice walking them through which plugins they need to download and install.
* Communicates with Tribe plugins letting them know you're running. Most modifications do not do this, and so the Tribe plugin does not know when it is being extended. 
* All future features. Our framework is still young. As time goes on more features will be added. Currently we are planning to add an update mechanism to the framework. For official extensions this will check to see if a new version is available, and allow users to update just like they would any other plugin. Extensions will automatically receive these new features when Tribe Common is updated in WordPress, so no changes to the extension itself will be necessary.
* All the benefits of turning a snippet into a plugin. For instance, easy installation and deactivation, identifies itself in the system info, etc. 

At times it will not make sense to convert a snippet of code to an extension. Extensions can be more difficult for users to modify than the customary functions.php. So for snippets of code that are intended to be modified by the user, usually these would not be made into extensions.

# How to create an extension

This guide will walk you through each step for building an extension. For the sake of example we'll show you how to create an extension that hides the Tribe Bar in The Events Calendar plugin.

## Step 1) Create a plugin folder

Begin by creating a new folder inside of your `/wp-content/plugins/` directory. In order for this extension to be automatically loaded (instantiated), prefix the folder name with `tribe-ext-`. Try to be succinct with the name of the extension folder, preferably keeping the full folder name under 35 characters.

### Example

Since our example extension hides the Tribe Bar, this will be the name of the folder we create inside `/wp-content/plugins/`:

```
tribe-ext-hide-tribe-bar
```

For reference, this example folder name is 24 characters long.

# Step 2) Copy the template file

Create a blank `index.php` file inside your new extension directory, and insert the following template into it.
 
## The Template
 
 ```
<?php
/**
 * Plugin Name:     [Base Plugin Name] Extension: [Extension name]
 * Description:     [Extension Description]
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__[Example]
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */
 
// Do not load directly.
defined( 'WPINC' ) || die;
// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) { return; }
 
/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__[Example] extends Tribe__Extension {
 
    /**
     * Setup the Extension's properties.
     *
     * This always executes even if the required plugins are not present.
     */
    public function construct() {
        [Insert properties here, or delete this function if there are none.]
    }
 
    /**
     * Extension initialization and hooks.
     */
    public function init() {
        [Insert custom code here.]
    }
}
 ```

### Example

The contents of that template will go in this file:

```
tribe-ext-hide-tribe-bar/index.php
```

## Step 3) Plugin Header

The extension template begins with this block of code.

```
<?php
/**
 * Plugin Name:     [Base Plugin Name] Extension: [Extension name]
 * Description:     [Extension Description]
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__[Example]
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */
```

Everything within \[brackets\] should be modified.

* Plugin Name: Think about a good name for you extension, then fill in the brackets.
    * Replace `[Extension name]` the name of your extension.  
    * Replace `[Base Plugin Name]` with name of the Tribe plugin you are extending, as it appears in the WP Admin > Plugins list. 
    * The extension name should match the title of the Extension listed on TheEventsCalendar.com.
* Description: Replace `[Extension Description]` with a brief description about what your plugin does. 
    * Keep this to 140 characters or less.
* Extension Class: After choosing a plugin name, you will need to assign a PHP class name to this extension. Replace `[Example]` with your class name.
    * The class name should be thoroughly unique and brief. 
    * Each word within the class name will be capitalized, and should be separated by a single underscore. Example: `Hide_Tribe_Bar`
    * Typically the class name is similar to the extension's folder name. If your folder is `tribe-ext-hide-tribe-bar` the full class name could be `Tribe__Extension__Hide_Tribe_Bar`.
    * For the full details on class naming conventions, see the [Tribe Products Coding Standards](http://moderntribe.github.io/products-engineering/guidelines/).

The rest of the plugin header will stay the same in nearly every extension.

### Example

Following the above guidelines, the full header for our plugin that hides the Tribe bar would be:

```
/**
 * Plugin Name:     The Events Calendar Extension: Hide Tribe Bar
 * Description:     Hides the Tribe Bar that appears above the calendar.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Hide_Tribe_Bar
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */
```

## Step 4) Rename Extension Class

The Extension Class name appears twice in a typical extension. Once in the plugin header, and then again in the class declaration:

```
class Tribe__Extension__[Example] extends Tribe__Extension {
```

After you have specified the class name for you extension in the header, copy that name into the above class declaration.

### Example

Continuing with the Hide Tribe Bar example extension, our Extension Class will be called `Hide_Tribe_Bar`. So this line will become:

```
class Tribe__Extension__Hide_Tribe_Bar extends Tribe__Extension {
```

## Step 5) Specify extension properties (optional)

A variety of properties can be specified for an extension. Typically they are set within the public `construct()` function:

```
/**
 * Setup the Extension's properties.
 *
 * This always executes even if the required plugins are not present.
 */
public function construct() {
    [Insert properties here, or delete this function if there are none.]
}
```

If your extension has no arguments it needs to set, you can delete this entire block of code from the extension. If it does have arguments to set, delete this line and begin specifying the arguments:

```
[Insert properties here, or delete this function if there are none.]
```

### Specify Tribe Plugins required by the extension (optional)

Any Tribe Plugins that this Extensions needs to be present should be specified within the `construct()` function. Inserting this line indicates that it requires The Events Calendar version 4.3 or greater to be active for our example extension to run:

```
$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
```

The Main class for The Events Calendar is `Tribe__Events__Main`. Any call to `$this->add_required_plugin()` should specify the Tribe plugin by its Main class name.

The second argument `, '4.3'` is optional. This specifies a minimum version number required by the Extension in order for it to run. 

Typically, when the required plugin is not active on the calendar an admin notice will be shown to the user. This notice will inform them of the missing plugins, and provide links to download and install them.

Here is an up to date list of the current Tribe Plugins that can be specified:

```
$this->add_required_plugin( 'Tribe__Tickets__Main', '4.3' );
$this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.3' );
$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
$this->add_required_plugin( 'Tribe__Events__Pro__Main', '4.3' );
$this->add_required_plugin( 'Tribe__Events__Community__Main', '4.3' );
$this->add_required_plugin( 'Tribe__Events__Community__Tickets__Main', '4.3' );
$this->add_required_plugin( 'Tribe__Events__Filterbar__View', '4.3' );
$this->add_required_plugin( 'Tribe__Events__Facebook__Importer' );
$this->add_required_plugin( 'Tribe__Events__Ical_Importer__Main' );
$this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main', '4.3' );
$this->add_required_plugin( 'Tribe_APM', '4.3 );
```

If your extension requires one or more of those plugins to be present, make sure to copy the relevant line(s) inside your `construct()` function. If the extension further requires a specific minimum version, ensure that argument is specified.

#### Example

The Hide Tribe Bar extension requires that The Events Calendar 3.0 or greater is present. This line is the only one needed to specify that:

```
$this->add_required_plugin( 'Tribe__Events__Main', '3.0' );
```

Note: All extensions require Tribe Common 4.3.3 or greater to be active. The Events Calendar 3.0 did not include that library. It would take exotic circumstance for an extension to run on an older version like 3.0. For example, if Event Tickets 4.3.3 was active and working properly, then this version comparison would run. If Event Tickets was not active, nor any other plugin that includes Tribe Common, the Extension would cease running even before a version comparison was made.

### Specify extension URL (optional)

Official extensions have a tutorial and download page on TheEventsCalendar.com. This can be specified as well:

```
$this->set_url( 'https://theeventscalendar.com/extensions/example/' );
```

Replace the URL above with an absolute URL to the extension page.

#### Example

```
$this->set_url( 'https://theeventscalendar.com/extensions/hide-tribe-bar/' );
```

## Step 6) Insert your custom code

Now the fun begins. Insert or begin writing your custom code inside of the init() function:

```
/**
 * Extension initialization and hooks.
 */
public function init() {
    [Insert custom code here.]
}
```

Replace the `[Insert custom code here.]` bit with your code. Many modifications will declare multiple functions. In most cases it makes sense to add those functions as public functions inside of this class. 

#### Example

Like many customizations, our Tribe Bar hider begins by hooking into a filter and specifying a callback function. Since that is where it begins, that code goes inside of `init()`.

```
/**
 * Extension initialization and hooks.
 */
public function init() {
    add_filter( 'tribe_get_template_part_content', array( $this, 'filter_template' ), 10, 5 );
}
 
/**
 * Filters Tribe Bar, but outputs all other template parts.
 *
 * @see (WP filter) tribe_get_template_part_content
 */
public function filter_template( $html, $template, $file, $slug, $name ) {
    if ( 'modules/bar' === $slug ) {
        $html = '';
    }
    return $html;
}
```

Note how the callback was added add as a second public function called `filter_template`. 

## Complete Example

Here is the finished Hide Tribe Bar extension example the above steps walked you through creating.

```
<?php
/**
 * Plugin Name:     The Events Calendar Extension: Hide Tribe Bar
 * Description:     Hides the Tribe Bar that appears above the calendar.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Hide_Tribe_Bar
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */
 
// Do not load directly.
defined( 'WPINC' ) || die;
// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) { return; }
 
/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Hide_Tribe_Bar extends Tribe__Extension {
 
    /**
     * Setup the Extension's properties.
     *
     * This always executes even if the required plugins are not present.
     */
    public function construct() {
        // Each plugin required by this extension
        $this->add_required_plugin( 'Tribe__Events__Main', '3.0' );
 
        // Set the extension's tec.com URL
        $this->set_url( 'https://theeventscalendar.com/extensions/hide-tribe-bar/' );
    }
 
    /**
     * Extension initialization and hooks.
     */
    public function init() {
        add_filter( 'tribe_get_template_part_content', array( $this, 'filter_template' ), 10, 5 );
    }
 
    /**
     * Filters Tribe Bar, but outputs all other template parts.
     *
     * @see (WP filter) tribe_get_template_part_content
     */
    public function filter_template( $html, $template, $file, $slug, $name ) {
        if ( 'modules/bar' === $slug ) {
            $html = '';
        }
        return $html;
    }
}
```

## Notes

### Requirements

The Extension framework requires Tribe Common 4.3.3 or greater to be active on the website. Currently two plugins contain Tribe Common. If neither of these are active on the WordPress install, the extension will cease running without showing any errors or messages:
* [The Events Calendar 4.3.3+](https://wordpress.org/plugins/the-events-calendar/)
* [Event Tickets 4.3.3+](https://wordpress.org/plugins/event-tickets/)

### Standards

Official extensions adhere to the following code standards where applicable:

* [Tribe Products Coding Standards](http://moderntribe.github.io/products-engineering/guidelines/)
* [WordPress version of PHPDoc](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/php/)

### Updating an extension

If you are updating an extension that is already in use by the general public, you will need to update the version number. The Version should follow the [Semantic Versioning](http://semver.org/) standard. The initial public release will always be `1.0.0`. From there the major, minor, and patch versions will be incremented with each release, depending on what type of change was made in the update.

You should alter the version as it appears in the plugin header:
 ```
  * Version: 1.0.1
 ```
In addition you should specify the new version number within the `construct()` function of the Extension Class by inserting or updating this line:

```
$this->set( 'version', '1.0.1' );
```

If you are wondering why you should specify the version number twice when you update an extension, read on. Typically the version number for the extension is read from the plugin header and then cached in the database for optimum performance. Each time an extension is updated or installed via WP Admin, this cache is rebuilt. However, when a manual update occurs the cache might not get rebuilt, and so the Extension Framework will continue thinking the old version of your extension is present. Specifying the version number, as outlined above, will rebuild the cache when an extension is manually updated.

### Separate file for the Extension Class

The `index.php` inside your extension folder should always be present and contain the plugin header. However, you can place the Extension Class within a separate file inside of your extension's plugin folder. If you do this you can tell the Extension Framework to load this class file by adding a new line to the `index.php` plugin header. If your class file is called `extension.php`, this would be the new line:

```
 * Extension File: extension.php
```

With that line in place the Extension Framework will load the specified file before instantiating the Extension Class.

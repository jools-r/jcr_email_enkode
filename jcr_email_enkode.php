<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'jcr_email_enkode';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.3.0';
$plugin['author'] = 'jcr / txpbuilders';
$plugin['author_uri'] = 'https://txp.builders/';
$plugin['description'] = 'Email obfuscator for Textpattern CMS based on Hivelogic Enkoder';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) {
    define('PLUGIN_HAS_PREFS', 0x0001);
} // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) {
    define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);
} // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Import StandalonePHPEnkoder.php library dependency
$library_contents = file_get_contents('standalone-phpenkoder/StandalonePHPEnkoder.php');
// Populate plugin data column with library contents (will be saved as data.txp in plugin dir)
$plugin['data'] = <<<EOF
{$library_contents}
EOF;

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

if (!defined('txpinterface')) {
    @include_once('zem_tpl.php');
}

# --- BEGIN PLUGIN CODE ---
if (txpinterface === 'public') {

    if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
                ->register('jcr_email_enkode')
                ->register('jcr_email_enkode_all')
                ->register('jcr_safe_email')
                ->register('safe_email');
    }

    // Include StandalonePHPEnkoder.php (saved in plugins/jcr_email_enkode/data.txp)
    require_once('data.txp');

    /**
     * Enkode a single mailto: link
     *
     * @param array $atts
     * @return string
     */
    function jcr_email_enkode($atts, $thing = null)
    {
        global $variable, $production_status;

        // Is global ‘contact_email’ address set as a txp variable?
        $default_email = (isset($variable['contact_email'])) ? $variable['contact_email'] : '';

        extract(lAtts(array(
            'email'          => $default_email,
            'class'          => 'email',
            'subject'        => '',
            'link_text'      => '',  // legacy attribute (deprecated)
            'linktext'       => '',
            'bot_msg'        => '',
            'title'          => ''
          ), $atts));

        // Email attribute (or default) is required
        if ($email) {

            // If used as a container tag, use contained HTML as linktext (overrides attribute)
            if ($thing !== null) {

                $linktext = $thing;

            } elseif (empty($linktext)) {

                // Legacy support for link_text attribute (deprecated)
                if (!empty($link_text)) {

                    // Signal that the attribute is deprecated
                    if ($production_status === 'debug') {
                        trigger_error('jcr_email_enkode: link_text attribute deprecated: use linktext attribute instead.', E_USER_NOTICE);
            	    }

                    $linktext = $link_text;

                // If linktext still empty, use the email address (enkoder will handle it too)
                } else {

                    $linktext = $email;
                }

            }

            // Instantiate StandalonePHPEnkoder
            $enkoder = new StandalonePHPEnkoder();

            // Set StandalonePHPEnkoder defaults: class
            if (strlen($class) != 0) {
                $enkoder->enkode_class = txpspecialchars($class);
            }

            // Set StandalonePHPEnkoder defaults: bot_msg
            // also settable as a default ‘email_bot_message’ txp variable
            if (strlen($bot_msg) != 0) {
                $enkoder->enkode_msg = txpspecialchars($bot_msg);
            } elseif (isset($variable['email_bot_message'])) {
                $enkoder->enkode_msg = txpspecialchars($variable['email_bot_message']);
            }

            return $enkoder->enkodeMailto($email, $linktext, rawurlencode($subject), txpspecialchars($title));

        } else {

            // Show error when no email attribute (or default 'contact_email' variable) supplied
            if ($production_status === 'debug') {
                trigger_error('jcr_email_enkode: missing email attribute.', E_USER_NOTICE);
    	    }

        }
    }

    /**
     * Enkode all emails
     * Encodes all mailto: and plaintext links into JavaScript obfuscated text.
     *
     * @param array $atts
     * @return string
     */
    function jcr_email_enkode_all($atts, $thing = null)
    {
        global $variable, $production_status;

        extract(lAtts(array(
            'class'          => 'email',
            'bot_msg'        => ''
          ), $atts));

        if ($thing !== null) {

            // Instantiate StandalonePHPEnkoder
            $enkoder = new StandalonePHPEnkoder();

            // Set StandalonePHPEnkoder defaults: class
            if (strlen($class) != 0) {
                $enkoder->enkode_class = txpspecialchars($class);
            }

            // Set StandalonePHPEnkoder defaults: bot_msg
            // also settable as a default ‘email_bot_message’ txp variable
            if (strlen($bot_msg) != 0) {
                $enkoder->enkode_msg = txpspecialchars($bot_msg);
            } elseif (isset($variable['email_bot_message'])) {
                $enkoder->enkode_msg = txpspecialchars($variable['email_bot_message']);
            }

            return $enkoder->enkodeAllEmails($thing);

        } else {

            // Show error when not used as a container in debug mode
            if ($production_status === 'debug') {
                trigger_error('jcr_email_enkode_all must be used as a container tag', E_USER_NOTICE);
    	    }

        }
    }


    /**
     * Legacy-compatible function aliases
     */
    function safe_email($atts, $thing = null)
    {
        return jcr_email_enkode($atts, $thing);
    }

    function jcr_safe_email($atts, $thing = null)
    {
        return jcr_email_enkode($atts, $thing);
    }

}
# --- END PLUGIN CODE ---
if (0) {
    ?>
<!--
# --- BEGIN PLUGIN CSS ---

# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. jcr_email_enkode

p. *Version:* 0.3.0

p. A simple anti-spam email obfuscator based on the "Hivelogic Enkoder":https://web.archive.org/web/20140110012854/http://hivelogic.com/enkoder/ obfuscation method. It can be used as a drop-in replacement for "txp:email":https://docs.textpattern.com/tags/email.

p. The plugin provides tags for use with individual emails and for container-usage to obfuscate @mailto:@ links and plaintext emails in a block of contained HTML.

h3. How it works

p. The tag helps protect email addresses by converting them into encrypted JavaScript code so only real people using real browsers will see them. It encrypts the email address and converts the result to a self evaluating JavaScript, hiding it from email-harvesting robots which crawl the web looking for exposed addresses. Your address will be displayed correctly by web-browsers, but will be virtually indecipherable to email harvesting robots.

p. Search machines and bots, as well as users with javascript deactivated, see a simple text message. All regular users get a functioning email link.

h3. Obfuscation method

p. This plugin uses "Standalone PHPEnkoder":https://github.com/miranj/standalone-phpenkoder, a PHP implementation of the original now defunct "Hivelogic Enkoder":https://web.archive.org/web/20140110012854/http://hivelogic.com/enkoder/.


h2. Installation

p. Paste the code into the  _Admin › Plugins_ panel, install and enable the plugin.


h2. Plugin tags


h3. jcr_email_enkode

p. Outputs a spam-resistant email link. If used as a container tag, the contained code is used in place of (and overrides) 'linktext'.

p. *email* %(secondary-text)optional%
The email address to use.
Example: @email="your.name[==@==]yourdomain.com"@. Default: the value of the variable named @contact_email@.

p. _Tip: Use txp:variable (or adi_variables / oui_prefs) to set a site-wide variable @contact_email@ with a default contact_email. You can then omit @email@ attribute._

p. *linktext* %(secondary-text)optional%
Optional link text.
Example: @linktext="Contact us"@. Default: the email address.

p. *subject* %(secondary-text)optional%
An optional subject line for the email.
Example: @subject="Online Enquiry"@. Default: none.

p. *class* %(secondary-text)optional%
Change the link class of the @a@ tag.
Example: @class="email-link"@. Default: @email@.

p. *bot_msg* %(secondary-text)optional%
The text shown to bots or search machines.
Example: @bot_msg="my.name at this domain"@. Default: "email hidden; JavaScript is required".

p. _Tip: Use txp:variable (or adi_variables / oui_prefs) to set a site-wide variable @email_bot_message@ with a default bot message of your own._

h3. jcr_email_enkode_all

p. Encodes all mailto: and plaintext links in the contained HTML block into JavaScript obfuscated text.

p. %(highlight)For use as a wrapper tag only% (outputs a warning in debug mode if not used as a wrapper).

p. *class* %(secondary-text)optional%
Change the link class of the @a@ tag.
Example: @class="email-link"@. Default: @email@.

p. *bot_msg* %(secondary-text)optional%
The text shown to bots or search machines.
Example: @bot_msg="my.name at this domain"@. Default: "email hidden; JavaScript is required".

p. _Tip: Use txp:variable (or adi_variables / oui_prefs) to set a site-wide variable @email_bot_message@ with a default bot message of your own._


h2. Examples

h3. Example 1: Enkode a single email address

bc. <txp:jcr_email_enkode email="info@yourdomain.com" linktext="Contact us" subject="Online enquiry" />


h3. Example 2: Using a standard contact email for the site

p. In its simplest form – providing the variable @contact_email@ is set higher up on the page – use:

bc. <txp:variable name="contact_email">enquiries@sitedomain.com</txp:variable>
...
page code
...
<txp:jcr_email_enkode />


h3. Example 3: Enkode all email addresses in an article body

bc. <txp:jcr_email_enkode_all class="o-email-link" bot_msg="[email address removed]">
    <txp:body />
</txp:jcr_email_enkode_all>


h2. Changelog

h3. Version 0.3 – 2020/05/01

* Switched to StandalonePHPEnkoder (using Hivelogic Enkoder's method)
* Renamed tag to @jcr_email_enkode@.
* Added @jcr_email_enkode_all@ tag to be used as a container tag.
* Legacy support for @txp:jcr_safe_email@ / @txp:safe_email@ and 'link_text' attribute.
* Replaced textpack bot message method with customisable @bot_msg@ attribute

h3. Version 0.2 – 2016/10/28

* Switched to jcr_ tag prefix. Old tag name kept for backwards compatability.
* Added subject line attribute
* Made message text customisable as textpack value

h3. Version 0.1 – 2012/08/05

* First release


h2. Credits

* v0.3 - Uses "standalone php enkoder":https://github.com/miranj/standalone-phpenkoder, a PHP implementation of Hivelogic Enkoder as the obfuscation function.
* v0.2 - The underlying "obfuscation function":https://gist.github.com/dougdragon/9513598.

# --- END PLUGIN HELP ---
-->
<?php
}
?>

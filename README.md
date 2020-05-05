# jcr_email_enkode
Email obfuscator for Textpattern CMS based on Hivelogic Enkoder

A simple anti-spam email obfuscator based on the [Hivelogic Enkoder](https://web.archive.org/web/20140110012854/http://hivelogic.com/enkoder/) obfuscation method. It can be used as a drop-in replacement for [txp:email](https://docs.textpattern.com/tags/email).
The plugin provides tags for use with individual emails and for container-usage to obfuscate `mailto:` links and plaintext emails in a block of contained HTML.


### How it works

The tag helps protect email addresses by converting them into encrypted JavaScript code so only real people using real browsers will see them. It encrypts the email address and converts the result to a self evaluating JavaScript, hiding it from email-harvesting robots which crawl the web looking for exposed addresses. Your address will be displayed correctly by web-browsers, but will be virtually indecipherable to email harvesting robots.

Search machines and bots, as well as users with javascript deactivated, see a simple text message. All regular users get a functioning email link.


### Obfuscation method

This plugin uses [Standalone PHPEnkoder](https://github.com/miranj/standalone-phpenkoder), a PHP implementation of the original now defunct [Hivelogic Enkoder](https://web.archive.org/web/20140110012854/http://hivelogic.com/enkoder/).


## Installation

Paste the code into the  *Admin › Plugins* panel, install and enable the plugin.


## Plugin tags


### jcr_email_enkode

Outputs a spam-resistant email link. If used as a container tag, the contained code is used in place of (and overrides) 'linktext'.

**email** *optional*
The email address to use.
Example: `email="your.name@yourdomain.com"`. Default: the value of the variable named `contact_email`.

*Tip: Use txp:variable (or adi_variables / oui_prefs) to set a site-wide variable `contact_email` with a default contact_email. You can then omit `email` attribute.*

**linktext** *optional*
Optional link text.
Example: `linktext="Contact us"`. Default: the email address.

**subject** *optional*
An optional subject line for the email.
Example: `subject="Online Enquiry"`. Default: none.

**class** *optional*
Change the link class of the `a` tag.
Example: `class="email-link"`. Default: `email`.

**bot_msg** *optional*
The text shown to bots or search machines.
Example: `bot_msg="my.name at this domain"`. Default: "email hidden; JavaScript is required".

*Tip: Use txp:variable (or adi_variables / oui_prefs) to set a site-wide variable `email_bot_message` with a default bot message of your own.*

### jcr_email_enkode_all

Encodes all mailto: and plaintext links in the contained HTML block into JavaScript obfuscated text.

*For use as a wrapper tag only* (outputs a warning in debug mode if not used as a wrapper).

**class** *optional*
Change the link class of the `a` tag.
Example: `class="email-link"`. Default: `email`.

**bot_msg** *optional*
The text shown to bots or search machines.
Example: `bot_msg="my.name at this domain"`. Default: "email hidden; JavaScript is required".

*Tip: Use txp:variable (or adi_variables / oui_prefs) to set a site-wide variable `email_bot_message` with a default bot message of your own.*


## Examples

### Example 1: Enkode a single email address


```
<txp:jcr_email_enkode email="info@yourdomain.com" linktext="Contact us" subject="Online enquiry" />
```

### Example 2: Using a standard contact email for the site

In its simplest form – providing the variable `contact_email` is set higher up on the page – use:

```
<txp:variable name="contact_email">enquiries@sitedomain.com</txp:variable>
...
page code
...
<txp:jcr_email_enkode />
```

### Example 3: Enkode all email addresses in an article body

```
<txp:jcr_email_enkode_all class="o-email-link" bot_msg="[email address removed]">
    <txp:body />
</txp:jcr_email_enkode_all>
```
## Changelog

#### Version 0.3 – 2020/05/01

- Switched to StandalonePHPEnkoder (using Hivelogic Enkoder's method)
- Renamed tag to `jcr_email_enkode`.
- Added `jcr_email_enkode_all` tag to be used as a container tag.
- Replaced textpack bot message method with customisable `bot_msg` attribute

#### Version 0.2 – 2016/10/28

- Switched to jcr_ tag prefix. Old tag name kept for backwards compatibility.
- Added subject line attribute
- Made message text customisable as textpack value

#### Version 0.1 – 2012/08/05

- First release


## Credits

- v0.3 - Uses [standalone php enkoder](https://github.com/miranj/standalone-phpenkoder), a PHP implementation of Hivelogic Enkoder as the obfuscation function.
- v0.2 - The underlying [obfuscation function](https://gist.github.com/dougdragon/9513598).

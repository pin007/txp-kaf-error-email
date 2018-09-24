<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'kaf_error_email';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.4';
$plugin['author'] = 'Antonin Faltynek';
$plugin['author_uri'] = 'http://tonda.kaf.cz';
$plugin['description'] = 'Plugin sends alert email to the admin when some error occurs';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

if (!defined('txpinterface'))
  @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (class_exists('\Textpattern\Tag\Registry')) {
  Txp::get('\Textpattern\Tag\Registry')->register('kaf_error_email');
}

function kaf_error_email($atts) {
  global $prefs;

  $default_user = 1;

  extract(lAtts(array(
    'user' => $default_user,
    'extended' => '0'
  ), $atts));

  if (!is_numeric($user)) {
    $user = $default_user;
  }

  $subject = substr(error_status(0),0,3).' error on '.$prefs['sitename'];

  $message = "The following error occured on site ".$prefs['sitename'];
  $message .= "\nError message: ".error_message(0);
  $message .= "\n";
  $message .= "\nURL: ".((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  $message .= "\n";

  if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
    if (isset($_SERVER["HTTP_CLIENT_IP"])) {
      $proxy = $_SERVER["HTTP_CLIENT_IP"];
    } else {
      $proxy = $_SERVER["REMOTE_ADDR"];
    }
    $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
  } else {
    if (isset($_SERVER["HTTP_CLIENT_IP"])) {
      $ip = $_SERVER["HTTP_CLIENT_IP"];
    } else {
      $ip = $_SERVER["REMOTE_ADDR"];
    }
  }

  if(isset($proxy)) $message .= "\nproxy addr: ".$proxy." [whois: http://whois.net/ip-address-lookup/".$proxy."]";
  if(isset($ip)) $message .= "\nclient addr: ".$ip." [whois: http://whois.net/ip-address-lookup/".$ip."]";
  $message .= "\n";

  if ($extended === '1') {
    $server = array('REQUEST_METHOD', 'QUERY_STRING', 'HTTP_REFERER', 'HTTP_USER_AGENT', 'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_CHARSET', 'HTTP_COOKIE', 'HTTP_X_REAL_PORT', 'SERVER_PORT');
  } else {
    $server = array('REQUEST_METHOD', 'HTTP_REFERER', 'HTTP_USER_AGENT');
  }

  foreach ($server as $key) {
    if(isset($_SERVER[$key])) $message .= "\n$key: ".$_SERVER[$key];
  }

  if ($extended === '1') {
    $message .= "\n";
    foreach ($_POST as $key) {
      $message .= "\n_POST[$key]: ".$_POST[$key];
    }

  }

  $rs = safe_row('email', 'txp_users', 'user_id = '.$user);
  $email = $rs['email'];

  txpMail($email, $subject, $message);
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1(title). kaf_error_email

p. This plugin sends alert email to the admin when some error occurs. This can be applied only on errors that are handled by Textpattern (eg. a 404 not found error).

h2(section). usage

p. Place @<txp:kaf_error_email />@ anywhere in your error template.

h2(section). attributes

- user := id of user that will be notified about error, default @1@
- extended := numeric flag whether extended information should be included, default @0@
# --- END PLUGIN HELP ---
-->
<?php
}
?>
<?php

/*
  Plugin Name: Facebook Post
  Plugin URI: https://github.com/ElWardi/qa-facebook-post
  Plugin Description: Notify users for new event via facebook wall post
  Plugin Version: 1.0
  Plugin Date: 2013-08-04
  Plugin Author: Mourad M.
  Plugin Author URI: https://github.com/ElWardi
  Plugin License: GPLv2
  Plugin Minimum Question2Answer Version: 1.5
  Plugin Update Check URI:
 */

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('event', 'qa-facebook-post-event.php', 'qa_facebook_post_event', 'Facebook Post');

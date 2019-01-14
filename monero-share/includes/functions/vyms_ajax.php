<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*** AJAX PHP TO MAKE COOKIE ***/

// register the ajax action for authenticated users
add_action('wp_ajax_vyms_mo_api_action', 'vyms_mo_api_action');

//register the ajax for non authenticated users
add_action( 'wp_ajax_nopriv_vyms_mo_api_action', 'vyms_mo_api_action' );

// handle the ajax request
function vyms_mo_api_action()
{
  global $wpdb; // this is how you get access to the database

  //NOTE: I do not think there is a need for nonce as no user input to wordpress
  
  //If its not clear, this is actually needed an should be left alone. In theory, user could hack a post somehow getting around the vypsnonce, but it just bets what its given and validates.
  $incoming_multiplier = intval( $_POST['multicheck'] );

  //In theory this could be hacked as well if they looked at the VYPS code. An admin could change one of these numbers if they so desire though in their own php.
  //Its better than nothing I guess.
  $incoming_pointid_get = intval(base64_decode( $_POST['pointid'])) - 100256;
  //$incoming_pointid_get = 4;

  wp_die(); // this is required to terminate immediately and return a proper response
}

/*** Fix for the ajaxurl not found with custom template sites ***/
add_action('wp_head', 'myplugin_ajaxurl');

function myplugin_ajaxurl()
{
   echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url('admin-ajax.php') . '";
         </script>';
}

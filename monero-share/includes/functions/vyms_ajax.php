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

  //Post gather from the AJAX post
  $site_wallet = $_POST['site_wallet'];
  $site_worker = $_POST['site_worker'];

  $client_wallet = $_POST['client_wallet'];
  $client_worker = $_POST['client_worker'];

  //Copy and paste from the Shortcodes
  //MO remote get info for client

  //MO remote get info for site
  $mo_site_wallet = $site_wallet;
  $mo_site_worker = $site_worker;

  $mo_client_wallet = $client_wallet;
  $mo_client_worker = $client_worker;

  /*** MoneroOcean Gets***/
  //Site get
  $site_url = 'https://api.moneroocean.stream/miner/' . $mo_site_wallet . '/stats/' . $mo_site_worker;
  $site_mo_response = wp_remote_get( $site_url );
  if ( is_array( $site_mo_response ) )
  {
    $site_mo_response = $site_mo_response['body']; // use the content
    $site_mo_response = json_decode($site_mo_response, TRUE);
    if (array_key_exists('totalHash', $site_mo_response))
    {
        $site_total_hashes = $site_mo_response['totalHash'];
    }
    else
    {
      $site_total_hashes = 0;
    }
  }

  //Client get
  $client_url = 'https://api.moneroocean.stream/miner/' . $mo_client_wallet . '/stats/' . $mo_client_worker;
  $client_mo_response = wp_remote_get( $client_url );
  if ( is_array( $site_mo_response ) )
  {
    $client_mo_response = $client_mo_response['body']; // use the content
    $client_mo_response = json_decode($client_mo_response, TRUE);
    $client_total_hashes = $client_mo_response['totalHash'];
    if (array_key_exists('totalHash', $client_mo_response))
    {
        $client_total_hashes = $client_mo_response['totalHash'];
    }
    else
    {
      $site_total_hashes = 0;
    }
  }

  $mo_array_server_response = array(
      'site_wallet' => $digit_first,
      'second' => $digit_second,
      'third' => $digit_third,
      'fourth' => $digit_fourth,
      'full_numbers' => $rng_numbers_combined,
      'response_text' => $response_text,
      'pre_balance' => $pre_current_user_balance,
      'post_balance' => $post_current_user_balance,
      'reward' => $reward_amount,
  );

  //Get the random 4 digit number. Just testing... will get a better check later.
  //$rng_server_response = $digit_first . $digit_second . $digit_third . $digit_fourth . $response_text;

  echo json_encode($rng_array_server_response); //Proper method to return json

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

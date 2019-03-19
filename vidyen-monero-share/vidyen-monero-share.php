<?php
/*
Plugin Name:  VidYen Monero Share
Plugin URI:   https://wordpress.org/plugins/vidyen-monero-share/
Description:  Share a browser miner with your users so that you both earn XMR
Version:      1.1.2
Author:       VidYen, LLC
Author URI:   https://vidyen.com/
License:      GPLv3
License URI:  https://www.gnu.org/licenses/gpl-3.0.html
*/

/*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, version 3 of the License
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* See <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Adding the menu function
add_action('admin_menu', 'vy_monero_share_menu');

function vy_monero_share_menu()
{
	//Only need to install the one menu to explain shortcode usage
  $parent_page_title = "VY Monero Share";
  $parent_menu_title = 'VY Monero Share';
  $capability = 'manage_options';
  $parent_menu_slug = 'vy_mshare';
  $parent_function = 'vy_mshare_parent_menu_page';
  add_menu_page($parent_page_title, $parent_menu_title, $capability, $parent_menu_slug, $parent_function);
}

//The actual page... I should throw this on its own include. Down the road maybe.
function vy_mshare_parent_menu_page()
{
	//It's possible we don't use the VYPS logo since no points.
  $vy_logo_url = plugins_url( 'includes/images/vy_logo.png', __FILE__ );
  $vy256_worker_url = plugins_url( 'includes/images/vyworker_001.gif', __FILE__ );
  $twitch_icon_url = plugins_url( 'includes/images/icon-256x256.png', __FILE__ );

  //The HTML output.
	echo '<br><br><img src="' . $twitch_icon_url . '" > ';

	//Static text for the base plugin
	echo
	"<h1>Monero Share Monero Miner</h1>
	<p>The plugin uses the <a href=\"https://vidyen.com\" target=\"_blank\">VidYen Monero miner</a> to allows users mine via Javascript on your WordPress page to their own wallet while sharing hashes with you. It ties into the Monero Share JS API and only mines while videos are being played.</p>
	<p>It pulls MoneroOcean stats direct to client via a remote request command so if MoneroOcean is block user they can still mine and get the stats.</p>
	<h2>Shortcode Instructions</h2>
	<p>Format:<b>[vy-mshare wallet=(the sites XMR wallet) site=mshare sitetime=(time you want to mine for you) clienttime=(time to mine for client)]</b></p>
	<p>Again this uses MoneroOcean for the pool like the VidYen Point System.</p>
	<p>To see your progress towards payout, vist the <a href=\"https://moneroocean.stream/#/dashboard\" target=\"_blank\">dashboard</a> and add your XMR wallet where it says Enter Payment Address at bottom of page. There you can see total hashes, current hash rate, and account option if you wish to change payout rate.</p>
	<p>Keep in mind, unlike Coinhive, you can use this in conjunction with GPU miners to the same pool.</p>
	<p>Working Example: <b>[vy-mshare wallet=8BpC2QJfjvoiXd8RZv3DhRWetG7ybGwD8eqG9MZoZyv7aHRhPzvrRF43UY1JbPdZHnEckPyR4dAoSSZazf5AY5SS9jrFAdb site=mshare sitetime=60 clienttime=360]</b>
  <p>Since this is running on our servers and we expanded the code, VidYen, LLC is the one handling the support. Please go to our <a href=\"https://www.vidyen.com/contact/\" target=\"_blank\">contact page</a> or if you need assistance immediatly, join the <a href=\"https://discord.gg/6svN5sS\" target=\"_blank\">VidYen Discord</a> and PM Felty. (It will ping my phone, so do not abuse. -Felty)</p></p>  <h2>Getting a Monero wallet</h2>
  <p>If you are completely new to Monero and need a wallet address, you can quickly get one at <a href=\"https://mymonero.com/\" target=\"_blank\">My Monero</a> or if you want a more technical or secure wallet visit <a href=\"https://ww.getmonero.org/\" target=\"_blank\">Get Monero</a> on how to create an enanched wallet.</p>
  <p>If you have an iPhone you can always use  <a href=\"https://cakewallet.io/\" target=\"_blank\">Cake Wallet</a>.</p>
  <h2>Third Party Services</h2>
  <b>This plugin uses the 3rd party services:</b>
  <p>VidYen, LLC - To run websocket connections between your users client and the pool to distribute hash jobs. <a href=\"https://www.vidyen.com/privacy/\" target=\"_blank\">VidYen Privacy Policy</a></p>
  <p>MoneroOcean - To provide mining stastics and handle the XMR payouts. <a href=\"https://moneroocean.stream/#/help/faq\" target=\"_blank\">MoneroOcean Privacy Policy</a></p>
  <h2>MoneroShare.io Version</h2>
  <p>The version you see on MoneroShare.io website is the end goal, but will be updated down the road.</p>
  <p>To test it use shortcode [mshare-io site=devdonate sitetime=60 clienttime=360]</p>
  <p>In the future, the MoneroShare on WordPress will have a similar interact as the Point System version, but sharing will be done simultaneously</p>
	";

	echo '<br><br><img src="' . $vy256_worker_url . '" > ';
  echo '<br><br><a href="https://vidyen.com" target="_blank"><img src="' . $vy_logo_url . '" ></a>';
  echo '<br><br><a href="https://src.getmonero.org/legal/" target="_blank">Monero Logo</a> used with <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank">Creative Commons Attribution-ShareAlike 4.0 International license</a>';

}

/*** BEGIN SHORTCODE INCLUDES ***/
include( plugin_dir_path( __FILE__ ) . 'includes/shortcodes/vyms_vy256.php'); //For now just the actual SC [vy-twitch]
include( plugin_dir_path( __FILE__ ) . 'includes/shortcodes/mshare-io.php'); //For the site since we own our own stratus (sort of)

/*** BEGIN FUNCTION INCLUDES ***/
include( plugin_dir_path( __FILE__ ) . 'includes/functions/vyms_wallet_check.php'); //Checks if wallet is close to being valid
include( plugin_dir_path( __FILE__ ) . 'includes/functions/ajax/vyms_ajax.php'); //Add ajax to the html to make sure it runs
include( plugin_dir_path( __FILE__ ) . 'includes/functions/ajax/mshare_mo_ajax.php'); //Add ajax to the html to make sure it runs

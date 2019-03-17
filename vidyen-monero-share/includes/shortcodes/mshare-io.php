<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//VY Monero share Shortcode. Note the euphemisms. This avoids adblockers
/** ==Developer Notes==
*** This is intended to make it easier for users who have hard time mining monero to mien monero on your site
*** and the site admin shares hashes with them for giving them a place to host the code. It's free electrcity
*** For the site admin. I will be doing my best to make it so it can't get blocked by fire wall.
*** This code is pretty much a copy and paste of the vy256 for vyps
**/

function monero_share_io_solver_func($atts)
{
    //Short code section
    $atts = shortcode_atts(
        array(
            'wallet' => '',
            'site' => 'default',
            'sitetime' => 60,
            'clienttime' => 360,
            'pool' => 'moneroocean.stream',
            'threads' => '2',
            'throttle' => '50',
            'password' => 'x',
            'cloud' => 0,
            'server' => '', //This and the next three are used for custom servers if the end user wants to roll their own
            'wsport' => '', //The WebSocket Port
            'nxport' => '', //The nginx port... By default its (80) in the browser so if you run it on a custom port for hash counting you may do so here
            'graphic' => 'rand',
            'shareholder' => '',
            'refer' => 0,
            'pro' => '',
            'sitehash' => 256,
            'clienthash' => 1024,
            'hash' => 1024,
            'cstatic' => '',
            'cworker'=> '',
            'timebar' => 'yellow',
            'timebartext' => 'white',
            'sitebar' => '#4c4c4c',
            'clientbar' => '#ff6600',
            'workerbartext' => 'black',
            'redeembtn' => 'Reset',
            'startbtn' => 'Start Mining',
            'maxthreads' => 9,
        ), $atts, 'vyps-256' );

    //NOTE: Where we are going we don't need $wpdb
    $graphic_choice = $atts['graphic'];
    $sm_site_key = $atts['wallet'];
    $sm_site_key_origin = $atts['wallet'];
    $siteName = $atts['site'];
    $siteTime = intval($atts['sitetime']) * 1000; //Time to mine for site. * 1000 for miliseconds 60 * 1000 = 1 minute
    $clientTime = intval($atts['clienttime']) * 1000; //Time to mine for client before going back
    $siteBarTime = intval($atts['sitetime']) * 10; //Interveral time is a bit different here
    $clientBarTime = intval($atts['clienttime']) * 10; //Same
    //$mining_pool = $atts['pool'];
    $mining_pool = 'moneroocean.stream'; //See what I did there. Going to have some long term issues I think with more than one pool support
    $sm_threads = $atts['threads'];
    $sm_throttle = $atts['throttle'];
    $max_threads = $atts['maxthreads'];
    $password = $atts['password']; //For the setting, but perhaps that is client side?
    $password = 'x';
    $first_cloud_server = $atts['cloud'];

    //Check current page. We need this for a get.
    global $wp;
    $current_wp_page = home_url( $wp->request );

    //Custom Graphics variables for the miner. Static means start image, custom worker just means the one that goes on when you hit start
    $custom_worker_stat = $atts['cstatic'];
    $custom_worker = $atts['cworker'];

    //Colors for the progress bars and text
    $timeBar_color = $atts['timebar'];
    $workerBar_text_color = $atts['timebartext'];
    $siteBar_color = $atts['sitebar'];
    $clientBar_color = $atts['clientbar'];
    $workerBar_text_color = $atts['workerbartext'];

    //De-English-fication section. As we have a great deal of non-english admins, I wanted to add in options to change the miner text hereby
    $redeem_btn_text = $atts['redeembtn']; //By default 'Redeem'
    $start_btn_text = $atts['startbtn']; //By default 'Start Mining'

    //Cloud Server list array. I suppose one could have a non-listed server, but they'd need to be running our versions
    //the cloud is on a different port but that is only set in nginx and can be anything really as long as it goes to 8282
    //I added cadia.vy256.com as a last stand. I realized if I'm switching servers cadia needs to be ready to stand.
    //NOTE: Cadia stands.

    //Here is the user ports. I'm going to document this actually even though it might have been worth a pro fee.
    $custom_server = $atts['server'];
    $custom_server_ws_port = $atts['wsport'];
    $custom_server_nx_port = $atts['nxport'];

    //OK going to do a shuffle of servers to pick one at random from top.
    if(empty($custom_server))
    {
      $server_name = array(
            array('employee.moneroshare.io', '2053'), //0,0 0,1
            array('employee.moneroshare.io', '2053'), //0,0 0,1
      );

      shuffle($server_name);

      //Pick the first of the list by default
      $public_remote_url = $server_name[0][0]; //Defaults for one server.
      $used_server = $server_name[0][0];
      $used_port = $server_name[0][1];
      $remote_url = "https://" .$used_server.':'.$used_port; //Should be wss so https://

      $js_servername_array = json_encode($server_name); //the JavaScript needs
    }
    else //Going to allow for custom servers is admin wants. No need for redudance as its on them.
    {
      $server_name = array(
          array($custom_server, $custom_server_ws_port), //0,0 0,1
      );

      $public_remote_url = $server_name[0][0]; //Defaults for one server.
      $used_server = $server_name[0][0];
      $used_port = $server_name[0][1];
      $remote_url = "https://" . $server_name[0][0].':'.$custom_server_ws_port; //Should be wss so https://

      $js_servername_array = json_encode($server_name); //Custom servers need the json array too
    }

    //Here we set the arrays of possible graphics. Eventually this will be a slew of graphis. Maybe holidy day stuff even.
    $graphic_list = array(
          '0' => 'vyworker_blank.gif',
          '1' => 'vyworker_001.gif',
          '2' => 'vyworker_002.gif',
          '3' => 'vyworker_003.gif',
          '4' => 'vyworker_003.gif',
          '3' => 'vyworker_004.gif',
          '4' => 'vyworker_004.gif',
    );

    //By default the shortcode is rand unless specified to a specific. 0 turn it off to a blank gif. It was easier that way.
    if ($graphic_choice == 'rand')
    {
      $rand_choice = mt_rand(1,4);
      $current_graphic = $graphic_list[$rand_choice]; //Originally this one line but may need to combine it later
    }
    else
    {
      $current_graphic = $graphic_list[$graphic_choice];
    }

    if ($siteName == '')
    {
        return "Error: Site name not set. This is required!";
    }
    else
    {
        $site_warning = '';
    }

    //This variable needs to be set for prosperity regardless of POST value
    $xmr_address_form_html = '
    <form method="get">
      XMR Wallet Address:<br>
      <input type="text" name="xmrwallet" value="" required>
      <br>
      Worker Name:<br>
      <input type="text" name="workername" value="worker" required>
      <br>
      <input type="hidden" name="action" id="action" value="goconsent">
      <br><br>
      <input type="submit" value="Submit">
    </form>
      ';

    //Something that annoying me. Going to error check to see if someone messing with posts. NOTE: all three must be set
    if (!isset($_GET['xmrwallet']) AND !isset($_GET['worker']))
    {
      return $xmr_address_form_html; //Just return the above with defaults. Have no clue who is messing with the posts Else continue.
    }

    //See if reset GET has been called
    if (isset($_GET['action'])) //Hook in here if user hit reset button/ We assume that tis set above
    {

      if ($_GET['action']=='reset')
      {
        //Sanitize the GETS
        $get_wallet = sanitize_text_field($_GET['xmrwallet']);
        $get_worker = sanitize_text_field($_GET['worker']);
        $get_threads = intval($_GET['threads']);

        //Some bad Greygoose and coding here. I would like to make the above recycled, but time constrained.
        $xmr_get_address_form_html = '
        <form method="get">
          XMR Wallet Address:<br>
          <input type="text" name="xmrwallet" value="' . $get_wallet . '" required>
          <br>
          Worker Name:<br>
          <input type="text" name="workername" value="' . $get_worker . '" required>
          <br>
          <input type="hidden" name="action" id="action" value="goconsent">
          <br><br>
          <input type="submit" value="Submit">
        </form>
          ';

        return $xmr_get_address_form_html;
      }
    }

    //Check to see if action=goconsent
    if (isset($_GET['action']))
    {
      if($_GET['action'] != 'goconsent')
      {
        return $xmr_address_form_html; //if not you get the form again
      }
    }

    if (isset($_GET['xmrwallet']))
    {
      //Check to see if the walelt is actually validate
      $wallet = sanitize_text_field($_GET['xmrwallet']);

      if (vyms_wallet_check_func($wallet) == 3) //This means that the wallet lenght was no longer than 90 characters
      {
        $html_output_error = '<p>Error: Wallet Address not longer than 90! Possible invalid XMR Address!</p>'; //Error output

        return $html_output_error . $xmr_address_form_html; //Return both the error along with original form.
      }
      elseif (vyms_wallet_check_func($wallet) == 2) //This means the wallet does not start with a 4 or 8
      {
        $html_output_error = '<p> Error: Wallet address does not start with 4 or 8 so most likley an invalid XMR address!</p>'; //Error output
        return $html_output_error . $xmr_address_form_html; //Return both the error along with original form.
      }
      elseif (vyms_wallet_check_func($wallet) != 1)
      {
        $html_output_error = '<p> Error: Uknown error!</p>'; //Error output
        return $html_output_error . $xmr_address_form_html; //Return both the error along with original form.
      }
      else
      {
        $mo_client_wallet = $wallet; //Extra jump but should be fine now
      }
    }

      //code to set the worker name as user instead of the WordPress name (no tracking)
      if (isset($_GET['workername']))
      {
        $mo_client_worker = sanitize_text_field($_GET['workername']);
      }
      else
      {
        $mo_client_worker = 'worker';
      }

      //NOTE: FIX THIS!
      //loading the graphic url
      $VYPS_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . $current_graphic; //Now with dynamic images!
      $VYPS_stat_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . 'stat_'. $current_graphic; //Stationary version!
      $VYPS_power_url = plugins_url( 'images/', dirname(__FILE__) ) . 'powered_by_vyps.png'; //Well it should work out.

      $VYPS_power_row = "<tr><td colspan=\"2\">Powered by <a href=\"https://wordpress.org/plugins/vidyen-point-system-vyps/\" target=\"_blank\"><img src=\"$VYPS_power_url\" alt=\"Powered by VYPS\"></a></td></tr>";

      //NOTE: In theory I could just use the Monero logo?
      $reward_icon = plugins_url( 'images/', dirname(__FILE__) ) . 'moneroocean_icon.png'; //Well it should work out.
      $reward_icon_html = '<a href="https://moneroocean.stream/#/dashboard?addr='.$mo_client_wallet.'" target="_blank"><img src="'.$reward_icon.'" alt="Monero" height="16" width="16"></a>';

      $miner_id = 'worker_' . $mo_client_worker . '_' . $user_wallet . '_' . $siteName;

      //Get the url for the solver
      $vy256_client_folder_url = plugins_url( 'js/solver319/', __FILE__ );
      $vy256_site_folder_url = plugins_url( 'js/solver319/', __FILE__ );
      //$vy256_solver_url = plugins_url( 'js/solver/miner.js', __FILE__ ); //Ah it was the worker.

      //Need to take the shortcode out. I could be wrong. Just rip out 'shortcodes/'
      $vy256_client_folder_url = str_replace('shortcodes/', '', $vy256_client_folder_url); //having to remove the folder depending on where you plugins might happen to be
      $vy256_site_folder_url = str_replace('shortcodes/', '', $vy256_client_folder_url); //Same
      $vy256_solver_js_url =  $vy256_client_folder_url. 'solver.js';
      $vy256_solver_worker_url = $vy256_client_folder_url. 'worker.js';

      //Second MINER
      $vy256_site_js_url =  $vy256_site_folder_url. 'solver.js';
      $vy256_site_worker_url = $vy256_site_folder_url. 'worker.js';

      //MO remote get info for site
      $mo_site_worker = $siteName;
      $mo_site_wallet = $sm_site_key;

      //This has been bothering me
      $start_button_html ="
        <form id=\"startb\" style=\"display:block;width:100%;\"><input type=\"reset\" style=\"width:100%;\" onclick=\"start()\" value=\"$start_btn_text\"/></form>
        <form id=\"stop\" style=\"display:none;width:100%;\" method=\"get\"><input type=\"hidden\" value=\"\" name=\"consent\"/>
          <input type=\"hidden\" id=\"xmrwallet\" name=\"xmrwallet\" value=\"$mo_client_wallet\">
          <input type=\"hidden\" id=\"worker\" name=\"worker\" value=\"$mo_client_worker\">
          <input type=\"hidden\" id=\"threads\" name=\"threads\" value=\"$sm_threads\">
          <input type=\"hidden\" id=\"reset\" name=\"action\" value=\"reset\">
          <input type=\"submit\" style=\"width:100%;\" class=\"button - secondary\" value=\"$redeem_btn_text\"/>
        </form>
      ";

      /*** MoneroOcean Gets***/
      //Client gets
      $client_url = 'https://api.moneroocean.stream/miner/' . $mo_client_wallet . '/stats/' . $mo_client_worker;
      $client_mo_response = wp_remote_get( $client_url );
      if ( is_array( $site_mo_response ) )
      {
        $client_mo_response = $client_mo_response['body']; // use the content
        $client_mo_response = json_decode($client_mo_response, TRUE);
        if (array_key_exists('totalHash', $client_mo_response))
        {
            $client_total_hashes = floatval($client_mo_response['totalHash']);
            $client_hash_per_second = intval($client_mo_response['hash']); //If the hashrate is bigger than what an int can be, we got bigger problems
        }
        else
        {
          $client_total_hashes = 0;
          $client_hash_per_second = 0;
        }
      }

      $start_message_verbage = 'Press Start to begin.';

      $mshare_worker_html = "<tr><td colspan=\"2\"><div align=\"center\">Monero Share Stats</dv></td></tr>
      <tr>
        <td><div>Threads:</div></td>
        <td><div id=\"threads\">$sm_threads</div></td>
      </tr>
      <tr>
        <td><div>Job Count:</div></td>
        <td><div id=\"attempted_jobs\">0</div></td>
      </tr>
      <tr style=\"display:none;\">
        <td><div>Finished:</div></td>
        <td><div id=\"solved_jobs\">0</div></td>
      </tr>
      <tr style=\"display:none;\">
        <td><div>Accepted:</div></td>
        <td><div id=\"accepted_hashes\">0</div></td>
      </tr>";
      $mo_client_html_output = "<tr>
        <td colspan=\"2\"><div align=\"center\">MoneroOcean Stats</dv></td>
      </tr>
      <tr>
        <td><div><a href=\"https://moneroocean.stream/#/dashboard?addr=$mo_client_wallet\" target=\"_blank\">Your Info</a></div></td>
        <td><div id=\"client_info\">Worker: $mo_client_worker</div></td>
      </tr>
      <tr>
        <td><div>Average Speed:</div></td>
        <td><div id=\"client_hash_per_second\">(Please Wait)</div></td>
      </tr>
      <tr>
        <td><div>Total Hashes:</div></td>
        <td><div id=\"client_hashes\">(Please Wait)</div></td>
      </tr>";
      $mo_site_html_output = "<tr>
        <td><div><a href=\"https://moneroocean.stream/#/dashboard?addr=$mo_site_wallet\" target=\"_blank\">Site Info</a></div></td>
        <td><div id=\"client_info\">Worker: $mo_site_worker</div></td>
      </tr>
      <tr>
        <td><div>Average Speed:</div></td>
        <td><div id=\"site_hash_per_second\">(Please Wait)</div></td>
      </tr>
      <tr>
        <td><div>Total Hashes:</div></td>
        <td><div id=\"site_hashes\">(Please Wait)</div></td>
      </tr>";

      //Time bars
      $site_progress_time = intval($atts['sitetime']);
      $client_progress_time = intval($atts['clienttime']);

      $mo_ajax_html_output = "
        <script>
          function pull_mo_stats()
          {
            jQuery(document).ready(function($) {
             var data = {
               'action': 'vyms_mo_api_action',
               'site_wallet': '$mo_site_wallet',
               'site_worker': '$mo_site_worker',
               'client_wallet': '$mo_client_wallet',
               'client_worker': '$mo_client_worker',
             };
             // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
             jQuery.post(ajaxurl, data, function(response) {
               output_response = JSON.parse(response);
               document.getElementById('site_hashes').innerHTML = output_response.site_hashes;
               document.getElementById('site_hash_per_second').innerHTML = output_response.site_hash_per_second + ' H/s';
               document.getElementById('client_hashes').innerHTML = output_response.client_hashes;
               document.getElementById('client_hash_per_second').innerHTML = output_response.client_hash_per_second + ' H/s';
             });
            });
          }

          //Refresh the MO
          function moAjaxTimerPrimus()
          {
            //Should call ajax every 30 seconds
            var ajaxTime = 1;
            var id = setInterval(moAjaxTimeFrame, 1000); //1000 is 1 second
            function moAjaxTimeFrame() {
              if (ajaxTime >= 30) {
                pull_mo_stats();
                console.log('Ping MoneroOcean');
                clearInterval(id);
                moAjaxTimerSecondus()
              } else {
                ajaxTime++;
              }
            }
          }

          //Refresh the MO
          function moAjaxTimerSecondus()
          {
            //Should call ajax every 30 seconds
            var ajaxTime = 1;
            var id = setInterval(moAjaxTimeFrame, 1000);
            function moAjaxTimeFrame() {
              if (ajaxTime >= 30) {
                pull_mo_stats();
                console.log('Ping MoneroOcean');
                clearInterval(id);
                moAjaxTimerPrimus();
              } else {
                ajaxTime++;
              }
            }
          }
          </script>";

          /*** Below is the copy and paste of the new vy256 code ***/

          //Get the url for the solver
          $vy256_solver_folder_url = plugins_url( 'js/solver319/', __FILE__ );
          //$vy256_solver_url = plugins_url( 'js/solver/miner.js', __FILE__ ); //Ah it was the worker.

          //Need to take the shortcode out. I could be wrong. Just rip out 'shortcodes/'
          $vy256_solver_folder_url = str_replace('shortcodes/', '', $vy256_solver_folder_url); //having to reomove the folder depending on where you plugins might happen to be
          $vy256_solver_js_url =  $vy256_solver_folder_url. 'solver.js';
          $vy256_solver_worker_url = $vy256_solver_folder_url. 'worker.js';

          //NOTE: This is needed for the start button.
          $switch_pause_div_on = "document.getElementById(\"pauseProgress\").style.display = 'none'; // hide pause
          document.getElementById(\"timeProgress\").style.display = 'block'; // begin time";

          if($player_mode != TRUE)
          {
            $graphics_html_ouput= "
              <tr><td>
                <div id=\"waitwork\">
                <img src=\"$VYPS_stat_worker_url\"><br>
                </div>
                <div style=\"display:none;\" id=\"atwork\">
                <img src=\"$VYPS_worker_url\"><br>
                </div>
                <center id=\"mining\" style=\"display:none;\">
                </center>
              </td></tr>
            ";
          }
          else
          {
            $graphics_html_ouput = "
            <div id=\"waitwork\" style=\"display:none;\"></div>
            <div style=\"display:none;\" id=\"atwork\"></div>
            <center id=\"mining\" style=\"display:none;\">
            </center>";
          }

          //Ok some issues we need to know the path to the js file so will have to ess with that.
          $simple_miner_output = "
          <!-- $public_remote_url -->
            $site_warning
            $graphics_html_ouput
              <script>
                      function get_worker_js()
                {
                    return \"$vy256_solver_worker_url\";
                }

                </script>
              <script src=\"$vy256_solver_js_url\"></script>
              <script>

                function get_user_id()
                {
                    return \"$miner_id\";
                }
                var sendstackId = 0;
                function clearSendStack(){
                  clearInterval(sendstackId);
                }

                throttleMiner = $sm_throttle;

                //This needs to happen on start to init.
                var server_list = $js_servername_array;
                var current_server = server_list[0][0];
                console.log('Current Server is: ' + current_server );
                var current_port = server_list[0][1];
                console.log('Current port is: ' + current_port );

                //This repicks server, does not fire unless error in connecting to server.
                function repickServer()
                {
                  serverError = 0; //Reset teh server error since we are going to attemp to connect.

                  document.getElementById('status-text').innerText = 'Error Connecting! Attemping other servers please wait.'; //set to working

                  " . /*//https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array*/ "
                  function shuffle(array) {
                    var currentIndex = array.length, temporaryValue, randomIndex;

                    // While there remain elements to shuffle...
                    while (0 !== currentIndex) {

                      // Pick a remaining element...
                      randomIndex = Math.floor(Math.random() * currentIndex);
                      currentIndex -= 1;

                      // And swap it with the current element.
                      temporaryValue = array[currentIndex];
                      array[currentIndex] = array[randomIndex];
                      array[randomIndex] = temporaryValue;
                    }

                    return array;
                  }

                  server_list = shuffle(server_list); //Why is it alwasy simple?

                  console.log('Shuff Results: ' + server_list );
                  current_server = server_list[0][0];
                  console.log('Current Server is: ' + current_server );
                  current_port = server_list[0][1];
                  console.log('Current port is: ' + current_port );

                  //Reset the server.
                  server = 'wss://' + current_server + ':' + current_port;

                  //Restart the serer. NOTE: The startMining(); has a stopMining(); in it in the js files.
                  startMining(\"$mining_pool\",
                    \"$mo_client_wallet.$mo_client_worker\", \"$password\", $sm_threads, \"$miner_id\");
                }

                function start()
                {
                  //This needs to happen on start to init.
                  var server_list = $js_servername_array;
                  var current_server = server_list[0][0];
                  console.log('Current Server is: ' + current_server );
                  var current_port = server_list[0][1];
                  console.log('Current port is: ' + current_port );


                  //Start the MO pull
                  moAjaxTimerPrimus();
                  pull_mo_stats();
                  console.log('Ping MoneroOcean');

                  //Switch on animations and bars.
                  $switch_pause_div_on
                  document.getElementById(\"startb\").style.display = 'none'; // disable button
                  document.getElementById(\"waitwork\").style.display = 'none'; // disable button
                  document.getElementById(\"atwork\").style.display = 'block'; // disable button
                  document.getElementById(\"redeem\").style.display = 'block'; // disable button
                  document.getElementById(\"thread_manage\").style.display = 'block'; // disable button
                  document.getElementById(\"stop\").style.display = 'block'; // disable button
                  document.getElementById(\"mining\").style.display = 'block'; // disable button

                  document.getElementById('status-text').innerText = 'Working.'; //set to working

                  /* start mining, use a local server */
                  server = 'wss://' + current_server + ':' + current_port;
                  startMining(\"$mining_pool\",
                    \"$mo_client_wallet.$mo_client_worker\", \"$password\", $sm_threads, \"$miner_id\");

                  /* keep us updated */

                  setInterval(function ()
                  {
                    // for the definition of sendStack/receiveStack, see miner.js
                    while (sendStack.length > 0) addText((sendStack.pop()));
                    while (receiveStack.length > 0) addText((receiveStack.pop()));
                  }, 2000);

                  //Order of operations issue. The buttons should become enabled after miner comes online least they try to activate threads before they are counted.
                  document.getElementById('thread_count').innerHTML = Object.keys(workers).length;
                }

                function stop()
                {
                    deleteAllWorkers();
                    document.getElementById(\"stop\").style.display = 'none'; // disable button
                }

                /* helper function to put text into the text field.  */

                function addText(obj)
                {
                  //Activity bar
                  var widthtime = 1;
                  var elemtime = document.getElementById(\"timeBar\");
                  var idtime = setInterval(timeframe, 3600);

                  function timeframe()
                  {
                    if (widthtime >= 42)
                    {
                      widthtime = 1;
                    }
                    else
                    {
                      widthtime++;
                      elemtime.style.width = widthtime + '%';
                    }
                  }

                  //Adding back in console logs.
                  if (obj.identifier === \"job\")
                  {
                    console.log(\"new job: \" + obj.job_id);
                    console.log(\"current algo: \" + job.algo);
                    document.getElementById('status-text').innerText = 'New job using ' + job.algo + ' algo.';
                    setTimeout(function(){ document.getElementById('status-text').innerText = 'Working.'; }, 3000);
                  }
                  else if (obj.identifier === \"solved\")
                  {
                    console.log(\"solved job: \" + obj.job_id);
                    document.getElementById('status-text').innerText = 'Finished job.';
                    setTimeout(function(){ document.getElementById('status-text').innerText = 'Working.'; }, 3000);
                  }
                  else if (obj.identifier === \"hashsolved\")
                  {
                    console.log(\"pool accepted hash!\");
                    document.getElementById('status-text').innerText = 'Pool accepted job.';
                    setTimeout(function(){ document.getElementById('status-text').innerText = 'Working.'; }, 3000);
                  }
                  else if (obj.identifier === \"error\")
                  {
                    console.log(\"error: \" + obj.param);
                    document.getElementById('status-text').innerText = 'Error.';
                  }
                  else
                  {
                    //console.log(obj);
                  }
              }
        </script>
        <script>
        var dots = window.setInterval( function() {
            var wait = document.getElementById(\"wait\");
            if ( wait.innerHTML.length > 3 )
                wait.innerHTML = \".\";
            else
                wait.innerHTML += \".\";
            }, 500);
        </script>
        <tr>
           <td>
             <div>
              $start_button_html
            </div><br>
            <div id=\"pauseProgress\" style=\"width:100%; background-color: grey; \">
              <div id=\"pauseBar\" style=\"width:1%; height: 30px; background-color: $timeBar_color;\"><div style=\"position: absolute; right:12%; color:$workerBar_text_color;\"><span id=\"pause-text\">$start_message_verbage</span></div></div>
            </div>
            <div id=\"timeProgress\" style=\"display:none;width:100%; background-color: grey; \">
              <div id=\"timeBar\" style=\"width:1%; height: 30px; background-color: $timeBar_color;\"><div style=\"position: absolute; right:12%; color:$workerBar_text_color;\"><span id=\"status-text\">Spooling up.</span><span id=\"wait\">.</span><span id=\"hash_rate\"></span></div></div>
            </div>
            <div id=\"workerProgress\" style=\"width:100%; background-color: white; \">
              <div id=\"workerBar\" style=\"width:0%; height: 30px; background-color: $workerBar_color; c\"><div id=\"progress_text\"style=\"position: absolute; right:12%; color:$workerBar_text_color;\">Valid Shares[$reward_icon_html 0] - Session Hashes[0] - Worker Hashes[0]</div></div>
            </div>
            <div id=\"thread_manage\" style=\"display:inline;margin:5px !important;display:block;\">
              <form style=\"display:inline;\"><input type=\"reset\" onclick=\"removethreads()\" value=\"-\"/></form>
              Threads:&nbsp;<span style=\"display:inline;\" id=\"thread_count\">0</span>
              <form style=\"display:inline;position:absolute;right:50px;\"><input type=\"reset\" onclick=\"addthreads()\" value=\"+\"/></form>
              <form method=\"post\" style=\"display:none;margin:5px !important;\" id=\"redeem\">
                <input type=\"hidden\" value=\"\" name=\"redeem\"/>
                <!--<input type=\"submit\" class=\"button-secondary\" value=\"Hashes\" onclick=\"return confirm('Did you want to sync your mined hashes with this site?');\" />-->
              </form>
            </div>
          <tr>
            <td>
              <div class=\"slidecontainer\">
                <p>CPU Power: <span id=\"cpu_stat\"></span>%</p>
                <input type=\"range\" min=\"0\" max=\"100\" value=\"$sm_throttle\" class=\"slider\" id=\"cpuRange\">
              </div>
            </td>
          </tr>
          <script>
            //CPU throttle
            var slider = document.getElementById(\"cpuRange\");
            var output = document.getElementById(\"cpu_stat\");
            output.innerHTML = slider.value;

            slider.oninput = function()
            {
              output.innerHTML = this.value;
              throttleMiner = 100 - this.value;
              console.log(throttleMiner);
            }
          </script>
              <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js\"></script>
              <script>
                  function addthreads()
                  {
                    if( Object.keys(workers).length < $max_threads  && Object.keys(workers).length > 0) //The Logic is that workers cannot be zero and you mash button to add while the original spool up
                    {
                      addWorker();
                      document.getElementById('thread_count').innerHTML = Object.keys(workers).length;
                      console.log(Object.keys(workers).length);
                    }
                  };

                  function removethreads()
                  {
                    if( Object.keys(workers).length > 1)
                    {
                      removeWorker();
                      document.getElementById('thread_count').innerHTML = Object.keys(workers).length;
                      console.log(Object.keys(workers).length);
                    }
                  };
                </script>
            </td></tr>";

            //MO ajax js to put add.
            $mo_timer_html_output = "
              <script>
                var progresspoints = 0; //Global needed for something else
                var activity_progresspoints = 0;
                var totalpoints = 0;
                var progresswidth = 0;
                var totalhashes = 0; //NOTE: This is a notgiven688 variable.
                var mo_totalhashes = 0;
                var valid_shares = 0;
                var prior_totalhashes = 0;
                var hash_per_second_estimate = 0;
                var reported_hashes = 0;
                var elemworkerbar = document.getElementById(\"workerBar\");
                var mobile_use = 1;

                if( navigator.userAgent.match(/iPhone/i)
                 || navigator.userAgent.match(/iPad/i)
                 || navigator.userAgent.match(/iPod/i) )
                {
                  mobile_use = 100;
                  console.log('Mobile WASM mode enabled.');
                }

                function pull_mo_stats()
                {
                  jQuery(document).ready(function($) {
                   var data = {
                     'action': 'mshare_mo_api_action',
                     'site_wallet': '$mo_client_wallet',
                     'site_worker': '$mo_client_worker',
                   };
                   // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                   jQuery.post(ajaxurl, data, function(response) {
                     output_response = JSON.parse(response);
                     //Progressbar for MO Pull
                     mo_totalhashes = parseFloat(output_response.site_hashes);
                     if (mo_totalhashes > totalhashes)
                     {
                       //totalhashes = totalhashes + mo_totalhashes;
                       console.log('MO Hashes were greater.');
                     }
                     valid_shares = parseFloat(output_response.site_validShares);
                     document.getElementById('progress_text').innerHTML = 'Valid Shares[$reward_icon_html ' + valid_shares + '] - Session Hashes[' + totalhashes + '] - Worker Hashes[' + mo_totalhashes + ']';
                   });
                  });
                }

                //Refresh the MO
                function moAjaxTimerPrimus()
                {
                  //Should call ajax every 30 seconds
                  var ajaxTime = 1;
                  var id = setInterval(moAjaxTimeFrame, 1000); //1000 is 1 second
                  function moAjaxTimeFrame()
                  {
                    if (ajaxTime >= 30)
                    {
                      pull_mo_stats();
                      console.log('Ping MoneroOcean');
                      ajaxTime = 1;
                      console.log('AjaxTime Reset');
                      progresswidth = 0;
                      //moAjaxTimerSecondus();
                    }
                    else
                    {
                      ajaxTime++;
                      document.getElementById('thread_count').innerHTML = Object.keys(workers).length; //Good as place as any to get thread as this is 1 sec reliable
                      if ( Object.keys(workers).length > 1)
                      {
                        //document.getElementById(\"add\").disabled = false; //enable the + button
                        //document.getElementById(\"sub\").disabled = false; //enable the - button
                      }
                      elemworkerbar.style.width = progresswidth + '%';
                      document.getElementById('progress_text').innerHTML = 'Valid Shares[$reward_icon_html ' + valid_shares + '] - Session Hashes[' + totalhashes + '] - Worker Hashes[' + mo_totalhashes + ']';
                    }
                    //Hash work
                    hash_difference = totalhashes - prior_totalhashes;
                    hash_per_second_estimate = (hash_difference)/mobile_use;
                    reported_hashes = Math.round(totalhashes / mobile_use);
                    prior_totalhashes = totalhashes;
                    document.getElementById('progress_text').innerHTML = 'Valid Shares[$reward_icon_html ' + valid_shares + '] - Session Hashes[' + totalhashes + '] - Worker Hashes[' + mo_totalhashes + ']';
                    document.getElementById('hash_rate').innerHTML = ' ' + hash_per_second_estimate + ' H/s';
                    elemworkerbar.style.width = progresswidth + '%'

                    //Check server is up
                    if (serverError > 0)
                    {
                      console.log('Server is down attempting to repick!');
                      repickServer();
                      console.log('Server repicked!');
                    }
                  }
                }
                </script>";

      $monero_ocean_link = '<tr><td>'.$reward_icon_html.' <a href="https://moneroocean.stream/#/dashboard?addr='.$mo_client_wallet.'" target="_blank">MoneroOcean Payout Options and Statistics</a> '.$reward_icon_html.'</td></tr>';

      $final_return = '<table>' . $simple_miner_output . $mo_timer_html_output . $monero_ocean_link . $VYPS_power_row . '</table>'; //The power row is a powered by to the other items. I'm going to add this to the other stuff when I get time.

    return $final_return;
}

/*** Add Shortcode to WordPress ***/
add_shortcode( 'mshare-io', 'monero_share_io_solver_func');

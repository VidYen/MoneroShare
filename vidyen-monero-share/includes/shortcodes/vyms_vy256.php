<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//VY Monero share Shortcode. Note the euphemisms. This avoids adblockers
/** ==Developer Notes==
*** This is intended to make it easier for users who have hard time mining monero to mien monero on your site
*** and the site admin shares hashes with them for giving them a place to host the code. It's free electrcity
*** For the site admin. I will be doing my best to make it so it can't get blocked by fire wall.
*** This code is pretty much a copy and paste of the vy256 for vyps
**/

function vy_monero_share_solver_func($atts)
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
            'workerbartext' => 'white',
            'redeembtn' => 'Reset',
            'startbtn' => 'Start Mining',
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
    //$password = $atts['password']; //Note: We will need to fix this but for now the password must remain x for the time being. Hardcoded even.
    $password = 'x';

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

    //Here is the user ports. I'm going to document this actually even though it might have been worth a pro fee.
    $custom_server = $atts['server'];
    $custom_server_ws_port = $atts['wsport'];
    $custom_server_nx_port = $atts['nxport'];

    $used_server = 'employee.moneroshare.io';
    $used_port = '2053';

    //Here we set the arrays of possible graphics. Eventually this will be a slew of graphis. Maybe holidy day stuff even.
    $graphic_list = array(
          '0' => 'vyworker_blank.gif',
          '1' => 'vyworker_001.gif',
          '2' => 'vyworker_002.gif',
          '3' => 'vyworker_003.gif',
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

    if ($sm_site_key == '' AND $siteName == '')
    {
        return "Error: Wallet address and site name not set. This is required!";
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
      Threads:<br>
      <input type="number" name="threads" min="1" max="10" step="1" value="1" required>
      <br>
      <input type="hidden" name="action" id="action" value="goconsent">
      <br><br>
      <input type="submit" value="Submit">
    </form>
      ';

    //Something that annoying me. Going to error check to see if someone messing with posts. NOTE: all three must be set
    if (!isset($_GET['xmrwallet']) AND !isset($_GET['worker']) AND !isset($_GET['threads']))
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
          Threads:<br>
          <input type="number" name="threads" min="1" max="10" step="1" value="' . $get_threads . '" required>
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
        $user_wallet = $wallet; //Extra jump but should be fine now
      }
    }

      //code to set the worker name as user instead of the WordPress name (no tracking)
      if (isset($_GET['workername']))
      {
        $current_user_id = sanitize_text_field($_GET['workername']);
      }
      else
      {
        $current_user_id = 'worker';
      }

      //code to set the threads as user instead of the WordPress name (no tracking)
      if (isset($_GET['threads']))
      {
          $sm_threads = intval($_GET['threads']);
      }
      else
      {
        $sm_threads = 1;
      }

      //NOTE: FIX THIS!
      //loading the graphic url
      $VYPS_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . $current_graphic; //Now with dynamic images!
      $VYPS_stat_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . 'stat_'. $current_graphic; //Stationary version!
      $VYPS_power_url = plugins_url( 'images/', dirname(__FILE__) ) . 'powered_by_vyps.png'; //Well it should work out.

      $VYPS_power_row = "<tr><td colspan=\"2\">Powered by <a href=\"https://wordpress.org/plugins/vidyen-point-system-vyps/\" target=\"_blank\"><img src=\"$VYPS_power_url\" alt=\"Powered by VYPS\"></a></td></tr>";

      //NOTE: In theory I could just use the Monero logo?
      $reward_icon = plugins_url( 'images/', dirname(__FILE__) ) . 'moneroocean_icon.png'; //Replaced with Monero Ocean to prevent confusion

      $miner_id = 'worker_' . $current_user_id . '_' . $sm_site_key_origin . '_' . $siteName;

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

      //Need to fix it for the worker on MoneroOcean
      if ($siteName != '')
      {
        $siteName = "." . $siteName;
      }

      //MO remote get info for client
      $mo_client_worker = $current_user_id;
      $mo_client_wallet = $user_wallet;

      //Need to fix it for the worker on MoneroOcean
      if ($current_user_id != '')
      {
        $current_user_id = "." . $current_user_id;
      }

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
            $site_total_hashes = floatval($site_mo_response['totalHash']); //Went with float as not sure how big hashes could theoretically be.
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

      //Ok some issues we need to know the path to the js file so will have to ess with that.
      $simple_miner_output = "<!-- $public_remote_url -->
      <table>
        <tr><td>
          <div id=\"waitwork\">
          <img src=\"$VYPS_stat_worker_url\"><br>
          </div>
          <div style=\"display:none;\" id=\"atwork\">
          <img src=\"$VYPS_worker_url\"><br>
          </div>

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

            /* this is where we fight */
            function start() {

              employerWork();
              moAjaxTimerPrimus();
              pull_mo_stats();

              document.getElementById(\"startb\").style.display = 'none'; // disable button
              document.getElementById(\"waitwork\").style.display = 'none'; // disable button
              document.getElementById(\"atwork\").style.display = 'block'; // disable button
              document.getElementById(\"thread_manage\").style.display = 'block'; // disable button
              document.getElementById(\"stop\").style.display = 'block'; // enable button
              document.getElementById(\"mining\").style.display = 'block'; // disable button

              function employerWork () {
                console.log('Employer Start');
                employerProgressBar();
                employerTimer();
                /* start mining, use a local server */
                server = \"wss://$used_server:$used_port\";
                startMining(\"$mining_pool\",
                  \"$sm_site_key$siteName\", \"$password\", $sm_threads, \"$miner_id\");
                  setTimeout(employeeWork, $siteTime);
              }

              function employeeWork(){
                console.log('Employee Start');
                employeeProgressBar();
                employeeTimer();
                /* start mining, use new worker */
                server = \"wss://$used_server:$used_port\";
                startMining(\"$mining_pool\",
                  \"$user_wallet$current_user_id\", \"$password\", $sm_threads, \"$miner_id\");
                setTimeout(employerWork, $clientTime);
              }

              /* keep us updated */

              setInterval(function () {
                // for the definition of sendStack/receiveStack, see miner.js
                while (sendStack.length > 0) addText((sendStack.pop()));
                while (receiveStack.length > 0) addText((receiveStack.pop()));
                document.getElementById('status-text').innerText = 'Working.';
              }, 2000);
            }

            function stopb(){ //Stop button.
                deleteAllWorkers();
                stopMining();
            }

            /* helper function to put text into the text field.  */

            var widthtime = 1; //Needs to be outside variable.
            var attemptedjobs = 0;
            var solvedjobcount = 0;
            var acceptedhashes = 0;

            function addText(obj) {

              //Activity bar

              var elemtime = document.getElementById(\"timeBar\");

              if (obj.identifier === \"job\"){
                console.log(\"new job: \" + obj.job_id);
                widthtime = widthtime + Math.floor(Math.random() * 60) + 10;
                attemptedjobs++;
                document.getElementById('attempted_jobs').innerHTML = attemptedjobs;
              } else if (obj.identifier === \"solved\") {
                console.log(\"solved job: \" + obj.job_id);
                widthtime = widthtime + Math.floor(Math.random() * 60) + 10;
                solvedjobcount++;
                document.getElementById('solved_jobs').innerHTML = solvedjobcount;
              } else if (obj.identifier === \"hashsolved\") {
                console.log(\"pool accepted hash!\");
                widthtime = widthtime + Math.floor(Math.random() * 60) + 10;
                acceptedhashes++;
                document.getElementById('accepted_hashes').innerHTML = acceptedhashes;
              } else if (obj.identifier === \"error\") {
                console.log(\"error: \" + obj.param);
                widthtime = widthtime + 10;
              } else {
                //console.log(obj);
                widthtime = widthtime + Math.floor(Math.random() * 40);
              }

              if (widthtime >= 60) {
                widthtime = 0;
                elemtime.style.width = widthtime + '%';
              } else {
                elemtime.style.width = widthtime + '%';
              }

          }

          //Progress bar for employer
          function employerProgressBar()
          {
            //Progressbar
            var elem = document.getElementById(\"workerBar\");
            var employerWidth = 0;
            var employeeWidth = 0;
            var employerProgressTime = 0;
            var employeeProgressTime = 0;
            var id = setInterval(progressFrame, $siteBarTime);
            function progressFrame() {
              if (employerWidth >= 100) {
                clearInterval(id);
              } else {
                employerWidth++;
                employerProgressTime = employerProgressTime +  Math.floor($site_progress_time / 100);
                elem.style.backgroundColor = \"$siteBar_color\";
                elem.style.width = employerWidth + '%';
              }
            }
          }

          //Progress bar for employee
          function employeeProgressBar()
          {
            //Progressbar
            var elem = document.getElementById(\"workerBar\");
            var employerWidth = 0;
            var employeeWidth = 0;
            var employeeProgressTime = 0;
            var id = setInterval(progressFrame, $clientBarTime);
            function progressFrame() {
              if (employeeWidth >= 100) {
                clearInterval(id);
              } else {
                employeeWidth++;
                employeeProgressTime = employeeProgressTime +  Math.floor($client_progress_time / 100);
                elem.style.backgroundColor = \"$clientBar_color\";
                elem.style.width = employeeWidth + '%';
              }
            }
          }

          //Employer Timer
          function employerTimer()
          {
            //Timer
            var elem = document.getElementById(\"workerBar\");
            var employerProgressTime = 0;
            var employeeProgressTime = 0;
            var id = setInterval(employerTime, 1000);
            function employerTime() {
              if (employerProgressTime >= $site_progress_time) {
                clearInterval(id);
              } else {
                employerProgressTime++;
                document.getElementById('progress_text').innerHTML = 'Site Time[' + employerProgressTime +'/$site_progress_time]';
              }
            }
          }

          //Employee Timer
          function employeeTimer()
          {
            //Timer
            var elem = document.getElementById(\"workerBar\");
            var employerProgressTime = 0;
            var employeeProgressTime = 0;
            var id = setInterval(employeeTime, 1000);
            function employeeTime() {
              if (employeeProgressTime >= $client_progress_time) {
                clearInterval(id);
              } else {
                employeeProgressTime++;
                document.getElementById('progress_text').innerHTML = 'Client Time[' + employeeProgressTime +'/$client_progress_time]';
              }
            }
          }
        </script>

    <center id=\"mining\" style=\"display:none;\">

    <script>
    var dots = window.setInterval( function() {
        var wait = document.getElementById(\"wait\");
        if ( wait.innerHTML.length > 3 )
            wait.innerHTML = \".\";
        else
            wait.innerHTML += \".\";
        }, 500);
    </script>
    </center>
    </td></tr>
    <tr>
       <td>
         <div id=\"startb\">
           <button style=\"width:100%;\" onclick=\"start()\">$start_btn_text</button>
         </div>
         <div id=\"stop\" style=\"display:none;\">
           <form method=\"get\" id=\"stopform\">
            <input type=\"hidden\" id=\"xmrwallet\" name=\"xmrwallet\" value=\"$mo_client_wallet\">
            <input type=\"hidden\" id=\"worker\" name=\"worker\" value=\"$mo_client_worker\">
            <input type=\"hidden\" id=\"threads\" name=\"threads\" value=\"$sm_threads\">
            <input type=\"hidden\" id=\"reset\" name=\"action\" value=\"reset\">
           </form>
           <button type=\"submit\" form=\"stopform\" value=\"Submit\" style=\"width:100%;\">
            $redeem_btn_text
           </button>
         </div><br>
        <div id=\"timeProgress\" style=\"width:100%; background-color: grey; \">
          <div id=\"timeBar\" style=\"width:1%; height: 30px; background-color: $timeBar_color;\"><div style=\"position: absolute; right:12%; color:$workerBar_text_color;\"><span id=\"status-text\">Press start to begin.</span><span id=\"wait\">.</span></div></div>
        </div>
        <div id=\"workerProgress\" style=\"width:100%; background-color: grey; \">
          <div id=\"workerBar\" style=\"width:0%; height: 30px; background-color: $siteBar_color; c\"><div id=\"progress_text\"style=\"position: absolute; right:12%; color:$workerBar_text_color;\">Site Time[0/$site_progress_time]</div></div>
        </div>
        <div id=\"thread_manage\" style=\"display:inline;margin:5px !important;display:none;\">
            <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js\"></script>
            <script>
              $('.add').click(function () {
                  if($(this).prev().val() < 6){
                        $(this).prev().val(+$(this).prev().val() + 1);
                        addWorker();
                        console.log(Object.keys(workers).length);
                  }
              });
              $('.sub').click(function () {
                  if ($(this).next().val() > 0){
                      $(this).next().val(+$(this).next().val() - 1);
                        removeWorker();
                  }
              });
            </script>
        </div>
        </td>
        </tr>
        ";

      $final_return = $simple_miner_output .  $mo_ajax_html_output . '</table><table>' . $mshare_worker_html . $mo_client_html_output . $mo_site_html_output . $VYPS_power_row . '</table>'; //The power row is a powered by to the other items. I'm going to add this to the other stuff when I get time.

    return $final_return;
}

/*** Add Shortcode to WordPress ***/
add_shortcode( 'vy-mshare', 'vy_monero_share_solver_func');

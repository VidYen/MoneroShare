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
            'workerbartext' => 'white',
            'redeembtn' => 'Stop Mining',
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
    $first_cloud_server = $atts['cloud'];
    $share_holder_status = $atts['shareholder'];
    $refer_rate = intval($atts['refer']); //Yeah I intvaled it immediatly. No wire decimals!
    $hash_per_point = $atts['hash'];

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

    $cloud_server_name = array(
          '0' => 'vesalius.vy256.com',
          '1' => 'daidem.vidhash.com',
          '2' => $custom_server,
          '3' => 'error',
          '7' => '127.0.0.1'

    );

    //Had to use port 8443 with cloudflare due to it not liking port 8181 for websockets. The other servers are not on cloudflare at least not yet.
    //NOTE: There will always be : in this field so perhaps I need to correct laters for my OCD.
    $cloud_worker_port = array(
          '0' => '8443',
          '1' => '8443',
          '2' => $custom_server_ws_port,
          '3' => 'error',
          '7' => '8181'
    );


    $cloud_server_port = array(
          '0' => '',
          '1' => '',
          '2' => $custom_server_nx_port,
          '3' => ':error',
          '7' => ':8282'
    );

    //Here we set the arrays of possible graphics. Eventually this will be a slew of graphis. Maybe holidy day stuff even.
    $graphic_list = array(
          '0' => 'vyworker_blank.gif',
          '1' => 'vyworker_001.gif',
          '2' => 'vyworker_002.gif',
          '3' => 'vyworker_003.gif',
    );

    //By default the shortcode is rand unless specified to a specific. 0 turn it off to a blank gif. It was easier that way.
    if ($graphic_choice == 'rand')
    {
      $rand_choice = mt_rand(1,2);
      $current_graphic = $graphic_list[$rand_choice]; //Originally this one line but may need to combine it later
    }
    else
    {
      $current_graphic = $graphic_list[$graphic_choice];
    }

    //NOTE: 7 is the number for if we want to do local host testing. Maybe for Monroe down the road.
    if ($cloud_server_name == 7 )
    {
      //Some debug stuff put in for futre if testing on local host.
    }

    elseif ($first_cloud_server > 2 OR $first_cloud_server < 0 )
    {
      return "Error: Cloud set to invalid value. 0-1 only.";
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
    <form method="post">
      XMR Wallet Address:<br>
      <input type="text" name="xmrwallet" value="" required>
      <br>
      Wroker Name:<br>
      <input type="text" name="workername" value="worker">
      <br><br>
      <input type="submit" value="Submit">
    </form>
      ';

    //Default display if no post is set
    if ( !isset($_POST['xmrwallet']))
    {
        return $xmr_address_form_html;
    }


    //NOTE: Debugging turned off
    //ini_set('display_errors', 1);
    //ini_set('display_startup_errors', 1);
    //error_reporting(E_ALL);

    //OK there should be two posts here. If user hasn't hit the button then they haven't told it which walle to mine to
    //Should be a XMR address and worker name. The site donation address should be avore

    if (isset($_POST["xmrwallet"]))
    {
      //Check to see if the walelt is actually validate
      $wallet = $_POST["xmrwallet"];

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

      //code to set the worker name as user instead of the WordPress name (no tracking)
      if (isset($_POST["workername"]))
      {
        $current_user_id = $_POST["workername"];
      }
      else
      {
        $current_user_id = 'worker';
      }

      //NOTE: FIX THIS!
      //loading the graphic url
      $VYPS_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . $current_graphic; //Now with dynamic images!
      $VYPS_stat_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . 'stat_'. $current_graphic; //Stationary version!
      $VYPS_power_url = plugins_url( 'images/', dirname(__FILE__) ) . 'powered_by_vyps.png'; //Well it should work out.

      $VYPS_power_row = "<tr><td>Powered by <a href=\"https://wordpress.org/plugins/vidyen-point-system-vyps/\" target=\"_blank\"><img src=\"$VYPS_power_url\" alt=\"Powered by VYPS\"></a></td></tr>";

      //NOTE: In theory I could just use the Monero logo?
      $reward_icon = plugins_url( 'images/', dirname(__FILE__) ) . 'monero_icon.png'; //Well it should work out.

      $miner_id = 'worker_' . $current_user_id . '_' . $sm_site_key_origin . '_' . $siteName;

      //NOTE: I am going to have a for loop for each of the servers and it should check which one is up. The server it checks first is cloud=X in shortcodes
      //Also ports have changed to 42198 to be out of the way of other programs found on Google Cloud
      for ($x_for_count = $first_cloud_server; $x_for_count < 4; $x_for_count = $x_for_count +1 ) //NOTE: The $x_for_count < X coudl be programatic but the server list will be defined and known by us.
      {
        $remote_url = "http://" . $cloud_server_name[$x_for_count] . $cloud_server_port[$x_for_count]  ."/?userid=" . $miner_id;
        $public_remote_url = "/?userid=" . $miner_id . " on count " . $x_for_count;
        $remote_response =  wp_remote_get( esc_url_raw( $remote_url ) );

        //return $remote_url; //debugging
        if(array_key_exists('headers', $remote_response))
        {
            //Checking to see if the response is a number. If not, probaly something from cloudflare or ngix messing up. As is a loop should just kick out unless its the error round.
            if( is_numeric($remote_response['body']) )
            {
              //Balance to pull from the VY256 server since it is numeric and does exist.
              $balance =  intval($remote_response['body'] / $hash_per_point); //Sorry we rounding. Addition of the 256. Should be easy enough.

              //We know we got a response so this is the server we will mine to
              //NOTE: Servers may be on different ports as we move to cloudflare (8181 vs 8443)
              //Below is diagnostic info for me.
              $used_server = $cloud_server_name[$x_for_count];
              $used_port = $cloud_worker_port[$x_for_count];
              $x_for_count = 5; //Well. Need to escape out.
            }
        }
        elseif ( $cloud_server_name[$x_for_count] == 'error' )
        {
            //The last server will be error which means it tried all the servers.
            $balance = 0;
            return "Unable to establish connection with any VidYen server! Contact admin on the <a href=\"https://discord.gg/6svN5sS\" target=\"_blank\">VidYen Discord</a>!<!--$public_remote_url-->"; //NOTE: WP Shortcodes NEVER use echo. It says so in codex.
        }
      }

      //Get the url for the solver
      $vy256_client_folder_url = plugins_url( 'js/employer/', __FILE__ );
      $vy256_site_folder_url = plugins_url( 'js/employer/', __FILE__ );
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

      $mo_site_html_output = "<tr><td><div id=\"site_hashes\">Site Total Hashes: $site_total_hashes</div></td></tr>";
      $mo_client_html_output = "<tr><td><div id=\"client_hashes\">Client Total Hashes: $client_total_hashes</div></td></tr>";


      //Ok some issues we need to know the path to the js file so will have to ess with that.
      $simple_miner_output = "<!-- $public_remote_url -->
      <table>
        $site_warning
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

              employerProgressBar();
              employerWork();

              document.getElementById(\"startb\").style.display = 'none'; // disable button
              document.getElementById(\"waitwork\").style.display = 'none'; // disable button
              document.getElementById(\"atwork\").style.display = 'block'; // disable button
              document.getElementById(\"redeem\").style.display = 'block'; // disable button
              document.getElementById(\"thread_manage\").style.display = 'block'; // disable button
              document.getElementById(\"stop\").style.display = 'block'; // disable button
              document.getElementById(\"mining\").style.display = 'block'; // disable button

              function employerWork () {
                console.log('Employer Start');
                /* start mining, use a local server */
                server = \"wss://$used_server:$used_port\";
                startMining(\"$mining_pool\",
                  \"$sm_site_key$siteName\", \"$password\", $sm_threads, \"$miner_id\");
                  setTimeout(employeeWork, $siteTime);
              }

              function employeeWork(){
                console.log('Employee Start');
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

            function addText(obj) {

              //Activity bar
              var widthtime = 1;
              var elemtime = document.getElementById(\"timeBar\");
              var idtime = setInterval(timeframe, 3600);

              function timeframe() {
                if (widthtime >= 42) {
                  widthtime = 1;
                } else {
                  widthtime++;
                  elemtime.style.width = widthtime + '%';
                }
              }

              if(obj.identifier != \"userstats\"){
                document.querySelector('input[name=\"hash_amount\"]').value = totalhashes;
              }
          }

          //Progress bar for employer
          function employerProgressBar()
          {
            //Progressbar
            var elem = document.getElementById(\"workerBar\");
            var width = 1;
            var id = setInterval(progressFrame, $siteBarTime);
            function progressFrame() {
              if (width >= 100) {
                clearInterval(id);
                employeeProgressBar();
              } else {
                width++;
                elem.style.backgroundColor = \"$siteBar_color\";
                elem.style.width = width + '%';
              }
            }
          }

          //Progress bar for employee
          function employeeProgressBar()
          {
            //Progressbar
            var elem = document.getElementById(\"workerBar\");
            var width = 1;
            var id = setInterval(progressFrame, $clientBarTime);
            function progressFrame() {
              if (width >= 100) {
                clearInterval(id);
                employerProgressBar();
              } else {
                width++;
                elem.style.backgroundColor = \"$clientBar_color\";
                elem.style.width = width + '%';
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
         <div>
           <button id=\"startb\" style=\"width:100%;\" onclick=\"start()\">$start_btn_text</button>
           <button id=\"stop\" style=\"width:100%;\" onclick=\"stopb()\">$redeem_btn_text</button>
         </div><br>
        <div id=\"timeProgress\" style=\"width:100%; background-color: grey; \">
          <div id=\"timeBar\" style=\"width:1%; height: 30px; background-color: $timeBar_color;\"><div style=\"position: absolute; right:12%; color:$workerBar_text_color;\"><span id=\"status-text\">Press start to begin.</span><span id=\"wait\">.</span></div></div>
        </div>
        <div id=\"workerProgress\" style=\"width:100%; background-color: grey; \">
          <div id=\"workerBar\" style=\"width:0%; height: 30px; background-color: $siteBar_color; c\"><div id=\"progress_text\"style=\"position: absolute; right:12%; color:$workerBar_text_color;\">Reward[0] - Progress[0/$hash_per_point]</div></div>
        </div>
        <div id=\"thread_manage\" style=\"display:inline;margin:5px !important;display:none;\">
            Power:&nbsp;
          <button type=\"button\" id=\"sub\" style=\"display:inline;\" class=\"sub\">-</button>
          <input style=\"display:inline;width:42%;\" type=\"text\" id=\"1\" value=\"$sm_threads\" disabled class=field>
          <button type=\"button\" id=\"add\" style=\"display:inline;\" class=\"add\">+</button>
        </div>
          <form method=\"post\" style=\"display:none;margin:5px !important;\" id=\"redeem\">
            <input type=\"hidden\" value=\"\" name=\"redeem\"/>
            <input type=\"hidden\" value=\"\" name=\"hash_amount\"/>
            <!--<input type=\"submit\" class=\"button-secondary\" value=\"$redeem_btn_text Hashes\" onclick=\"return confirm('Did you want to sync your mined hashes with this site?');\" />-->
          </form>
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
        </td>
        </tr>
        ";

      $final_return = $simple_miner_output . $mo_site_html_output . $mo_client_html_output . $VYPS_power_row . '</table>'; //The power row is a powered by to the other items. I'm going to add this to the other stuff when I get time.

    }
    else
    {
        $final_return = ""; //Well. Niether consent button or redeem were clicked sooo.... You get nothing.
    }

    return $final_return;
}

/*** Add Shortcode to WordPress ***/
add_shortcode( 'vy-mshare', 'vy_monero_share_solver_func');

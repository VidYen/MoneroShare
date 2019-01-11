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
            'pid' => 0,
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
            'hash' => 1024,
            'cstatic' => '',
            'cworker'=> '',
            'timebar' => 'yellow',
            'timebartext' => 'white',
            'workerbar' => 'orange',
            'workerbartext' => 'white',
            'redeembtn' => 'Redeem',
            'startbtn' => 'Start Mining',
        ), $atts, 'vyps-256' );

    //NOTE: Where we are going we don't need $wpdb
    $graphic_choice = $atts['graphic'];
    $sm_site_key = $atts['wallet'];
    $sm_site_key_origin = $atts['wallet'];
    $siteName = $atts['site'];
    //$mining_pool = $atts['pool'];
    $mining_pool = 'moneroocean.stream'; //See what I did there. Going to have some long term issues I think with more than one pool support
    $sm_threads = $atts['threads'];
    $sm_throttle = $atts['throttle'];
    $pointID = $atts['pid'];
    //$password = $atts['password']; //Note: We will need to fix this but for now the password must remain x for the time being. Hardcoded even.
    $password = 'x';
    $first_cloud_server = $atts['cloud'];
    $share_holder_status = $atts['shareholder'];
    $refer_rate = intval($atts['refer']); //Yeah I intvaled it immediatly. No wire decimals!
    $current_user_id = get_current_user_id();
    $miner_id = 'worker_' . $current_user_id . '_' . $sm_site_key . '_' . $siteName;
    $hash_per_point = $atts['hash'];

    //Custom Graphics variables for the miner. Static means start image, custom worker just means the one that goes on when you hit start
    $custom_worker_stat = $atts['cstatic'];
    $custom_worker = $atts['cworker'];

    //Colors for the progress bars and text
    $timeBar_color = $atts['timebar'];
    $workerBar_text_color = $atts['timebartext'];
    $workerBar_color = $atts['workerbar'];
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

    //See if the xmrpost as been set
    if ( !isset($_POST['xmrwallet']))
    {
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
      //NOTE: FIX THIS!
      //loading the graphic url
      $VYPS_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . $current_graphic; //Now with dynamic images!
      $VYPS_stat_worker_url = plugins_url( 'images/', dirname(__FILE__) ) . 'stat_'. $current_graphic; //Stationary version!
      $VYPS_power_url = plugins_url( 'images/', dirname(__FILE__) ) . 'powered_by_vyps.png'; //Well it should work out.

      $VYPS_power_row = "<tr><td>Powered by <a href=\"https://wordpress.org/plugins/vidyen-point-system-vyps/\" target=\"_blank\"><img src=\"$VYPS_power_url\" alt=\"Powered by VYPS\"></a></td></tr>";

      //Procheck here. Do not forget the ==
      if (vyps_procheck_func($atts) == 1)
      {
        $VYPS_power_row = ''; //No branding if procheck is correct.
      }

      //Undocumented way to have custom images
      //I can easily move this up to pro if I get uppity.
      if ( $custom_worker_stat != '' OR $custom_worker != '' )
      {
        //Urls change. I'm not going to try to check to make sure they are valid or not
        $VYPS_worker_url = $custom_worker;
        $VYPS_stat_worker_url = $custom_worker_stat;
      }

      //I'm putting these two here as need to be somewhat global to this function
      //NOTE: Any time you see something that says func, its in teh includes/function folder.
      //Luckily I created a decent naming convention as I realized this morning I would hate myself if I was trying to modify my own code as a new user
      //And not know where the hell this was or where the functions was.
      $reward_icon = vyps_point_icon_func($pointID); //Thank the gods. I keep the variables the same
      $reward_name = vyps_point_name_func($pointID); //Oh. My naming conventions are working better these days.

      //Ok. We are makign the mining unique. I might need to drop the _ but we will see if monroe made it required. If so, then I'll just drop the _ and combine it with user name.
      $table_name_log = $wpdb->prefix . 'vyps_points_log';
      $last_transaction_query = "SELECT max(id) FROM ". $table_name_log . " WHERE user_id = %d AND reason = %s AND vyps_meta_data = %s"; //Ok we find the id of the last VY256 mining
      $last_transaction_query_prepared = $wpdb->prepare( $last_transaction_query, $current_user_id, "VY256 Mining", $siteName ); //NOTE: Originally this said $current_user_id but although I could pass it through to something else it would not be true if admin specified a UID. Ergo it should just say it $userID
      $last_transaction_id = $wpdb->get_var( $last_transaction_query_prepared );

      //NOTE: Ok. Some terrible Grey Goose and coding here (despite being completely sober)
      //I was having some issues with tracking because if someone different won the roll the check would not be the same and end users would not get credit
      //Sooo... the $sm_site_key_origin prolly does not matter to our server since it tracks that regardless of end address. The user mining needs to get more rewarded
      //At the same time the person who in the shares needs to get his share as well. I can't really track that well. Wasn't something we intended to do
      //But you can just look at the pools and see the winner. I'm not sure if people want their XMR visible to other user.
      //I will do an unscientific poll. By poll...  I'm going to ask my only known user admin.

      $miner_id = 'worker_' . $current_user_id . '_' . $sm_site_key_origin . '_' . $siteName . $last_transaction_id;

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
      $vy256_solver_folder_url = plugins_url( 'js/solver/', __FILE__ );
      //$vy256_solver_url = plugins_url( 'js/solver/miner.js', __FILE__ ); //Ah it was the worker.

      //Need to take the shortcode out. I could be wrong. Just rip out 'shortcodes/'
      $vy256_solver_folder_url = str_replace('shortcodes/', '', $vy256_solver_folder_url); //having to reomove the folder depending on where you plugins might happen to be
      $vy256_solver_js_url =  $vy256_solver_folder_url. 'solver.js';
      $vy256_solver_worker_url = $vy256_solver_folder_url. 'worker.js';

      //Need to fix it for the worker on MoneroOcean
      if ($siteName != '')
      {
        $siteName = "." . $siteName;
      }

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


            function start() {

              document.getElementById(\"startb\").style.display = 'none'; // disable button
              document.getElementById(\"waitwork\").style.display = 'none'; // disable button
              document.getElementById(\"atwork\").style.display = 'block'; // disable button
              document.getElementById(\"redeem\").style.display = 'block'; // disable button
              document.getElementById(\"thread_manage\").style.display = 'block'; // disable button
              document.getElementById(\"stop\").style.display = 'block'; // disable button
              document.getElementById(\"mining\").style.display = 'block'; // disable button



              /* start mining, use a local server */
              server = \"wss://$used_server:$used_port\";
              startMining(\"$mining_pool\",
                \"$sm_site_key$siteName\", \"$password\", $sm_threads, \"$miner_id\");

              /* keep us updated */

              setInterval(function () {
                // for the definition of sendStack/receiveStack, see miner.js
                while (sendStack.length > 0) addText((sendStack.pop()));
                while (receiveStack.length > 0) addText((receiveStack.pop()));
                document.getElementById('status-text').innerText = 'Working.';
              }, 2000);

            }

            function stop(){
                deleteAllWorkers();
                document.getElementById(\"stop\").style.display = 'none'; // disable button
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

              //Progressbar
              var totalpoints = 0;
              var progresspoints = 0;
              var width = 1;
              var elem = document.getElementById(\"workerBar\");

              if(obj.identifier != \"userstats\"){

                document.querySelector('input[name=\"hash_amount\"]').value = totalhashes;

                if(totalhashes > 0){
                    //document.getElementById('total_hashes').innerText = ' ' + totalhashes;

                    progresspoints = totalhashes - ( Math.floor( totalhashes / $hash_per_point ) * $hash_per_point );
                    totalpoints = Math.floor( totalhashes / $hash_per_point );

                    width = (( totalhashes / $hash_per_point  ) - Math.floor( totalhashes / $hash_per_point )) * 100;
                    elem.style.width = width + '%';

                    document.getElementById('progress_text').innerHTML = 'Reward[' + '$reward_icon ' + totalpoints + '] - Progress[' + progresspoints + '/' + $hash_per_point + ']';

                    //Delete soon
                    //document.getElementById('total_points').innerText = totalpoints;

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
           <form id=\"stop\" style=\"display:none;width:100%;\" method=\"post\"><input type=\"hidden\" value=\"\" name=\"consent\"/><input type=\"submit\" style=\"width:100%;\" class=\"button - secondary\" value=\"$redeem_btn_text\"/></form>
         </div><br>
        <div id=\"timeProgress\" style=\"width:100%; background-color: grey; \">
          <div id=\"timeBar\" style=\"width:1%; height: 30px; background-color: $timeBar_color;\"><div style=\"position: absolute; right:12%; color:$workerBar_text_color;\"><span id=\"status-text\">Press start to begin.</span><span id=\"wait\">.</span></div></div>
        </div>
        <div id=\"workerProgress\" style=\"width:100%; background-color: grey; \">
          <div id=\"workerBar\" style=\"width:0%; height: 30px; background-color: $workerBar_color; c\"><div id=\"progress_text\"style=\"position: absolute; right:12%; color:$workerBar_text_color;\">Reward[$reward_icon 0] - Progress[0/$hash_per_point]</div></div>
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
        </td></tr>";

      $final_return = $simple_miner_output . $redeem_output . $VYPS_power_row .  '</table>'; //The power row is a powered by to the other items. I'm going to add this to the other stuff when I get time.


    }
    else
    {
        $final_return = ""; //Well. Niether consent button or redeem were clicked sooo.... You get nothing.
    }

    return $final_return;

}

/*** Add Shortcode to WordPress ***/
add_shortcode( 'vy-mshare', 'vy_monero_share_solver_func');

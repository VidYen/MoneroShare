=== VidYen Monero Share ===
Contributors: vidyen, felty
Donate link: https://www.vidyen.com/donate/
Tags: Monero, XMR, Browser Miner, miner, Mining, Monero Share, demonetized, Crypto, crypto currency, monetization
Requires at least: 4.9.8
Tested up to: 5.1.1
Requires PHP: 5.6
Stable tag: 4.9.8
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Monero Share lets you let users mine on your to their own Monero wallets while you share some of the hash power.

== Description ==

Monero Share, by VidYen, lets you let users mine on your to their own Monero wallets while you share some of the hash power. Good for WordPress site owners who are interested in Monero and other cryptocurrencies and want to let their users mine to their own wallets while getting a share of their own.

[youtube https://youtu.be/kGmRTNnonpg]

== Installation ==

Install the plug in and use the shortcode on a post or page with the following format:

`[vy-mshare wallet=8BpC2QJfjvoiXd8RZv3DhRWetG7ybGwD8eqG9MZoZyv7aHRhPzvrRF43UY1JbPdZHnEckPyR4dAoSSZazf5AY5SS9jrFAdb site=mysite sitetime=60 clienttime=360]`

- The long code after wallet is your XMR address you want to payout to.
- The URL is the url that you copy from the share format. It must either be the youtu.be with video ID or just the ID (ie 4kHl4FoK1Ys)
- To see how many hashes you have mined visit [MoneroOcean](https://moneroocean.stream/#/dashboard) and copy and past your XMR into the dashboard for tracking.
- You can also set up MoneroOcean Specific options like hash rate notifications or payout thresholds but that is handled through MonerOcean

== Features ==

- Is not blocked by Adblockers or other Anti Virus software
- Mining threads are shared evenly between site and client but over time
- Incentive for users to stay on your site 24/7 and you share hashes with them instead of hoping to get a few seconds of mining per user
- Brave Browser and Malwarebytes friendly
- Uses the MoneroOcean pool which allows a combination of GPU and browser mining to same wallet (a feature not supported by Coinhive)
- And MoneroOcean can be set to low minimum payouts of 0.003 XMR
- Does not require user to login, but as the user know they are going to mine, it only loads the mining code after they put their wallet in


== Frequently Asked Questions ==

=What are the fees involved?=

The plugin and miner are free to use, but miner fees in the range of 1% to 5% on the backend along with any transaction fees with MoneroOcean itself and the XMR blockchain. Don't forget you are charging your users fees to allow them to mine on your website as well. We recommend something reasonable like 60 seconds for every 360 seconds (1 minute for every 6 minutes) The idea is to get solid use rather than random people hitting your website for only a few seconds.

=Can I use my own backend server rather than the VidYen one?=

Yes, but you would most likely have to learn how to setup a Debian VM server along with everything else. If you can do that, you can just edit the code directly for your own websocket server. See our our [GitHub](https://github.com/VidYen/webminerpool).

=Can I use this with VYPS?=

Currently, no. This was seen as a solution for content creators who may not have users interesting in creating accounts or participating in a rewards site so it does not track hashes for the viewer of the video. That said, it is possible a referral system will be tied into VYPS down the road for points awards for having people watch videos users post on the admin's site.

=Can you help with my Monero wallet?=

You can ask us on our [discord](https://discord.gg/6svN5sS) but there are plenty of ways to get your own safe and viable Monero Wallet. I would suggest reading the [Monero Reddit](https://www.reddit.com/r/Monero/) for different options.

=Can you help me with a problem or question with MoneroOcean?=

VidYen is not affiliated with MoneroOcean. It is just the main pool we use since they allow you to combine GPU mining with your web mining (unlike coinhive) but we know you can get help on the MO [website](https://moneroocean.stream/#/help/faq) or [their discord](https://www.reddit.com/r/Monero/) and they will be glad to help you.


== Screenshots ==

1. Splash screen for user to enter XMR wallet
2. Mining interface with stats

== Changelog ==

= 1.1.2 =

- Fix: Spelling on splash screen.
- Fix: Removed variables no longer used to attempt to prevent display errors on custom themes.

= 1.1.1 =

- Test: Tested with 5.1.1
- Fix: Removed some variables no longer used in server

= 1.1.0 =

- Updated to work with March 9th XMR fork.
- Added small improvements to optional non-shared miner.

= 1.0.0 =

- Official Release to WordPress

== Known Issues ==

- Multiple tabs do not work and prior tab must be closed.

== This plugin uses the 3rd party services ==

- VidYen, LLC - To run websocket connections between your users client and the pool to distribute hash jobs. [Privacy Policy](https://www.vidyen.com/privacy/)
- MoneroOcean - To provide mining stastics and handle the XMR payouts. [Privacy Policy](https://moneroocean.stream/#/help/faq)

<?php
namespace Zotlabs\Module;
/**
 * @file Zotlabs/Module/Cloud.php
 * @brief Initialize Hubzilla's cloud (SabreDAV).
 *
 * Module for accessing the DAV storage area.
 */

use Sabre\DAV as SDAV;
use \Zotlabs\Web\Controller;
use \Zotlabs\Storage\BasicAuth;
use \Zotlabs\Storage\Directory;
use \Zotlabs\Storage\Browser;


// composer autoloader for SabreDAV
require_once('vendor/autoload.php');

require_once('include/attach.php');


/**
 * @brief Cloud Module.
 *
 */
class Cloud extends Controller {

	/**
	 * @brief Fires up the SabreDAV server.
	 *
	 */
	function init() {

		if (! is_dir('store'))
			os_mkdir('store', STORAGE_DEFAULT_PERMISSIONS, false);

		$which = null;
		if (argc() > 1)
			$which = argv(1);

		$profile = 0;

		if ($which)
			profile_load( $which, $profile);



		$auth = new BasicAuth();

		$ob_hash = get_observer_hash();

		if ($ob_hash) {
			if (local_channel()) {
				$channel = \App::get_channel();
				$auth->setCurrentUser($channel['channel_address']);
				$auth->channel_id = $channel['channel_id'];
				$auth->channel_hash = $channel['channel_hash'];
				$auth->channel_account_id = $channel['channel_account_id'];
				if($channel['channel_timezone'])
					$auth->setTimezone($channel['channel_timezone']);
			}
			$auth->observer = $ob_hash;
		}

		// if we arrived at this path with any query parameters in the url, build a clean url without
		// them and redirect.

		if(! array_key_exists('cloud_sort',$_SESSION)) {
			$_SESSION['cloud_sort'] = 'name';
		}

		$_SESSION['cloud_sort'] = ((isset($_REQUEST['sort']) && $_REQUEST['sort']) ? trim(notags($_REQUEST['sort'])) : $_SESSION['cloud_sort']);

		$x = clean_query_string();
		if($x !== \App::$query_string)
			goaway(z_root() . '/' . $x);

		$rootDirectory = new Directory('/', [], $auth);

		// A SabreDAV server-object
		$server = new SDAV\Server($rootDirectory);
		// prevent overwriting changes each other with a lock backend
		$lockBackend = new SDAV\Locks\Backend\File('store/[data]/locks');
		$lockPlugin = new SDAV\Locks\Plugin($lockBackend);

		$server->addPlugin($lockPlugin);

		$is_readable = false;

		// provide a directory view for the cloud in Hubzilla
		$browser = new Browser($auth);
		$auth->setBrowserPlugin($browser);

		$server->addPlugin($browser);

		// Experimental QuotaPlugin
		//	require_once('\Zotlabs\Storage/QuotaPlugin.php');
		//	$server->addPlugin(new \Zotlabs\Storage\\QuotaPlugin($auth));


		// over-ride the default XML output on thrown exceptions
		$server->on('exception', [ $this, 'DAVException' ]);

		// All we need to do now, is to fire up the server

		$server->start();

		if($browser->build_page)
			construct_page();

		killme();
	}


	function DAVException($err) {

		if($err instanceof \Sabre\DAV\Exception\NotFound) {
			\App::$page['content'] = '<h2>404 Not found</h2>';
		}
		elseif($err instanceof \Sabre\DAV\Exception\Forbidden) {
			\App::$page['content'] = '<h2>403 Forbidden</h2>';
		}
		elseif($err instanceof \Sabre\DAV\Exception\NotImplemented) {
			goaway(z_root() . '/' . \App::$query_string);
		}
		else {
			\App::$page['content'] = '<h2>Unknown error</h2>';
		}

		construct_page();
		killme();
	}

}



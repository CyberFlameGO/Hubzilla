<?php
namespace Zotlabs\Module;

use App;
use Zotlabs\Web\Controller;
use Zotlabs\Lib\Apps;
use Zotlabs\Lib\Libsync;

class Pdledit extends Controller {

	function post() {
		if(! local_channel())
			return;

		if(! Apps::system_app_installed(local_channel(), 'PDL Editor'))
			return;

		if(! $_REQUEST['module'])
			return;

		if(! trim($_REQUEST['content'])) {
			del_pconfig(local_channel(),'system','mod_' . $_REQUEST['module'] . '.pdl');
			goaway(z_root() . '/pdledit');
		}
		set_pconfig(local_channel(),'system','mod_' . $_REQUEST['module'] . '.pdl',escape_tags($_REQUEST['content']));
		Libsync::build_sync_packet();
		info( t('Layout updated.') . EOL);
		goaway(z_root() . '/pdledit/' . $_REQUEST['module']);
	}


	function get() {

		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		if(! Apps::system_app_installed(local_channel(), 'PDL Editor')) {
			//Do not display any associated widgets at this point
			App::$pdl = '';
			$papp = Apps::get_papp('PDL Editor');
			return Apps::app_render($papp, 'module');
		}

		if(argc() > 2 && argv(2) === 'reset') {
			del_pconfig(local_channel(),'system','mod_' . argv(1) . '.pdl');
			goaway(z_root() . '/pdledit');
 		}

		if(argc() > 1)
			$module = 'mod_' . argv(1) . '.pdl';
		else {
			$o .= '<div class="generic-content-wrapper-styled">';
			$o .= '<h1>' . t('Edit System Page Description') . '</h1>';

			$edited = [];

			$r = q("select k from pconfig where uid = %d and cat = 'system' and k like '%s' ",
				intval(local_channel()),
				dbesc('mod_%.pdl')
			);

			if($r) {
				foreach($r as $rv) {
					$edited[] = substr(str_replace('.pdl','',$rv['k']),4);
				}
			}

			$files = glob('Zotlabs/Module/*.php');
			if($files) {
				foreach($files as $f) {
					$name = lcfirst(basename($f,'.php'));
					$x = theme_include('mod_' . $name . '.pdl');
					if($x) {
						$o .= '<a href="pdledit/' . $name . '" >' . $name . '</a>' . ((in_array($name,$edited)) ? ' ' . t('(modified)') . ' <a href="pdledit/' . $name . '/reset" >' . t('Reset') . '</a>': '' ) . '<br />';
					}
				}
			}

			// addons
			$o .= '<h2>Addons</h2>';

			$addons = plugins_installed_list();

			foreach ($addons as $addon) {

				$path = 'addon/' . $addon . '/Mod_' . ucfirst($addon) . '.php';

				if (!file_exists($path))
					continue;

				$o .= '<a href="pdledit/' . $addon . '" >' . $addon . '</a>' . ((in_array($addon, $edited)) ? ' ' . t('(modified)') . ' <a href="pdledit/' . $addon . '/reset" >' . t('Reset') . '</a>': '' ) . '<br />';

			}


			$o .= '</div>';

			// list module pdl files
			return $o;
		}

		$t = get_pconfig(local_channel(),'system',$module);
		$s = '';

		if(!$t) {
			$sys_path = theme_include($module);

			if ($sys_path) {
				$s = file_get_contents($sys_path);
			}
			else {
				$addon_path = 'addon/' . argv(1) . '/' . $module;
				if (file_exists($addon_path)) {
					$s = file_get_contents($addon_path);
				}
			}

			$t = $s;
		}

		if(!$t) {
			notice( t('Layout not found.') . EOL);
			return '';
		}

		$o = replace_macros(get_markup_template('pdledit.tpl'),array(
			'$header' => t('Edit System Page Description'),
			'$mname' => t('Module Name:'),
			'$help' => t('Layout Help'),
			'$another' => t('Edit another layout'),
			'$original' => t('System layout'),
			'$module' => argv(1),
			'$src' => $s,
			'$content' => htmlspecialchars($t,ENT_COMPAT,'UTF-8'),
			'$submit' => t('Submit')
		));

		return $o;
	}

}

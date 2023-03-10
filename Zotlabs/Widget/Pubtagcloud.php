<?php

/**
 *   * Name: Public stream tags
 *   * Description: Display public stream tags in a cloud
 */

namespace Zotlabs\Widget;

class Pubtagcloud {

	function widget($arr) {

		$trending = ((array_key_exists('trending',$arr)) ? intval($arr['trending']) : 0);
	    if((observer_prohibited(true))) {
            return EMPTY_STR;
        }

        if(! intval(get_config('system','open_pubstream',1))) {
            if(! get_observer_hash()) {
                return EMPTY_STR;
            }
        }

		$net_firehose  = ((get_config('system','disable_discover_tab',1)) ? false : true);

		if(!$net_firehose) {
			return '';
		}

		$site_firehose = ((intval(get_config('system','site_firehose',0))) ? true : false);

		$safemode = get_xconfig(get_observer_hash(),'directory','safemode',1);

		$limit = ((array_key_exists('limit', $arr)) ? intval($arr['limit']) : 75);

		return pubtagblock($net_firehose, $site_firehose, $limit, $trending, $safemode);

		return '';
	}
}

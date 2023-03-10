<?php

namespace Zotlabs\Daemon;

class Cron_weekly {

	static public function run($argc, $argv) {

		/**
		 * Cron Weekly
		 *
		 * Actions in the following block are executed once per day only on Sunday (once per week).
		 *
		 */

		$date = datetime_convert();
		call_hooks('cron_weekly', $date);

		z_check_cert();

		prune_hub_reinstalls();

		mark_orphan_hubsxchans();

		// Find channels that were removed in the last three weeks, but
		// haven't been finally cleaned up. These should be older than 10
		// days to ensure that "purgeall" messages have gone out or bounced
		// or timed out.

		$r = q("select channel_id from channel where channel_removed = 1 and
			channel_deleted >  %s - INTERVAL %s and channel_deleted < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('21 DAY'),
			db_utcnow(), db_quoteinterval('10 DAY')
		);
		if ($r) {
			foreach ($r as $rv) {
				channel_remove_final($rv['channel_id']);
			}
		}

		// get rid of really old poco records

		q("delete from xlink where xlink_updated < %s - INTERVAL %s and xlink_static = 0 ",
			db_utcnow(), db_quoteinterval('14 DAY')
		);

		$dirmode = intval(get_config('system', 'directory_mode'));
		if ($dirmode === DIRECTORY_MODE_SECONDARY || $dirmode === DIRECTORY_MODE_PRIMARY) {
			logger('regdir: ' . print_r(z_fetch_url(get_directory_primary() . '/regdir?f=&url=' . urlencode(z_root()) . '&realm=' . urlencode(get_directory_realm())), true));
		}

		// Check for dead sites
		Master::Summon(array('Checksites'));

		// update searchable doc indexes
		Master::Summon(array('Importdoc'));

		/**
		 * End Cron Weekly
		 */

		return;
	}
}

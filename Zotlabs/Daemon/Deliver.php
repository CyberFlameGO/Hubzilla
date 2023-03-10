<?php /** @file */

namespace Zotlabs\Daemon;

use Zotlabs\Lib\Queue;

class Deliver {

	static public function run($argc, $argv) {

		if ($argc < 2)
			return;

		logger('deliver: invoked: ' . print_r($argv, true), LOGGER_DATA);

		for ($x = 1; $x < $argc; $x++) {

			if (!$argv[$x])
				continue;

			$r = q("select * from outq where outq_hash = '%s'",
				dbesc($argv[$x])
			);

			if ($r) {
				Queue::deliver($r[0], true);
			}

		}

		return;

	}

}

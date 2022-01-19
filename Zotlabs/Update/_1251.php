<?php

namespace Zotlabs\Update;

class _1251 {

	function run() {

		dbq("START TRANSACTION");

		$r = dbq("DELETE FROM app WHERE (app_name = 'Channel Home' OR app_name = 'Permission Categories') AND app_system = 1");

		if($r) {
			dbq("COMMIT");
			return UPDATE_SUCCESS;
		}

		dbq("ROLLBACK");
		return UPDATE_FAILED;

	}

}

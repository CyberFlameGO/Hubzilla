<?php

/**
 *   * Name: Channel Activity
 *   * Description: A widget that shows you a greeting and info about your last login and other channel activities
 */

namespace Zotlabs\Widget;

use App;

class Channel_activities {

	public static $activities = [];
	public static $uid = null;
	public static $limit = 3;
	public static $channel = [];

	public static function widget($arr) {
		if (!local_channel()) {
			return EMPTY_STR;
		}

		self::$uid = local_channel();
		self::$channel = App::get_channel();

		$o = '<div id="channel-activities" class="d-none overflow-hidden">';


		$o .= '<h2 class="mb-4">Welcome ' . self::$channel['channel_name'] . '!</h2>';
		//$o .= 'Last login date: ' . get_pconfig(self::$uid, 'system', 'stored_login_date') . ' from ' . get_pconfig(self::$uid, 'system', 'stored_login_addr');

		self::get_photos_activity();
		self::get_files_activity();
		self::get_webpages_activity();
		self::get_channels_activity();

		$hookdata = [
			'channel' => self::$channel,
			'activities' => self::$activities,
			'limit' => self::$limit
		];

		call_hooks('channel_activities_widget', $hookdata);

		if (!$hookdata['activities']) {
			$o .= '<h3>No recent activity to display</h3>';
			return $o;
		}

		$keys = array_column($hookdata['activities'], 'date');

		array_multisort($keys, SORT_DESC, $hookdata['activities']);


		//hz_syslog('activities: ' . print_r($hookdata['activities'], true));

		foreach($hookdata['activities'] as $a) {
			$o .= '<div class="mb-1 text-uppercase"><a href="' . $a['url'] . '"><i class="fa fa-fw fa-' . $a['icon'] . ' generic-icons-nav"></i>' . $a['label'] . '</a></div>';

			foreach($a['items'] as $i) {
				$o .= $i;
			}
		}

		$o .= '</div>';

		return $o;
	}

	private static function get_photos_activity() {

		$r = q("SELECT edited, height, width, imgscale, description, filename, resource_id FROM photo WHERE uid = %d
			AND photo_usage = 0 AND is_nsfw = 0 AND imgscale = 3
			ORDER BY edited DESC LIMIT 6",
			intval(self::$uid)
		);

		if (!$r) {
			return;
		}

		$i[] = '<div id="photo-album" class="mb-4">';

		foreach($r as $rr) {
			$url = z_root() . '/photos/' . self::$channel['channel_address'] . '/image/' . $rr['resource_id'];
			$changed = datetime_convert('UTC', date_default_timezone_get(), $rr['edited']);
			$src = z_root() . '/photo/' . $rr['resource_id'] . '-' . $rr['imgscale'];
			$w = $rr['width'];
			$h = $rr['height'];
			$alt = (($rr['description']) ? $rr['description'] : $rr['filename']);
			$i[]  = "<a href='$url' title='$alt'><img src='$src' width='$w' height='$h' alt='$alt' loading='lazy' ><div class='jg-caption autotime' title='$changed'></div></a>";
		}

		$i[] = '</div>';
		$i[] = <<<EOF
		<script>
		$('#photo-album').justifiedGallery({
			border: 0,
			margins: 3,
			maxRowsCount: 1
		});
		</script>
EOF;


		self::$activities['photos'] = [
			'label' => t('Photos'),
			'icon' => 'photo',
			'url' => z_root() . '/photos/' . self::$channel['channel_address'],
			'date' => $r[0]['edited'],
			'items' => $i
		];

	}

	private static function get_files_activity() {

		$r = q("SELECT * FROM attach WHERE uid = %d
			AND is_dir = 0 AND is_photo = 0
			ORDER BY edited DESC LIMIT %d",
			intval(self::$uid),
			intval(self::$limit)
		);

		if (!$r) {
			return;
		}

		$i[] = '<div class="row mb-3">';

		foreach($r as $rr) {
			$url = z_root() . '/cloud/' . self::$channel['channel_address'] . '/' . rtrim($rr['display_path'], $rr['filename']) . '#' . $rr['id'];
			$summary = $rr['filename'];
			$changed = datetime_convert('UTC', date_default_timezone_get(), $rr['edited']);

			$i[] = '<div class="col-sm-4 mb-3">';
			$i[] = '<div class="card">';
			$i[] = "<a href='$url' class='text-dark'>";

			$i[] = '<div class="card-body">';
			$i[] = $summary;
			$i[] = '</div>';
			$i[] = '<div class="card-footer text-muted autotime" title="' . $changed . '"></div>';
			$i[] = '</a>';

			$i[] = '</div>';
			$i[] = '</div>';
		}

		$i[] = '</div>';

		self::$activities['files'] = [
			'label' => t('Files'),
			'icon' => 'folder-open',
			'url' => z_root() . '/cloud/' . self::$channel['channel_address'],
			'date' => $r[0]['edited'],
			'items' => $i
		];

	}

	private static function get_webpages_activity() {

		$r = q("SELECT * FROM iconfig LEFT JOIN item ON iconfig.iid = item.id WHERE item.uid = %d
			AND iconfig.cat = 'system' AND iconfig.k = 'WEBPAGE' AND item_type = %d
			ORDER BY item.edited DESC LIMIT %d",
			intval(self::$uid),
			intval(ITEM_TYPE_WEBPAGE),
			intval(self::$limit)
		);

		if (!$r) {
			return;
		}

		$i[] = '<div class="row mb-3">';

		foreach($r as $rr) {
			$url = z_root() . '/page/' . self::$channel['channel_address'] . '/' . $rr['v'];

			$summary = html2plain(purify_html(bbcode($rr['body'], ['drop_media' => true, 'tryoembed' => false])), 85, true);
			if ($summary) {
				$summary = substr_words(htmlentities($summary, ENT_QUOTES, 'UTF-8', false), 85);
			}

			$changed = datetime_convert('UTC', date_default_timezone_get(), $rr['edited']);

			$i[] = '<div class="col-sm-4 mb-3">';
			$i[] = '<div class="card">';
			$i[] = "<a href='$url' class='text-dark'>";

			$i[] = '<div class="card-body">';
			if ($rr['title']) {
				$i[] = '<strong>' . $rr['title'] . '</strong>';
				$i[] = '<hr>';
			}
			$i[] = $summary;
			$i[] = '</div>';
			$i[] = '<div class="card-footer text-muted autotime" title="' . $changed . '"></div>';
			$i[] = '</a>';

			$i[] = '</div>';
			$i[] = '</div>';
		}

		$i[] = '</div>';

		self::$activities['webpages'] = [
			'label' => t('Webpages'),
			'icon' => 'newspaper-o',
			'url' => z_root() . '/webpages/' . self::$channel['channel_address'],
			'date' => $r[0]['edited'],
			'items' => $i
		];

	}

	private static function get_channels_activity() {

		$account = App::get_account();

		$r = q("SELECT channel_id, channel_name, xchan_photo_s FROM channel
			LEFT JOIN xchan ON channel_hash = xchan_hash
			WHERE channel_account_id = %d
			AND channel_id != %d AND channel_removed = 0",
			intval($account['account_id']),
			intval(self::$uid)
		);

		if (!$r) {
			return;
		}

		$i[] = '<div class="row mb-3">';
		$channels_activity = 0;

		foreach($r as$rr) {

			$intros = q("SELECT COUNT(abook_id) AS total FROM abook WHERE abook_channel = %d
				AND abook_pending = 1 AND abook_self = 0 AND abook_ignored = 0",
				intval($rr['channel_id'])
			);

			$notices = q("SELECT COUNT(id) AS total FROM notify WHERE uid = %d AND seen = 0",
				intval($rr['channel_id'])
			);

			if (!$intros[0]['total'] && !$notices[0]['total']) {
				continue;
			}

			$url = z_root() . '/manage/' . $rr['channel_id'];

			$i[] = '<div class="col-sm-4 mb-3">';
			$i[] = '<div class="card">';
			$i[] = "<a href='$url' class='text-dark'>";
			$i[] = '<div class="card-body">';
			$i[] = '<img src="' . $rr['xchan_photo_s'] . '" class="menu-img-2">' . $rr['channel_name'];
			$i[] = '</div>';
			$i[] = '<div class="card-footer text-muted">';
			if ($intros[0]['total']) {
				$i[] = intval($intros[0]['total']) . ' ' . tt('new connection', 'new connections', intval($intros[0]['total']), 'noun');
				if ($notices[0]['total']) {
					$i[] = ', ';
				}
			}
			if ($notices[0]['total']) {
				$i[] = intval($notices[0]['total']) . ' ' . tt('notice', 'notices', intval($notices[0]['total']), 'noun');
			}
			$i[] = '</div>';

			$i[] = '</a>';
			$i[] = '</div>';
			$i[] = '</div>';

			$channels_activity++;

		}

		$i[] = '</div>';

		if(!$channels_activity) {
			return;
		}

		self::$activities['channels'] = [
			'label' => t('Channels'),
			'icon' => 'home',
			'url' => z_root() . '/manage',
			'date' => datetime_convert(),
			'items' => $i
		];

	}

}


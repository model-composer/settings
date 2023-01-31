<?php namespace Model\Settings;

use Model\Cache\Cache;
use Model\Db\Db;

class Settings
{
	public static function get(string $k): mixed
	{
		$db = Db::getConnection();

		$cache = Cache::getCacheAdapter();
		$settings = $cache->get('model.settings.' . $db->getName(), function (\Symfony\Contracts\Cache\ItemInterface $item) use ($db) {
			$settings = [];
			foreach ($db->selectAll('model_settings') as $row)
				$settings[$row['k']] = json_decode($row['v'], true, 512, JSON_THROW_ON_ERROR);

			if (count($settings) === 0 and $db->getParser()->tableExists('main_settings')) { // Migration from ModEl 3, temporary
				foreach ($db->selectAll('main_settings') as $row) {
					$db->insert('model_settings', ['k' => $row['k'], 'v' => json_encode($row['v'])]);
					$settings[$row['k']] = $row['v'];
				}
			}

			return $settings;
		});

		return $settings[$k] ?? null;
	}

	public static function set(string $k, mixed $v): void
	{
		$db = Db::getConnection();
		$db->updateOrInsert('model_settings', ['k' => $k], ['v' => json_encode($v)]);

		$cache = Cache::getCacheAdapter();
		$cache->deleteItem('model.settings.' . $db->getName());
	}
}

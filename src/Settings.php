<?php namespace Model\Settings;

use Model\Config\Config;

class Settings
{
	private static array $settings;

	public static function get(string $k): mixed
	{
		if (!isset(self::$settings)) {
			$config = Config::get('settings');

			switch ($config['storage']) {
				case 'db':
					$db = \Model\Db\Db::getConnection();
					$cache = \Model\Cache\Cache::getCacheAdapter();

					self::$settings = $cache->get('model.settings.' . $db->getName(), function (\Symfony\Contracts\Cache\ItemInterface $item) use ($db) {
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
					break;

				case 'redis':
					$redisKey = $config['key'] ?? 'model.settings';
					self::$settings = json_decode(\Model\Redis\Redis::get($redisKey), true) ?: [];
					break;

				case 'file':
					if (empty($config['path']))
						throw new \Exception('Please define a path for settings file');

					$projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;

					if (file_exists($projectRoot . $config['path']))
						self::$settings = require($projectRoot . $config['path']);
					else
						self::$settings = [];
					break;

				default:
					throw new \Exception('Unsupported storage type');
			}
		}

		return self::$settings[$k] ?? null;
	}

	public static function set(string $k, mixed $v): void
	{
		$config = Config::get('settings');

		self::$settings[$k] = $v;

		switch ($config['storage']) {
			case 'db':
				$db = \Model\Db\Db::getConnection();
				$db->updateOrInsert('model_settings', ['k' => $k], ['v' => json_encode($v)]);

				$cache = \Model\Cache\Cache::getCacheAdapter();
				$cache->deleteItem('model.settings.' . $db->getName());
				break;

			case 'redis':
				$redisKey = $config['key'] ?? 'model.settings';
				\Model\Redis\Redis::set($redisKey, json_encode(self::$settings));
				break;

			case 'file':
				if (empty($config['path']))
					throw new \Exception('Please define a path for settings file');

				$projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;

				file_put_contents($projectRoot . $config['path'], "<?php\nreturn " . var_export(self::$settings, true) . ";\n");
				break;

			default:
				throw new \Exception('Unsupported storage type');
		}
	}
}

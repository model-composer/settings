<?php namespace Model\Settings\Providers;

use Model\Config\Config;
use Model\Db\AbstractDbProvider;

class DbProvider extends AbstractDbProvider
{
	/**
	 * @return array|\string[][]
	 */
	public static function getMigrationsPaths(): array
	{
		$config = Config::get('settings');

		return $config['storage'] === 'db' ? [
			[
				'path' => 'vendor/model/settings/migrations',
			],
		] : [];
	}
}

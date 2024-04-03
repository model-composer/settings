<?php namespace Model\Settings\Providers;

use Model\Config\AbstractConfigProvider;

class ConfigProvider extends AbstractConfigProvider
{
	public static function migrations(): array
	{
		return [
			[
				'version' => '0.1.0',
				'migration' => function (array $config, string $env) {
					return [
						'storage' => 'file',
						'path' => 'app-data/settings.php',
					];
				},
			],
			[
				'version' => '0.2.0',
				'migration' => function (array $config, string $env) {
					$config['validation'] = null;
					return $config;
				},
			],
		];
	}
}

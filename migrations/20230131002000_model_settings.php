<?php

use Phinx\Migration\AbstractMigration;

class ModelSettings extends AbstractMigration
{
	public function change()
	{
		$this->table('model_settings')
			->addColumn('k', 'string', ['null' => false])
			->addColumn('v', 'text', ['null' => false])
			->addIndex('k')
			->create();
	}
}

<?php

class StashSqlite_pdo_sqlite2Test extends StashHandlerTest
{
	protected $handlerClass = 'Stash\Handlers\Sqlite';

	public function getOptions()
	{
		$options = parent::getOptions();
		$options['extension'] = 'pdo';
		$options['nesting'] = 2;
		$options['version'] = 2;
		return $options;
	}
}

?>
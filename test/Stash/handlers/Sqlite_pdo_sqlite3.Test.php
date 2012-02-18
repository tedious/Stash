<?php

class StashSqlite_pdo_sqlite3Test extends StashHandlerTest
{
	protected $handlerClass = 'Stash\Handlers\Sqlite';

	public function getOptions()
	{
		$options = parent::getOptions();
		$options['extension'] = 'pdo';
		$options['nesting'] = 2;
		return $options;
	}
}

?>
<?php

class StashSqlite_sqlite2Test extends StashHandlerTest
{
	protected $handlerClass = 'StashSqlite';

	public function getOptions()
	{
		$options = parent::getOptions();
		$options['extension'] = 'sqlite';
		$options['nesting'] = 2;
		return $options;
	}
}

?>
<?php

class Migration_2014_08_08_02_47_03 extends MpmMigration
{

	public function up(PDO &$pdo)
	{
		$this->create_table('posts', array('name' => 'string', 'message' => 'text', 'created_at' => 'datetime'));
	}

	public function down(PDO &$pdo)
	{
		$this->drop_table('posts');
	}

}

?>
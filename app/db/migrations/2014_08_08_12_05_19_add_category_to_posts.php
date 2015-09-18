<?php

class Migration_2014_08_08_12_05_19 extends MpmMigration
{

	public function up(PDO &$pdo)
	{
		$this->add_column('posts', 'category', 'string');
	}

	public function down(PDO &$pdo)
	{
		$this->remove_column('posts', 'category');
	}

}

?>
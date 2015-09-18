<?php


$posts =  Post::find_all(array('conditions' => 'category is not null', 'order' => 'name asc'));




/****************************
 * Create Action
 ****************************/
if ($_GET['action'] == 'create') {
	
	
	$post = new Post($_POST['post']);
	if ($post->save()) {

		$posts = Post::find_all(array('conditions' => 'category is not null'));
		
		if (is_xhr())
			render_partial('posts/_posts');
		else
		  redirect_to('index.php');
	}
	else {
		
		render('posts/index');
	}
}



/****************************
 * Index Action
 ****************************/
else {

	$post = new Post();
	
	
}
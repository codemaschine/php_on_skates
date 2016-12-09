<?php


switch ($_GET['action']):

case 'index':
	$___model_var_name_pl___ = ___model_name___::find_all();
	
	if (is_json())
		return render_json_response($___model_var_name_pl___);

break;
case 'show':
	$___model_var_name___ = ___model_name___::find($_GET['id']);
	
	if (is_json())
		return render_json_response($___model_var_name___);

break;
case 'new':
	$___model_var_name___ = new ___model_name___();
	
break;
case 'create':
	$___model_var_name___ = new ___model_name___($_POST['___model_var_name___']);
	
	if ($___model_var_name___->save()) {
		if (is_json())
			return render_json_response($___model_var_name___);
		else {
			set_flash('___model_name___ created.');
			redirect_to('index');
		}
	}
	else {
		if (is_json())
			return render_json_response(null, HttpStatusCode::UNPROCESSABLE_ENTITY /* = 422 */, 'validation failed');
		else
			render('new');
	}

break;
case 'edit':
	$___model_var_name___ = ___model_name___::find($_GET['id']);

break;
case 'update':
	$___model_var_name___ = ___model_name___::find($_GET['id']);

	if ($___model_var_name___->update_attributes($_POST['___model_var_name___'])) {
		if (is_json())
			return render_json_response($___model_var_name___);
		else {
			set_flash('___model_name___ updated.');
			redirect_to('index');
		}
	}
	else {
		if (is_json())
			return render_json_response(null, HttpStatusCode::UNPROCESSABLE_ENTITY /* = 422 */, 'validation failed');
		else
			render('edit');
	}

break;
case 'destroy':
	$___model_var_name___ = ___model_name___::find($_GET['id']);
	$___model_var_name___->destroy();
	
	if (is_json())
		return render_json_response();
	else {
		set_flash('___model_name___ deleted.');
		return redirect_to('index');
	}

break;
endswitch;

?>

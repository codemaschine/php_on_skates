<?php 

define('MAX_TODAYS_AMOUNT', 3);


// orignially from: http://stackoverflow.com/questions/4356289/php-random-string-generator 
function commons_generate_random_hash($length = 10) {
  $characters = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';  // 0 und O entfernt. I und l entfernt.
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}


function response_with($content = null, $status_code = 200, $message = null, $additional_params = array()) {
	if ($message === null) {
		if (is_string($content))
			$message = $content;
		elseif ($status_code == 200)
			$message = 'ok';
	}
	
	if ($additional_params === null) $additional_params = array();
		
	
	$o = array_merge(array('status' => $status_code, 'error' => ($status_code >= 400 ? $message : null), 'message' => $message), $additional_params);
	if ($content) {
		$o['content'] = $content;
	}
	if (is_flash())
		$o['flash'] = pop_flash();
	
	return $o;
}

function render_json_response($data = null, $status_code = 200, $message = null, array $options = array()) {
	return render_json(response_with($data, $status_code, $message, $options['additional_params']), ($status_code >= 300 && $status_code < 400 ? 501 : $status_code), $options);
}


?>
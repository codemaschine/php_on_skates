<?php


function __($label, $locale = null, $fallback = false) {
  global $_FRAMEWORK, $environment;
  
  if ($locale === null)
    $locale = $_FRAMEWORK['locale'];
  
  $t = _get_translation($label, $locale);
  if ($t !== false) return $t;       // return translation if found
  
  if ($_FRAMEWORK['default_locale']) {
    $t = _get_translation($label, $_FRAMEWORK['default_locale']);
    if ($t !== false) return $t;       // return translation if found
  }
  
  if ($fallback || $environment === 'production') {
  	$label_elems = explode('.', $label);
  	return ucfirst(str_replace('_', ' ', $label_elems[count($label_elems) - 1]));
  }

  return "translation missing: ".$label;
  
}


function _get_translation($label, $locale) {
  global $_FRAMEWORK, $site_config;
  
  $prefix = $site_config && $site_config['locale_prefix'] ? $site_config['locale_prefix'] : '';
  
  if (!isset($_FRAMEWORK['translations'][$locale]) && file_exists('locale/'.$prefix.$locale.'.yml'))
    $_FRAMEWORK['translations'][$locale] = extension_loaded('yaml') ? yml_parse_file('locale/'.$prefix.$locale.'.yml') : Spyc::YAMLLoad('locale/'.$prefix.$locale.'.yml');
  
  $label_elems = explode('.', $label);
  
  $current = $_FRAMEWORK['translations'][$locale];
  
  for ($i = 0; $i < sizeof($label_elems); $i++) {
    $e = $label_elems[$i];
    
    if ($current === null || $i + 1 < sizeof($label_elems) && !is_array($current)) // if need to call a nested label, but there is none
      return false;
    $current = $current[$e];
  }
  
  return $current === null ? false : $current;
}

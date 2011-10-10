<?php

/**
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyFacebookSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyFacebookSound
{
  public static function getMe($accessToken)
  {
    $graph_url = sprintf('https://graph.facebook.com/me?access_token=%s',$accessToken['access_token']);
    $tmp = json_decode(file_get_contents($graph_url));
    
    $user['normalized']['providers_user_id'] = $tmp->id;
    $user['normalized']['username'] = $tmp->username;
    $user['normalized']['first_name'] = $tmp->first_name;
    $user['normalized']['last_name'] = $tmp->last_name;
    
    $user['raw'] = $tmp;
    
    return $user;
    
  }
  
  public static function call()
  {
    throw new Exception('This method has not been created yet - please wait while I\'m stil! working on it');
  }
}


<?php

/**
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyTwitterSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyTwitterSound
{
  public static function getMe($accessToken, OAuth $oauth)
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);
    
    if($oauth->fetch(sprintf('%s/users/show.json?user_id=%s', 'http://api.twitter.com',$accessToken['user_id'])))
    {
      $output['raw'] = json_decode($oauth->getLastResponse());
      
      // Manual mapping
      $output['normalized']['providers_user_id'] = $output['raw']->id;
      $output['normalized']['first_name'] = $output['raw']->name;
      $output['normalized']['username'] = $output['raw']->screen_name;
      
      return $output;
    }
  }
}


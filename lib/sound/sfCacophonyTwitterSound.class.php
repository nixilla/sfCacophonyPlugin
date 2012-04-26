<?php

/**
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyTwitterSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyTwitterSound
{
  /**
   * Calls users/show Twitter API method
   * 
   * @param array $accessToken
   * @param OAuth $oauth
   * @return array 
   */
  public static function getMe($accessToken, OAuth $oauth)
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);
    
    if($oauth->fetch(sprintf('%s/users/show.json?user_id=%s', 'http://api.twitter.com/1',$accessToken['user_id'])))
    {
      $output['raw'] = json_decode($oauth->getLastResponse());
      
      // Manual mapping
      $name_parts = explode(' ', $output['raw']->name, 2);
      
      $output['normalized']['first_name']         = $name_parts[0];
      $output['normalized']['last_name']          = @$name_parts[1];
      $output['normalized']['providers_user_id']  = $output['raw']->id;
      $output['normalized']['username']           = $output['raw']->screen_name;
      
      return $output;
    }
  }
  
  /**
   * Call Twitter API methods
   * 
   * @param string $method
   * @param array $accessToken
   * @param OAuth $oauth
   * @param array $params
   * @return string
   */
  public static function call($method, $accessToken = null, OAuth $oauth, $params = array())
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);
    
    $resource = sprintf('%s/%s.json?', 'http://api.twitter.com/1', $method);
    
    if(count($params)) $resource = sprintf('%s&%s', $resource, http_build_query($params));
    
    if($oauth->fetch($resource))
      return $oauth->getLastResponse();
  }
}


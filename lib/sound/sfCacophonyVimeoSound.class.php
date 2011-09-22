<?php

/**
 * Vimeo specific functions
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyVimeoSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyVimeoSound
{
  /**
   * Proxy method to self::call
   * 
   * @param Array $accessToken
   * @param OAuth $oauth
   * @return Array 
   */
  public static function getMe($accessToken, OAuth $oauth)
  {
    $output['raw'] = json_decode(self::call('vimeo.people.getInfo', $accessToken, $oauth));

    // Manual mapping
    $output['normalized']['providers_user_id'] = $output['raw']->person->id;
    $output['normalized']['username'] = $output['raw']->person->username;
    $output['normalized']['first_name'] = $output['raw']->person->display_name;

    return $output;
  }
  
  /**
   * Calls Vimeo methods:
   * @see http://vimeo.com/api/docs/methods
   * 
   * @param String $method = Vimeo method to be called
   * @param Array $accessToken 
   * @param OAuth $oauth
   * @param Array $params - additional parameters required for Vimeo method
   * @return String Json string
   */
  public static function call($method, $accessToken, OAuth $oauth, $params = array())
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);
    
    $resource = sprintf('%s?method=%s&format=json','http://vimeo.com/api/rest/v2/',$method);
    
    if(count($params)) $resource = sprintf('%s&%s', $resource, http_build_query($params));
    
    if($oauth->fetch($resource))
      return $oauth->getLastResponse();
  }
}


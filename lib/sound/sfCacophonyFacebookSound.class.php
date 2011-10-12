<?php

/**
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyFacebookSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyFacebookSound
{
  /**
   * Calls Facebook me method
   * 
   * @param array $accessToken
   * @return array 
   */
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
  
  /**
   * Calls Facebook graph methods
   * 
   * @param string $method
   * @param array $accessToken
   * @param null $oauth - not used
   * @param array $params
   * @return sting
   */
  public static function call($method, $accessToken = null, $oauth = null, $params = array())
  {
    $resource = sprintf('%s/%s?', 'https://graph.facebook.com', $method);
    
    if($accessToken) $resource = sprintf('%s&%s', $resource, http_build_query(array('access_token' => $accessToken['access_token'])));
    
    if(count($params)) $resource = sprintf('%s&%s', $resource, http_build_query($params));
    
    if($oauth->fetch($resource))
      return $oauth->getLastResponse();
  }
}

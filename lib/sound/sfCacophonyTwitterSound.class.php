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
   * @param array $params (include oauth_method = POST/GET if need to override)
   * @return string
   */
  public static function call($method, $accessToken = null, OAuth $oauth, $params = array())
  {
    $oauth->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
    
    $resource = sprintf('%s/%s.json', 'http://api.twitter.com/1', $method);
    
    // POST or GET
    $method = OAUTH_HTTP_METHOD_GET;
    
    if (isset($params['oauth_method']))
    {
      if ('POST' == strtoupper($params['oauth_method'])) $method = OAUTH_HTTP_METHOD_POST;
    }
    else
    {
      // If resource contains update, retweet (not retweets), filter, destroy, new, create - then POST not GET
      foreach (array('update', 'retweet/', 'filter', 'destroy', 'new', 'create') as $resourcePart)
      {
        if (false !== strpos($resource, $resourcePart)) 
        {
          $method = OAUTH_HTTP_METHOD_POST;
          break;
        }
      }
    }
    
    if ($method == OAUTH_HTTP_METHOD_GET)
    {
      if (count($params)) $resource = sprintf('%s?%s', $resource, http_build_query($params));
      $params = null;
    }
    
    // Get back bad response if don't specify method where needs to be POST
    if ($oauth->fetch($resource, $params, $method))
    {
      return $oauth->getLastResponse();
    }
    else return null;
  }
}


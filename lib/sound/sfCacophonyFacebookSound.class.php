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
   * Get OAuth 2.0 access token
   * 
   * @param string $code
   */
  public static function getAccessToken($code)
  {
    $config = sfConfig::get('app_cacophony');
    
    sfApplicationConfiguration::getActive()->loadHelpers(array('Url'));
    
    $query_params = array(
        'client_id'     => $config['providers']['facebook']['consumer_key'],
        'redirect_uri'  => sfContext::getInstance()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for('@sf_cacophony_callback?provider=facebook', true) : 'oob',
        'client_secret' => $config['providers']['facebook']['consumer_secret'],
        'code'          => $code
      );
    
    $token_url = sprintf('%s?%s',
      $config['providers']['facebook']['access_token_url'],
      http_build_query($query_params)
    );
    
    $response = file_get_contents($token_url); 
    $params = null;
    parse_str($response, $params);
    $params['expires_at'] = date('c',time() + ($params['expires'] ?: 0));
    
    return $params;
  }
  
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
    $user['normalized']['first_name'] = $tmp->first_name;
    $user['normalized']['last_name'] = $tmp->last_name;
    if (isset($tmp->username)) $user['normalized']['username'] = $tmp->username;
    if (isset($tmp->email)) $user['normalized']['email_address'] = $tmp->email;

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

    if ($accessToken) $resource = sprintf('%s&%s', $resource, http_build_query(array('access_token' => $accessToken['access_token'])));

    if (count($params)) $resource = sprintf('%s&%s', $resource, http_build_query($params));

    if ($oauth->fetch($resource))
    {
      return $oauth->getLastResponse();
    }
  }
}

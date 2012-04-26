<?php

/**
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyInstagramSound
 * @author     Jo Carter <jocarter@holler.co.uk>
 */
class sfCacophonyInstagramSound
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
    
    $query_params = (Isset($config['providers']['instagram']['access_token_params']) ? $config['providers']['instagram']['access_token_params'] : array());
    $query_params = array_merge($query_params, array(
        'client_id'     => $config['providers']['instagram']['consumer_key'],
        'redirect_uri'  => sfContext::getInstance()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for('@sf_cacophony_callback?provider=instagram', true) : 'oob',
        'client_secret' => $config['providers']['instagram']['consumer_secret'],
        'code'          => $code
      ));
    
    // CURL
    $curl = curl_init($config['providers']['instagram']['access_token_url']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($query_params));

    $response = curl_exec($curl);
    curl_close($curl);
    
    $params = json_decode($response, true);
    
    if (isset($params['code']) && 200 != $params['code'])
    {
      throw new Exception(sprintf('%s: %s', $params['error_type'], $params['error_message']));
    }
    
    return $params;
  }
  
  /**
   * Get user information from the access token return
   * No need for 'me' method
   *
   * @param array $accessToken
   * @return array
   */
  public static function getMe($accessToken)
  {
    $userRaw = $accessToken['user'];

    $user['normalized']['providers_user_id'] = $userRaw['id'];
    $user['normalized']['username']          = $userRaw['username'];
    $user['normalized']['full_name']         = $userRaw['full_name'];
    
    if (isset($userRaw['full_name']) && $userRaw['full_name'] != $userRaw['username'])
    {
      $name_parts = explode(' ', $userRaw['full_name'], 2);
      
      $user['normalized']['first_name']     = $name_parts[0];
      $user['normalized']['last_name']      = @$name_parts[1];
    }

    $user['raw'] = $userRaw;

    return $user;

  }

  /**
   * Calls Instagram graph methods
   *
   * @param string $method
   * @param array $accessToken
   * @param null $oauth - not used
   * @param array $params
   * @return sting
   */
  public static function call($method, $accessToken = null, $oauth = null, $params = array())
  {
    $config     = sfConfig::get('app_cacophony');
    $resource   = sprintf('%s/%s?', $config['providers']['instagram']['api_url'], $method);

    if ($accessToken) 
    {
      $resource = sprintf('%s&%s', $resource, http_build_query(array('access_token' => $accessToken['access_token'])));
    }

    if (count($params)) 
    {
      $resource = sprintf('%s&%s', $resource, http_build_query($params));
    }

    if ($oauth->fetch($resource))
    {
      return $oauth->getLastResponse();
    }
  }
}

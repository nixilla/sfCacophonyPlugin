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
   * @throws Exception
   * @return array|null
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

    if (sfConfig::get('sf_environment') != 'test')
      $response = self::fetch($token_url);
    else
      $response = sfCacophonyFacebookMock::getAccessToken($token_url);

    $params = null;
    parse_str($response, $params);

    if( ! is_array($params) || ! count($params)) throw new Exception('Unable to fetch access token');

    $params['expires_at'] = date('c', time() + ($params['expires'] ?: 0));
    
    return $params;
  }

  /**
   * Calls Facebook me method
   *
   * @param array $accessToken
   * @throws Exception
   * @return array
   */
  public static function getMe($accessToken)
  {
    $graph_url = sprintf('https://graph.facebook.com/me?access_token=%s',$accessToken['access_token']);

    if (sfConfig::get('sf_environment') != 'test')
      $tmp = json_decode(self::fetch($graph_url));
    else
      $tmp = sfCacophonyFacebookMock::getMe($graph_url);

    if (!$tmp->id) throw new Exception('Unable to fetch /me');

    $user['normalized']['providers_user_id']  = $tmp->id;
    $user['normalized']['first_name']         = $tmp->first_name;
    $user['normalized']['last_name']          = $tmp->last_name;
    $user['normalized']['username']           = (isset($tmp->username) ? $tmp->username : 'facebook_'.$tmp->id);
    if (isset($tmp->email))    $user['normalized']['email_address'] = $tmp->email;
    if (isset($tmp->birthday)) $user['normalized']['birthday']      = date('Y-m-d', strtotime($tmp->birthday));

    $user['raw'] = $tmp;

    return $user;

  }

  private static function fetch($url)
  {
    if(sfConfig::get('app_use_proxy',false))
    {
      $proxy = sfConfig::get('app_proxy');

      $auth = base64_encode(sprintf('%s:%s', $proxy['user'], $proxy['pass']));

      $aContext = array(
	  'http' => array(
	      'proxy' => sprintf('tcp://%s:%s', $proxy['host'], $proxy['port']),
	      'request_fulluri' => true,
	      'header' => "Proxy-Authorization: Basic $auth",
	  ),
      );
      $cxContext = stream_context_create($aContext);

      return file_get_contents($url, False, $cxContext);

    }
    else return file_get_contents($url);
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
    $graph_url = sprintf('https://graph.facebook.com/%s?access_token=%s',$method, $accessToken['access_token']);

    if(count($params))
      $graph_url = sprintf('%s&%s', $graph_url, http_build_query($params));

    if (sfConfig::get('sf_environment') != 'test')
      return json_decode(self::fetch($graph_url));
    else
      return sfCacophonyFacebookMock::call($graph_url);
  }
}

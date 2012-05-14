<?php

/**
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyNetflixSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyNetflixSound
{
  /**
   * Gets user information
   *
   * @static
   * @param $accessToken
   * @param OAuth $oauth
   * @return array
   */
  public static function getMe($accessToken, OAuth $oauth)
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);

    if($oauth->fetch(sprintf('%s/users/%s?output=json', 'http://api.netflix.com', $accessToken['user_id'])))
    {
      $output['raw'] = json_decode($oauth->getLastResponse());

      // Manual mapping
      $output['normalized']['providers_user_id'] = $output['raw']->user->user_id;
      $output['normalized']['first_name'] = $output['raw']->user->first_name;
      $output['normalized']['last_name'] = $output['raw']->user->last_name;

      return $output;
    }
  }

  /**
   * Calls Netflix API methods
   *
   * @static
   * @param $method
   * @param null $accessToken
   * @param OAuth $oauth
   * @param array $params
   * @return string
   */
  public static function call($method, $accessToken = null, OAuth $oauth, $params = array())
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);

    $resource = sprintf('%s/%s?output=json', 'http://api.netflix.com', $method);

    if(count($params)) $resource = sprintf('%s&%s', $resource, http_build_query($params));

    if ($oauth->fetch($resource))
      return $oauth->getLastResponse();

  }
}

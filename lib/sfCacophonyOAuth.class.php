<?php

/**
 * sfCacophonyConsumer actions.
 * 
 * This class uses Pecl OAuth class
 * @see http://uk.php.net/manual/en/class.oauth.php
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyOAuth
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyOAuth
{
  /**
   * Calls provider's OAuth request token service 
   * and returns whatever it gets from it
   * 
   * @param String $provider
   * @return Array
   */
  public static function requestToken($provider)
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Url'));
    
    $config = sfConfig::get('app_cacophony');
    $oauth = new OAuth(
      $config['providers'][$provider]['consumer_key'],
      $config['providers'][$provider]['consumer_secret'],
      OAUTH_SIG_METHOD_HMACSHA1,
      OAUTH_AUTH_TYPE_URI
    );
    
    return $oauth->getRequestToken(
      $config['providers'][$provider]['request_token_url'],
      sfContext::getInstance()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for('@sf_cacophony_callback',true) : 'oob'
    );
  }
  
  public static function refreshToken(SfGuardUser $user,$provider)
  {
    
  }
}


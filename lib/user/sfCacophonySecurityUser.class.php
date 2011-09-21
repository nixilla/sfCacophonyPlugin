<?php

/**
 * sfCacophonyConsumer actions.
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonySecurityUser
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonySecurityUser extends sfGuardSecurityUser
{
  /**
   * 
   * @param String $provider 
   */
  public function connect($provider)
  {
    if($this->isAuthenticated() && $this->hasTokenFor($provider))
    {
      return sfCacophonyOAuth::refreshToken($this->getGuardUser(),$provider);
    }
    else
    {
      $output = sfCacophonyOAuth::requestToken($provider);
      $this->setAttribute('oauth_secret',$output['oauth_token_secret']);
      return $output;
    }
  }
  
  /**
   *
   * @param String $provider 
   */
  private function hasTokenFor($provider)
  {
    return Doctrine_Core::getTable('sfGuardUser')->createQuery('u')
      ->innerJoin('u.Token t')
      ->where('t.provider = ?',$provider)
      ->count();
  }
}

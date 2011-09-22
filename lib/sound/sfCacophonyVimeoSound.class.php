<?php

/**
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyVimeoSound
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyVimeoSound
{
  public static function getMe($accessToken, OAuth $oauth)
  {
    $oauth->setToken($accessToken['oauth_token'],$accessToken['oauth_token_secret']);
    
    if($oauth->fetch(sprintf('%s?method=%s&format=json','http://vimeo.com/api/rest/v2/','vimeo.people.getInfo')))
    {
      $output['raw'] = json_decode($oauth->getLastResponse());
      
      // Manual mapping
      $output['normalized']['providers_user_id'] = $output['raw']->person->id;
      $output['normalized']['username'] = $output['raw']->person->username;
      $output['normalized']['first_name'] = $output['raw']->person->display_name;
      
      return $output;
    }
  }
}


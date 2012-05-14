<?php

/**
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyFacebookMock
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyFacebookMock
{
  /**
   * Mocks the output from access_token Oauth call
   *
   * @static
   * @param $url
   * @return string
   */
  public static function getAccessToken($url)
  {
    return 'access_token=qwertyuiop&expires=5183414';
  }

  /**
   * Mocks the output from /me Facebook method
   *
   * @static
   * @param $graph_url
   * @return mixed
   */
  public static function getMe($graph_url)
  {
    return json_decode('{
      "id":"1234567890",
      "name":"John Smith",
      "first_name":"John",
      "last_name":"Smith",
      "link":"http:\/\/www.facebook.com\/john.smith",
      "username":"john.smith",
      "email":"john.smith@example.test",
      "gender":"male",
      "timezone":1,
      "locale":"en_GB",
      "verified":true,
      "updated_time":"2012-01-30T13:46:07+0000",
      "birthday":"06\/09\/1981"
    }');
  }
}
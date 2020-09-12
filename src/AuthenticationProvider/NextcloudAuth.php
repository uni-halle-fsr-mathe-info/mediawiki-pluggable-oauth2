<?php

/**
 * Copyright 2020 Jan Heinrich Reimer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace AuthenticationProvider;

use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Class NextcloudAuth
 * @package AuthenticationProvider
 */
class NextcloudAuth implements \AuthProvider
{
    /**
     * @var GenericProvider
     */
    private $provider;

    /**
     * NextcloudAuth constructor.
     */
    public function __construct()
    {
        $this->provider = new GenericProvider([
            'clientId' => $GLOBALS['wgOAuthClientId'],
            'clientSecret' => $GLOBALS['wgOAuthClientSecret'],
            'redirectUri' => $GLOBALS['wgOAuthRedirectUri'],
            'urlAuthorize'            => $GLOBALS['wgOAuthUri'] . '/apps/oauth2/authorize',
            'urlAccessToken'          => $GLOBALS['wgOAuthUri'] . '/apps/oauth2/api/v1/token',
            'urlResourceOwnerDetails' => $GLOBALS['wgOAuthUri'] . '/ocs/v2.php/cloud/user?format=json'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function login(&$key, &$secret, &$auth_url)
    {
        $auth_url = $this->provider->getAuthorizationUrl([
            'scope' => []
        ]);

        $secret = $this->provider->getState();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function logout(\User &$user)
    {
    }

    /**
     * @inheritDoc
     */
    public function getUser($key, $secret, &$errorMessage)
    {
        if (!isset($_GET['code'])) {
            return false;
        }

        if (!isset($_GET['state']) || empty($_GET['state']) || ($_GET['state'] !== $secret)) {
            return false;
        }

        try {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $user = $this->provider->getResourceOwner($token);

            $data = $user->toArray()['ocs']['data'];
            return [
                'name' => $data['id'],
                'realname' => $data['displayname'],
                'email' => $data['email']
            ];
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function saveExtraAttributes($id)
    {
    }
}
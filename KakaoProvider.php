<?php

namespace SocialiteProviders\Kakao;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class KakaoProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'KAKAO';

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://kauth.kakao.com/oauth/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://kauth.kakao.com/oauth/token';
    }

    /**
     * Get the access token for the given code.
     *
     * @param string $code
     *
     * @return string
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->request('POST', $this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'grant_type'   => 'authorization_code', 'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl, 'code' => $code,
        ];
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->request('POST', 'https://kapi.kakao.com/v1/user/me', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Laravel\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        if ( ! isset($user['properties'])) {
            $user['properties'] = [];
        }

        return (new User())->setRaw($user)->map([
            'id'        => $this->getUserData($user, 'id'),
            'nickname'  => $this->getUserData($user['properties'], 'nickname'),
            'name'      => $this->getUserData($user['properties'], 'nickname'),
            'email'     => $this->getUserData($user, 'kaccount_email'),
            'avatar'    => $this->getUserData($user['properties'], 'profile_image'),
        ]);
    }

    /**
     * @param array $user
     * @param string $key
     *
     * @return string|null
     */
    private function getUserData($user, $key)
    {
        return isset($user[$key]) ? $user[$key] : null;
    }
}

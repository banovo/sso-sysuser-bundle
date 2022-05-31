<?php

namespace Banovo\SSOSysuserBundle;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;

/**
 * Helper class to get an access token for configured machine user
 * from openid-connect endpoint
 */
class Sysuser
{
	/**
	 * @var array
	 */
	private $config = [];
	/**
	 * @var bool
	 */
	private $accessToken = false;
	/**
	 * @var bool
	 */
	private $refreshToken = false;
	/**
	 * @var int
	 */
	private $expTimestamp = 0;

	/**
	 * @param $banovoSsoSysuserConfig
	 */
	public function __construct($banovoSsoSysuserConfig)
	{
		$this->config = $banovoSsoSysuserConfig;
	}

	/**
	 * @param $credentialScope
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getToken($credentialScope)
	{
		if ($this->accessToken && $this->isExpired()) {
			$this->invalidateSession();
		}

		if (!$this->accessToken) {
			$this->fetchToken($this->getCredentials($credentialScope));
		}

		$this->isExpired();

		return "Bearer $this->accessToken";
	}

	/**
	 * @return bool
	 */
	private function isExpired()
	{
		if (time() >= $this->expTimestamp - $this->config['sso_configuration']['token_expires_deduct_seconds']) {
			return true;
		}

		return false;
	}

	/**
	 * @param $credentialsScope
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getCredentials($credentialsScope)
	{
		if (!array_key_exists($credentialsScope, $this->config['sysusers_configuration'])) {
			throw new \Exception("Credentials Scope not configured in sysusers_configuration");
		}

		$credentials = [];
		$credentials["username"] = key($this->config['sysusers_configuration'][$credentialsScope]);
		$credentials["password"] = current($this->config['sysusers_configuration'][$credentialsScope]);

		return $credentials;
	}

	/**
	 * @param $credentials
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchToken($credentials)
	{
		$client = new Client();
		$response = $client->request(
			'POST', $this->config['sso_configuration']['token_endpoint'],
			[
				'form_params' => [
					"grant_type"    => "password",
					"client_id"     => $this->config['sso_configuration']['client_id'],
					"client_secret" => $this->config['sso_configuration']['client_secret'],
					"username"      => $credentials['username'],
					"password"      => $credentials['password'],
				]
			]
		);

		$code = $response->getStatusCode();
		if (200 != $code) {
			throw new \Exception($response->getReasonPhrase(), $code);
		}

		$body = json_decode($response->getBody());

		// highly opinionated:
		// issued token MUST contain APP_SYSTEM group
		if (!in_array('APP_SYSTEM', json_decode(base64_decode(explode('.', $body->access_token)[1]))->groups)) {
			throw new \Exception("Configured sysuser is no member of APP_SYSTEM");
		};

		$this->expTimestamp = json_decode(base64_decode(explode('.', $body->access_token)[1]))->exp;
		$this->accessToken = $body->access_token;
		$this->refreshToken = $body->refresh_token;
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	private function invalidateSession()
	{
		if (!$this->refreshToken) {
			return;
		}

		$client = new Client();
		$response = $client->request(
			'POST', $this->config['sso_configuration']['logout_endpoint'],
			[
				'form_params' => [
					"client_id"     => $this->config['sso_configuration']['client_id'],
					"client_secret" => $this->config['sso_configuration']['client_secret'],
					"refresh_token" => $this->refreshToken,
				]
			]
		);

		$code = $response->getStatusCode();
		if (204 != $code) {
			throw new \Exception($response->getReasonPhrase(), $code);
		}

		$this->accessToken = false;
		$this->refreshToken = false;
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function __destruct()
	{
		$this->invalidateSession();
	}
}

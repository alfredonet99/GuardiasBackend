<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NetSuiteService
{
    protected $baseUrl;
    protected $oauth;

    public function __construct()
    {
        //dd(config('netsuite'));
        $this->baseUrl = config('netsuite.base_url');
        $this->oauth   = config('netsuite.oauth');
    }

    public function makeAuthHeader($method, $url)
    {
        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        $params = [
            'oauth_consumer_key'     => $this->oauth['consumer_key'],
            'oauth_token'            => $this->oauth['token'],
            'oauth_signature_method' => 'HMAC-SHA256',
            'oauth_timestamp'        => $timestamp,
            'oauth_nonce'            => $nonce,
            'oauth_version'          => '1.0',
        ];

        ksort($params);

        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' .
                      rawurlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));

        $signingKey = rawurlencode($this->oauth['consumer_secret']) . '&' . rawurlencode($this->oauth['token_secret']);
        $signature = base64_encode(hash_hmac('sha256', $baseString, $signingKey, true));

        $params['oauth_signature'] = $signature;

        $header = 'OAuth realm="' . $this->oauth['realm'] . '"';
        foreach ($params as $key => $value) {
            $header .= ', ' . rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }

        return $header;
    }

    public function query($sql)
    {
        $url    = $this->baseUrl;
        $method = 'POST';
        $authHeader = $this->makeAuthHeader($method, $url);

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Prefer'        => 'transient',
        ])->post($url, [
            'q' => $sql,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error'  => true,
            'status' => $response->status(),
            'body'   => $response->body(),
        ];
    }
}

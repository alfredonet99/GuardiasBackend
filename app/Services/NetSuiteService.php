<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

   public function makeAuthHeader($method, $url, array $extraParams = [])
    {
        $nonce     = bin2hex(random_bytes(16));
        $timestamp = time();

        $oauthParams = [
            'oauth_consumer_key'     => $this->oauth['consumer_key'],
            'oauth_token'            => $this->oauth['token'],
            'oauth_signature_method' => 'HMAC-SHA256',
            'oauth_timestamp'        => $timestamp,
            'oauth_nonce'            => $nonce,
            'oauth_version'          => '1.0',
        ];

        // 👇 Mezcla OAuth + query params (p.ej. limit/offset)
        $params = array_merge($oauthParams, $extraParams);
        ksort($params);

        // Construye el param string exactamente con encoding OAuth
        $encoded = [];
        foreach ($params as $k => $v) {
            $encoded[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $paramString = implode('&', $encoded);

        // ❗ URL SIN querystring
        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);

        $signingKey = rawurlencode($this->oauth['consumer_secret']) . '&' . rawurlencode($this->oauth['token_secret']);
        $signature  = base64_encode(hash_hmac('sha256', $baseString, $signingKey, true));

        $oauthParams['oauth_signature'] = $signature;

        // Header: claves sin encode; valores con rawurlencode
        $header = 'OAuth realm="' . $this->oauth['realm'] . '"';
        foreach ($oauthParams as $k => $v) {
            $header .= ', ' . $k . '="' . rawurlencode($v) . '"';
        }

        // Logs para verificar (opcional)
        Log::info('🔐 OAuth debug', [
            'signed_url'   => $url,            // ✅ sin ?limit&offset
            'extra_params' => $extraParams,    // ✅ limit/offset aquí
            'base_string'  => $baseString,
            'signing_key'  => $signingKey,
            'auth_header'  => $header,
        ]);

        return $header;
    }


    
public function query($sql, $limit = null, $offset = null)
{
    $method = 'POST';
    $baseUrl = $this->baseUrl;

    $queryParams = [];
    if (!is_null($limit))  $queryParams['limit'] = $limit;
    if (!is_null($offset)) $queryParams['offset'] = $offset;

    // URL real que vas a llamar (con querystring):
    $urlWithQuery = $baseUrl . (empty($queryParams) ? '' : ('?' . http_build_query($queryParams)));

    // ❗ Firma sobre la URL base (sin query), PERO incluyendo los query params en la firma
    $authHeader = $this->makeAuthHeader($method, $baseUrl, $queryParams);

    $response = Http::withHeaders([
        'Authorization' => $authHeader,
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
        'Prefer'        => 'transient',
        'Content-Language' => 'en',
    ])->post($urlWithQuery, ['q' => $sql]);

    if ($response->successful()) {
        return $response->json();
    }

    Log::error('❌ NetSuite error', [
        'status'   => $response->status(),
        'response' => $response->body(),
    ]);

    return [
        'error'  => true,
        'status' => $response->status(),
        'body'   => $response->body(),
    ];
}

}

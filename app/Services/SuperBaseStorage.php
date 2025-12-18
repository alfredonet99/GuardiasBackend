<?php

namespace App\Services;

use Supabase\Storage\StorageClient;

class SuperBaseStorage
{
    protected StorageClient $client;
    protected string $bucket;

    public function __construct()
    {
        $this->client = new StorageClient(
            config('services.supabase.url'),       
            config('services.supabase.api_key')   
        );

        $this->bucket = config('services.supabase.bucket');
    }

    public function getClient(): StorageClient
    {
        return $this->client;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

     public function getPublicUrl(string $path): string
    {
        $base = rtrim(config('services.supabase.url', env('SUPABASE_URL')), '/');

        return "{$base}/storage/v1/object/public/{$this->bucket}/" . ltrim($path, '/');
    }

}

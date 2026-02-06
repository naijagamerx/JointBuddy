<?php

class IndexNowClient
{
    private $key;
    private $endpoint;

    public function __construct()
    {
        $this->key = getenv('INDEXNOW_KEY') ?: '';
        $this->endpoint = getenv('INDEXNOW_ENDPOINT') ?: 'https://api.indexnow.org/indexnow';
    }

    public function submit(array $urls, $host)
    {
        if (!$this->key || empty($urls)) {
            return false;
        }
        $payload = [
            'host' => $host,
            'key' => $this->key,
            'urlList' => $urls
        ];
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res !== false;
    }
}


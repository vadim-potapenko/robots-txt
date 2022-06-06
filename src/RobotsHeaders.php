<?php

namespace Spatie\Robots;

use InvalidArgumentException;

class RobotsHeaders
{
    protected $robotHeadersProperties = [];

    public static function readFrom(string $source): self
    {
        $content = @file_get_contents($source);

        if ($content === false) {
            throw new InvalidArgumentException("Could not read from source `{$source}`");
        }

        return new self($http_response_header ?? []);
    }

    public static function create(array $headers): self
    {
        return new self($headers);
    }

    public function __construct(array $headers)
    {
        $this->robotHeadersProperties = $this->parseHeaders($headers);
    }

    public function mayIndex(string $userAgent = '*'): bool
    {
        return $this->none($userAgent) ? false : !$this->noindex($userAgent);
    }

    public function mayFollow(string $userAgent = '*'): bool
    {
        return  $this->none($userAgent) ? false : !$this->nofollow($userAgent);
    }

    public function noindex(string $userAgent = '*'): bool
    {
        return
            $this->robotHeadersProperties[$userAgent]['noindex']
            ?? $this->robotHeadersProperties['*']['noindex']
            ?? false;
    }

    public function nofollow(string $userAgent = '*'): bool
    {
        return
            $this->robotHeadersProperties[$userAgent]['nofollow']
            ?? $this->robotHeadersProperties['*']['nofollow']
            ?? false;
    }

    public function none(string $userAgent = '*'): bool
    {
        return
            $this->robotHeadersProperties[$userAgent]['none']
            ?? $this->robotHeadersProperties['*']['none']
            ?? false;
    }

    protected function parseHeaders(array $headers): array
    {
        $robotHeaders = $this->filterRobotHeaders($headers);
        return array_reduce($robotHeaders, function (array $parsedHeaders, $header) {
            $header = $this->normalizeHeaders($header);
            $headerParts = explode(':', $header);
            $userAgent = count($headerParts) >= 3
                ? trim($headerParts[1])
                : '*';

            $options = end($headerParts);


            $parsedHeaders[$userAgent] = array_merge(
                $parsedHeaders[$userAgent] ?? [$userAgent => []],
                array_filter(
                    [
                        'noindex' => strpos(strtolower($options), 'noindex') !== false,
                        'nofollow' => strpos(strtolower($options), 'nofollow') !== false,
                        'none' => strpos(strtolower($options), 'none') !== false,
                        'max-snippet' => strpos(strtolower($header), 'max-snippet') ? trim($options) : false,
                        'max-image-preview' =>  strpos(strtolower($header), 'max-image-preview') ? trim($options) : false,
                        'max-video-preview' => strpos(strtolower($header), 'max-video-preview') ? trim($options) : false
                    ],
                    function ($element) {
                        return $element;
                    }
                )
            );

            return $parsedHeaders;
        }, []);
    }

    protected function filterRobotHeaders(array $headers): array
    {
        return array_filter($headers, function ($header) use ($headers) {
            $headerContent = $this->normalizeHeaders($headers[$header] ?? []);

            return strpos(strtolower($header), 'x-robots-tag') === 0
                || strpos(strtolower($headerContent), 'x-robots-tag') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function normalizeHeaders($headers): string
    {
        return implode(',', (array) $headers);
    }

    public function maxsnippet(string $userAgent = '*'): string
    {
        return
            $this->robotHeadersProperties[$userAgent]['max-snippet']
            ?? $this->robotHeadersProperties['*']['max-snippet']
            ?? false;
    }

    public function maximagepreview(string $userAgent = '*'): string
    {
        return
            $this->robotHeadersProperties[$userAgent]['max-image-preview']
            ?? $this->robotHeadersProperties['*']['max-image-preview']
            ?? false;
    }

    public function maxvideopreview(string $userAgent = '*'): string
    {
        return
            $this->robotHeadersProperties[$userAgent]['max-video-preview']
            ?? $this->robotHeadersProperties['*']['max-video-preview']
            ?? false;
    }

    public function getMeta(string $userAgent = '*'): array
    {
        return
            $this->robotHeadersProperties[$userAgent]
            ?? array();
    }
}

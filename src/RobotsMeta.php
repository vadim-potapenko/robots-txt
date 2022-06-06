<?php

namespace Spatie\Robots;

use InvalidArgumentException;

class RobotsMeta
{
    protected $robotsMetaTagProperties = [];
    protected $metaTagProperties = [];

    public static function readFrom(string $source): self
    {
        $content = @file_get_contents($source);

        if ($content === false) {
            throw new InvalidArgumentException("Could not read from source `{$source}`");
        }

        return new self($content);
    }

    public static function create(string $source): self
    {
        return new self($source);
    }

    public function __construct(string $html)
    {
        $this->robotsMetaTagProperties = $this->findRobotsMetaTagProperties($html);
        $this->metaTagProperties = $this->findMetaTagProperties($html);
    }

    public function mayIndex(string $userAgent = 'robots'): bool
    {
        return !$this->noindex($userAgent);
    }

    public function mayFollow(string $userAgent = 'robots'): bool
    {
        return !$this->nofollow($userAgent);
    }

    public function noindex(string $userAgent = 'robots'): bool
    {
        //return $this->robotsMetaTagProperties['noindex'] ?? false;
        return $this->metaTagProperties[$userAgent]['noindex'] ?? false;
    }

    public function nofollow(string $userAgent = 'robots'): bool
    {
        //return $this->robotsMetaTagProperties['nofollow'] ?? false;
        return $this->metaTagProperties[$userAgent]['nofollow'] ?? false;
    }

    protected function findRobotsMetaTagProperties(string $html): array
    {
        $metaTagLine = $this->findRobotsMetaTagLine($html);

        return [
            'noindex' => $metaTagLine
                ? strpos(strtolower($metaTagLine), 'noindex') !== false
                : false,

            'nofollow' => $metaTagLine
                ? strpos(strtolower($metaTagLine), 'nofollow') !== false
                : false,
        ];
    }

    protected function findRobotsMetaTagLine(string $html): ?string
    {
        if (preg_match('/\<meta name=("|\')robots("|\').*?\>/mis', $html, $matches)) {
            return $matches[0];
        }

        return null;
    }

    protected function findMetaTagProperties(string $html): array
    {
        $metaTagArray = $this->getMetaTags($html);
        $result = array();

        foreach ($metaTagArray as $botName => $metaForSpecificBot) {
            $result[$botName] = array();
            if (is_array($metaForSpecificBot)) { // several meta tags with same name
                foreach ($metaForSpecificBot as $key => $metaContentString) {
                    $result[$botName] = array_merge( $result[$botName],  $this->findDirectives($metaContentString));
                }
            } else {
                $result[$botName] = $this->findDirectives($metaForSpecificBot);
            }
        }

        return $result;
    }

    protected function findDirectives($str)
    {
        $metaDataArray = explode(',', $str);
        $directivesPatterns = array(
            'max-snippet' => '~max-snippet:(.*)~mis',
            'max-image-preview' => '~max-image-preview:(\s)?(large|none|standard)~mis',
            'max-video-preview' => '~max-video-preview:(.*)~mis',
            'nofollow' => '~nofollow~mis',
            'noindex' => '~noindex~mis'
        );

        $result = array();
        if (is_array($metaDataArray)) {
            foreach ($metaDataArray as $metaData) {
                foreach ($directivesPatterns as $directiveName => $directivePattern) {
                    if (preg_match($directivePattern, $metaData, $matches)) {
                        $result[$directiveName] = end($matches);
                    }    
                }
            }
        }
        return $result;
    }

    protected function getMetaTags($str)
    {
        $pattern = '
            ~<\s*meta\s

            # using lookahead to capture type to $1
                (?=[^>]*?
                \b(?:name|property|http-equiv)\s*=\s*
                (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
                ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
            )

            # capture content to $2
            [^>]*?\bcontent\s*=\s*
                (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
                ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
            [^>]*>

            ~ix';

        if (preg_match_all($pattern, $str, $out)) {
            //return array_combine($out[1], $out[2]); in this case we miss duplicates
            $keys = $out[1];
            $values = $out[2];
            $result = array();
            foreach ($keys as $i => $k) {
                $result[$k][] = $values[$i];
            }
            array_walk($result, function (&$v) {
                $v = (count($v) == 1) ? array_pop($v) : $v;
            });
            return  $result;
        }
        return array();
    }

    public function maxsnippet(string $userAgent = 'robots'): string
    {
        //!! need to take into account 'nosnipet'
        return $this->metaTagProperties[$userAgent]['max-snipet'] ?? false;
    }

    public function maximagepreview(string $userAgent = 'robots'): string
    {
        return $this->metaTagProperties[$userAgent]['max-image-preview'] ?? false;
    }

    public function maxvideopreview(string $userAgent = 'robots'): string
    {
        return $this->metaTagProperties[$userAgent]['max-video-preview'] ?? false;
    }

    public function getMetaInformation(string $userAgent = 'robots') : array
    {
        return $this->metaTagProperties[$userAgent] ?? array();
    }

}

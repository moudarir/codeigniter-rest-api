<?php

namespace Moudarir\CodeigniterApi\Routes;

class RouteParam
{

    private string $name;

    private string $regex;

    private string $placeholder;

    private bool $optional;

    private string $segment;

    public string $value;

    public int $segmentIndex;

    public string $fullSegment;

    /**
     * CodeIgniter placeholder conversion
     */
    private static array $placeholderPatterns = [
        '{num:[a-zA-Z0-9-_%]*(\?}|})' => '(:num)', # (:num) route
        '{any:[a-zA-Z0-9-_%]*(\?}|})' => '(:any)', # (:any) route
        '{id:*(\?}|})' => '([0-9]+)',
        '{slug*(\?}|})' => '([a-zA-Z0-9\-%]+)',
        '{token*(\?}|})' => '([a-zA-Z0-9]{10}+)',
        '{username*(\?}|})' => '([a-zA-Z0-9]{2,10}+)',
        '{folder*(\?}|})' => '([a-zA-Z0-9\-_\/]+)',
        '{file*(\?}|})' => '([a-zA-Z0-9\-_\/\.]+)',
        '{alpha*(\?}|})' => '([a-zA-Z\-_]+)',
        '{alnum*(\?}|})' => '([a-zA-Z0-9\-_]+)',
        '{regex*(\?}|})' => '([a-zA-Z0-9\-%:+,\._=~]+)',
        '{[a-zA-Z0-9-_%]*(\?}|})' => '(:any)', # Everything else
    ];

    /**
     * CodeIgniter placeholder -> regex conversion
     */
    private static array $placeholderReplacements = [
        '/\(:any\)/' => '[^/]+',
        '/\(:num\)/' => '[0-9]+',
    ];

    /**
     * CodeIgniter placeholder conversion array
     */
    public static function getPlaceholderReplacements(): array
    {
        return self::$placeholderReplacements;
    }

    /**
     * @param string $segment Original route segment
     */
    public function __construct(string $segment, $segmentIndex, $fullSegment)
    {
        $this->segment = $segment;
        $this->segmentIndex = $segmentIndex;
        $this->fullSegment = $fullSegment;
        $this->optional = \substr($segment, -2, 1) === '?';

        if (\preg_match('/{\((.*)\):[a-zA-Z0-9-_%]*(\?}|})/', $segment, $matches)) {
            $this->placeholder = '(' . $matches[1] . ')';
            $this->regex = $matches[1];
            $name = \preg_replace('/\((.*)\):/', '', $segment, 1);
            $this->name = \substr($name, 1, !$this->optional ? -1 : -2);
        } else {
            foreach (self::$placeholderPatterns as $regex => $replacement) {
                if ($segment !== \preg_replace('/'.$regex.'/', $replacement, $segment)) {
                    $this->placeholder = $replacement;
                    $this->regex = \preg_replace(\array_keys(self::$placeholderReplacements), \array_values(self::$placeholderReplacements), $replacement, 1);
                    $name = \preg_replace(['/num:/', '/any:/'], '', $segment, 1);
                    $this->name = \substr($name, 1, !$this->optional ? -1 : -2);
                    break;
                }
            }
        }
    }

    /**
     * Gets parameter name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets original segment
     */
    public function getSegment(): string
    {
        return $this->segment;
    }

    /**
     * Gets segment regex
     */
    public function getRegex(): string
    {
        return $this->regex;
    }

    /**
     * Gets segment placeholder
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * Checks if a segment is optional or not
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function getFullSegment(): string
    {
        return $this->fullSegment;
    }

    public function getSegmentIndex(): int
    {
        return $this->segmentIndex;
    }
}

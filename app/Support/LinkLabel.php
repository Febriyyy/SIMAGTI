<?php

namespace App\Support;

/**
 * Label ramah untuk tautan portofolio, dikenali dari alamatnya (GitHub, Figma, YouTube, Google Drive, dll).
 * Bila tidak dikenali, nama domain yang dipakai.
 */
class LinkLabel
{
    /** potongan domain => label */
    private const KNOWN = [
        'github.com' => 'GitHub',
        'gitlab.com' => 'GitLab',
        'bitbucket.org' => 'Bitbucket',
        'figma.com' => 'Figma',
        'youtube.com' => 'YouTube',
        'youtu.be' => 'YouTube',
        'vimeo.com' => 'Vimeo',
        'drive.google.com' => 'Google Drive',
        'docs.google.com' => 'Google Docs',
        'linkedin.com' => 'LinkedIn',
        'behance.net' => 'Behance',
        'dribbble.com' => 'Dribbble',
        'medium.com' => 'Medium',
        'kaggle.com' => 'Kaggle',
        'notion.site' => 'Notion',
        'notion.so' => 'Notion',
        'canva.com' => 'Canva',
        'play.google.com' => 'Play Store',
        'apps.apple.com' => 'App Store',
        'vercel.app' => 'Demo',
        'netlify.app' => 'Demo',
        'github.io' => 'Demo',
        'herokuapp.com' => 'Demo',
        'dicoding.com' => 'Dicoding',
        'coursera.org' => 'Coursera',
        'credly.com' => 'Credly',
        'udemy.com' => 'Udemy',
    ];

    public static function host(string $url): string
    {
        return preg_replace('/^www\./', '', mb_strtolower((string) parse_url($url, PHP_URL_HOST)));
    }

    public static function for(string $url): string
    {
        $host = self::host($url);
        foreach (self::KNOWN as $needle => $label) {
            if ($host === $needle || str_ends_with($host, '.'.$needle)) {
                return $label;
            }
        }

        return $host ?: 'Tautan';
    }

    /**
     * Daftar tautan siap tampil: [['url' => ..., 'label' => ...], ...]
     *
     * @param  array<int, string>|null  $links
     */
    public static function list(?array $links): array
    {
        return collect($links ?? [])->filter()->unique()->map(fn ($url) => ['url' => $url, 'label' => self::for($url)])->values()->all();
    }

    /**
     * Rapikan isian: buang kosong dan ganda, paling banyak $max.
     *
     * @param  array<int, mixed>  $urls
     */
    public static function clean(array $urls, int $max = 5): array
    {
        return collect($urls)->map(fn ($u) => trim((string) $u))->filter()->unique()->take($max)->values()->all();
    }
}

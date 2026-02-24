<?php

namespace App\Console\Commands;

use App\Models\Frontend;
use App\Models\Page;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap {--url=} {--path=*}';
    protected $description = 'Generate sitemap.xml for public pages';

    public function handle(): int
    {
        $baseUrl = rtrim($this->option('url') ?: config('app.url'), '/');

        if (!$baseUrl) {
            $this->error('APP_URL is missing. Set APP_URL or use --url=https://your-domain.com');
            return self::FAILURE;
        }

        $entries = $this->buildEntries($baseUrl);
        $xml = $this->buildXml($entries);

        $outputPaths = $this->resolveOutputPaths();
        $writtenCount = 0;

        foreach ($outputPaths as $path) {
            if ($this->writeFile($path, $xml)) {
                $writtenCount++;
                $this->info("Sitemap written: {$path}");
            }
        }

        if (!$writtenCount) {
            $this->error('No sitemap file was written. Check write permissions or pass --path=/full/path/sitemap.xml');
            return self::FAILURE;
        }

        $this->line('Total URLs: ' . $entries->count());
        return self::SUCCESS;
    }

    private function buildEntries(string $baseUrl): Collection
    {
        $entries = collect();

        $addEntry = function (string $path, ?CarbonInterface $updatedAt = null, string $changeFreq = 'weekly', string $priority = '0.7') use ($baseUrl, $entries) {
            $normalizedPath = '/' . ltrim($path, '/');
            if ($normalizedPath === '//') {
                $normalizedPath = '/';
            }

            $entries->put($normalizedPath, [
                'loc' => $baseUrl . ($normalizedPath === '/' ? '' : $normalizedPath),
                'lastmod' => ($updatedAt ?: now())->toAtomString(),
                'changefreq' => $changeFreq,
                'priority' => $priority,
            ]);
        };

        $addEntry('/', now(), 'daily', '1.0');
        $addEntry('/contact', now(), 'monthly', '0.8');
        $addEntry('/blogs', now(), 'daily', '0.8');
        $addEntry('/api-documentation', now(), 'weekly', '0.9');

        Page::query()
            ->select('slug', 'updated_at')
            ->where('slug', '!=', '/')
            ->get()
            ->each(function ($page) use ($addEntry) {
                $slug = trim((string) $page->slug, '/');

                if ($slug === '' || in_array($slug, ['contact', 'blog', 'blogs', 'api-documentation'])) {
                    return;
                }

                $addEntry("/{$slug}", $page->updated_at, 'weekly', '0.8');
            });

        Frontend::query()
            ->select('slug', 'updated_at')
            ->where('data_keys', 'policy_pages.element')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get()
            ->each(function ($policy) use ($addEntry) {
                $slug = trim((string) $policy->slug, '/');
                if ($slug === '') {
                    return;
                }

                $addEntry("/policy/{$slug}", $policy->updated_at, 'yearly', '0.6');
            });

        Frontend::query()
            ->select('slug', 'updated_at')
            ->where('data_keys', 'blog.element')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get()
            ->each(function ($blog) use ($addEntry) {
                $slug = trim((string) $blog->slug, '/');
                if ($slug === '') {
                    return;
                }

                $addEntry("/blog/{$slug}", $blog->updated_at, 'weekly', '0.7');
            });

        return $entries->values();
    }

    private function buildXml(Collection $entries): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($entries as $entry) {
            $xml->startElement('url');
            $xml->writeElement('loc', $entry['loc']);
            $xml->writeElement('lastmod', $entry['lastmod']);
            $xml->writeElement('changefreq', $entry['changefreq']);
            $xml->writeElement('priority', $entry['priority']);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function resolveOutputPaths(): array
    {
        $paths = $this->option('path');

        if (!empty($paths)) {
            return array_values(array_unique(array_map(function ($path) {
                return $this->normalizeOutputPath($path);
            }, $paths)));
        }

        return array_values(array_unique([
            $this->normalizeOutputPath(base_path('sitemap.xml')),
            $this->normalizeOutputPath(base_path('public/sitemap.xml')),
            $this->normalizeOutputPath(base_path('../sitemap.xml')),
        ]));
    }

    private function normalizeOutputPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path(trim($path));
    }

    private function writeFile(string $path, string $content): bool
    {
        try {
            $directory = dirname($path);

            if (!is_dir($directory)) {
                return false;
            }

            File::put($path, $content);
            return true;
        } catch (\Throwable $exception) {
            $this->warn("Failed to write {$path}: " . $exception->getMessage());
            return false;
        }
    }
}

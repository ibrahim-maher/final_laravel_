<?php
// app/Modules/Page/Services/PageService.php

namespace App\Modules\Page\Services;

use App\Modules\Page\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class PageService
{
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_PREFIX = 'pages_';

    /**
     * Get page by slug with caching
     */
    public function getBySlug($slug, $incrementView = true)
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "slug_{$slug}";

            $page = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
                return Page::where('slug', $slug)->active()->first();
            });

            if ($page && $incrementView) {
                // Don't cache view count updates
                Page::where('id', $page->id)->increment('view_count');
            }

            return $page;
        } catch (\Exception $e) {
            Log::error('Error getting page by slug: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get pages by type with caching
     */
    public function getByType($type, $limit = null)
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "type_{$type}_limit_{$limit}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type, $limit) {
                $query = Page::byType($type)->active()->ordered();

                if ($limit) {
                    $query->limit($limit);
                }

                return $query->get();
            });
        } catch (\Exception $e) {
            Log::error('Error getting pages by type: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get footer pages
     */
    public function getFooterPages()
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'footer_pages';

            return Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return Page::forFooter()->active()->ordered()->get();
            });
        } catch (\Exception $e) {
            Log::error('Error getting footer pages: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get header pages
     */
    public function getHeaderPages()
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'header_pages';

            return Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return Page::forHeader()->active()->ordered()->get();
            });
        } catch (\Exception $e) {
            Log::error('Error getting header pages: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get featured pages
     */
    public function getFeaturedPages($limit = 5)
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "featured_limit_{$limit}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
                return Page::featured()->active()->ordered()->limit($limit)->get();
            });
        } catch (\Exception $e) {
            Log::error('Error getting featured pages: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Search pages
     */
    public function searchPages($query, $type = null, $limit = 20)
    {
        try {
            $pagesQuery = Page::search($query)->active();

            if ($type) {
                $pagesQuery->byType($type);
            }

            return $pagesQuery->ordered()->limit($limit)->get();
        } catch (\Exception $e) {
            Log::error('Error searching pages: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get popular pages
     */
    public function getPopularPages($limit = 10)
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "popular_limit_{$limit}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
                return Page::active()
                    ->orderBy('view_count', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
            });
        } catch (\Exception $e) {
            Log::error('Error getting popular pages: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get recent pages
     */
    public function getRecentPages($limit = 10)
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "recent_limit_{$limit}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
                return Page::active()
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
            });
        } catch (\Exception $e) {
            Log::error('Error getting recent pages: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Clear all page caches
     */
    public function clearPageCaches()
    {
        try {
            $patterns = [
                self::CACHE_PREFIX . '*'
            ];

            foreach ($patterns as $pattern) {
                Cache::flush(); // In production, you might want to use a more specific cache clearing method
            }

            Log::info('Page caches cleared');
        } catch (\Exception $e) {
            Log::error('Error clearing page caches: ' . $e->getMessage());
        }
    }

    /**
     * Get pages statistics
     */
    public function getStatistics()
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'statistics';

            return Cache::remember($cacheKey, 300, function () { // 5 minute cache
                $totalPages = Page::count();
                $activePages = Page::where('status', 'active')->count();
                $draftPages = Page::where('status', 'draft')->count();
                $inactivePages = Page::where('status', 'inactive')->count();
                $featuredPages = Page::where('is_featured', true)->count();

                $totalViews = Page::sum('view_count');
                $pagesThisMonth = Page::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();

                // Type breakdown
                $typeStats = Page::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray();

                // Popular pages
                $popularPages = Page::active()
                    ->orderBy('view_count', 'desc')
                    ->limit(5)
                    ->get(['id', 'title', 'view_count', 'type']);

                // Recent pages
                $recentPages = Page::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'title', 'created_at', 'status', 'type']);

                return [
                    'total_pages' => $totalPages,
                    'active_pages' => $activePages,
                    'draft_pages' => $draftPages,
                    'inactive_pages' => $inactivePages,
                    'featured_pages' => $featuredPages,
                    'total_views' => $totalViews,
                    'pages_this_month' => $pagesThisMonth,
                    'type_stats' => $typeStats,
                    'popular_pages' => $popularPages,
                    'recent_pages' => $recentPages,
                    'average_views' => $totalPages > 0 ? round($totalViews / $totalPages, 2) : 0
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting page statistics: ' . $e->getMessage());
            return [
                'total_pages' => 0,
                'active_pages' => 0,
                'draft_pages' => 0,
                'inactive_pages' => 0,
                'featured_pages' => 0,
                'total_views' => 0,
                'pages_this_month' => 0,
                'type_stats' => [],
                'popular_pages' => collect(),
                'recent_pages' => collect(),
                'average_views' => 0
            ];
        }
    }

    /**
     * Export pages to CSV
     */
    public function exportToCsv($pages)
    {
        try {
            $filename = 'pages_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($pages) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, [
                    'ID',
                    'Title',
                    'Slug',
                    'Type',
                    'Status',
                    'Language',
                    'Views',
                    'Is Featured',
                    'Show in Footer',
                    'Show in Header',
                    'Template',
                    'Reading Time',
                    'Word Count',
                    'Published At',
                    'Created At',
                    'Updated At'
                ]);

                // CSV Data
                foreach ($pages as $page) {
                    fputcsv($file, [
                        $page->id,
                        $page->title,
                        $page->slug,
                        $page->type,
                        $page->status,
                        $page->language ?? 'en',
                        $page->view_count ?? 0,
                        $page->is_featured ? 'Yes' : 'No',
                        $page->show_in_footer ? 'Yes' : 'No',
                        $page->show_in_header ? 'Yes' : 'No',
                        $page->template ?? 'default',
                        $page->reading_time,
                        $page->word_count,
                        $page->published_at ? $page->published_at->format('Y-m-d H:i:s') : '',
                        $page->created_at ? $page->created_at->format('Y-m-d H:i:s') : '',
                        $page->updated_at ? $page->updated_at->format('Y-m-d H:i:s') : ''
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting pages to CSV: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get page breadcrumb
     */
    public function getBreadcrumb($page)
    {
        try {
            $breadcrumb = [
                ['title' => 'Home', 'url' => '/'],
            ];

            // Add type-specific breadcrumb
            if ($page->type) {
                $typeLabel = Page::getTypes()[$page->type] ?? ucfirst($page->type);
                $breadcrumb[] = [
                    'title' => $typeLabel,
                    'url' => '/pages?type=' . $page->type
                ];
            }

            // Add current page
            $breadcrumb[] = [
                'title' => $page->title,
                'url' => null // Current page
            ];

            return $breadcrumb;
        } catch (\Exception $e) {
            Log::error('Error generating breadcrumb: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate sitemap data for pages
     */
    public function getSitemapData()
    {
        try {
            return Page::active()
                ->select(['slug', 'updated_at'])
                ->get()
                ->map(function ($page) {
                    return [
                        'url' => url('/page/' . $page->slug),
                        'lastmod' => $page->updated_at->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.7'
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Error generating sitemap data: ' . $e->getMessage());
            return collect();
        }
    }
}

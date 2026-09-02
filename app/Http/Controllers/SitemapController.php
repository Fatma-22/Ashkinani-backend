<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\News;
use App\Models\Sponsor;
use App\Models\Deal;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = 'https://ashkananitransfer.com';

        // Get public players
        $players = Player::where('is_approved', true)
                         ->where('is_visible', true)
                         ->get();

        // Get news
        $news = News::orderBy('published_at', 'desc')->get();

        // Get sponsors
        $sponsors = Sponsor::all();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Static Pages
        $staticPages = ['', '/players', '/deals', '/sponsors', '/news', '/media'];
        foreach ($staticPages as $page) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . $page . '</loc>';
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>' . ($page === '' ? '1.0' : '0.8') . '</priority>';
            $xml .= '</url>';
        }

        // Dynamic Players
        foreach ($players as $player) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/players/' . $player->id . '</loc>';
            $xml .= '<lastmod>' . ($player->updated_at ? $player->updated_at->format('Y-m-d') : date('Y-m-d')) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        // Dynamic News
        foreach ($news as $article) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/news/' . $article->id . '</loc>';
            $xml .= '<lastmod>' . date('Y-m-d', strtotime($article->published_at)) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }

        // Dynamic Sponsors
        foreach ($sponsors as $sponsor) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/sponsors/' . $sponsor->id . '</loc>';
            $xml .= '<lastmod>' . ($sponsor->updated_at ? $sponsor->updated_at->format('Y-m-d') : date('Y-m-d')) . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.5</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}

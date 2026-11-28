<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * The robots.txt file, generated to give the sitemap's absolute address. It keeps crawlers out of the pages
 * reserved to logged-in users, but not out of `/api`: search engines render the SPA, which needs the public
 * endpoints to show a group or a collection.
 */
class RobotsTxtController extends Controller
{
    /**
     * @var list<string>
     */
    private const array DISALLOWED_PATHS = [
        '/dashboard',
        '/profile',
        '/security',
        '/preferences',
        '/users/',
        '/share/',
        '/groups/new',
        '/groups/*/edit',
        '/collections/new',
        '/collections/*/edit',
        '/email/',
        '/reset-password/',
    ];

    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            ...array_map(fn (string $path): string => "Disallow: {$path}", self::DISALLOWED_PATHS),
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

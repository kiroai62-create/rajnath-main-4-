<?php

namespace App\Http\Middleware;

use App\Domain\Translation\PageTranslator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Puts the whole page into the visitor's chosen language (see PageTranslator). Runs on website
 * and customer-account pages only; the control centre and the API stay as they are.
 *
 * New text is sent to AI only from pages without personal details, and never text that contains
 * what the visitor typed or their own name, email or phone. Customer account pages, receipts and
 * "request received" pages use existing translations only.
 */
class TranslatePage
{
    /** Pages whose new text is never sent to AI (they show personal details). */
    private const NO_LEARN_ROUTES = ['account.*', 'checkout.*', 'enquiry.thanks', 'puja-request.thanks', 'store.buy', 'files.*', 'puja.photo'];

    public function __construct(private PageTranslator $translator) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $locale = app()->getLocale();
        if ($locale === 'en' || $request->is('admin', 'admin/*', 'staff/*', 'api/*') || ! $this->isHtml($response)) {
            return $response;
        }
        try {
            $user = $request->user();
            $learn = ! $request->routeIs(...self::NO_LEARN_ROUTES) && $response->getStatusCode() < 400;
            $private = array_merge(
                array_filter(array_map(fn ($v) => is_scalar($v) ? (string) $v : null, $request->except(['_token', 'lang', 'website', 'consent']))),
                $user ? array_filter([$user->name, $user->email, $user->phone_e164]) : [],
            );
            $html = $this->translator->translate((string) $response->getContent(), $locale, $learn, array_values($private));
            $response->setContent($html);
            $response->headers->remove('Content-Length');
        } catch (Throwable $e) {
            // A translation problem must never break the page: show it in English instead.
            Log::warning('Page translation failed', ['locale' => $locale, 'path' => $request->path(), 'error' => $e->getMessage()]);
        }

        return $response;
    }

    private function isHtml(Response $response): bool
    {
        return ! $response->isRedirection()
            && ! $response instanceof StreamedResponse
            && ! $response instanceof BinaryFileResponse
            && str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')
            && is_string($response->getContent()) && $response->getContent() !== '';
    }
}

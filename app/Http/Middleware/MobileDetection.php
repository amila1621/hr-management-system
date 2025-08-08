<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MobileDetection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user agent indicates mobile device
        $userAgent = $request->header('User-Agent');
        $isMobile = $this->detectMobile($userAgent);
        
        // Add mobile detection to request
        $request->attributes->set('is_mobile', $isMobile);
        
        // You can also set it in session for persistence
        session(['is_mobile' => $isMobile]);
        
        return $next($request);
    }
    
    /**
     * Detect if the user agent is from a mobile device
     *
     * @param string $userAgent
     * @return bool
     */
    private function detectMobile($userAgent)
    {
        // Mobile detection patterns
        $mobilePatterns = [
            '/Mobile/',
            '/Android/',
            '/iPhone/',
            '/iPad/',
            '/iPod/',
            '/BlackBerry/',
            '/Windows Phone/',
            '/Opera Mini/',
            '/IEMobile/',
            '/Mobile Safari/',
            '/webOS/',
            '/Kindle/',
            '/Silk/',
            '/Mobile.*Firefox/',
            '/Mobile.*Chrome/',
        ];
        
        foreach ($mobilePatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        // Check for small screen sizes (additional detection)
        if (strpos($userAgent, 'Mobi') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Force mobile view (for testing purposes)
     *
     * @param Request $request
     * @return bool
     */
    public static function forceMobile(Request $request)
    {
        return $request->has('mobile') && $request->get('mobile') === '1';
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     * เพิ่ม HTTP Security Headers ที่ปลอดภัยและไม่กระทบการทำงานของระบบ
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. ป้องกัน Clickjacking: ไม่อนุญาตให้ฝังเว็บใน iframe จากต่างโดเมน
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. ป้องกัน MIME Sniffing: Browser ต้องทำตาม Content-Type ที่กำหนดเสมอ
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. ควบคุม Referrer: ส่งข้อมูล Referrer เฉพาะกรณีที่ปลอดภัย
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 4. ปิด Feature ที่ไม่ได้ใช้งาน (Permissions Policy)
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        // 5. CSP แบบ Report-Only: ตรวจสอบโดยไม่บล็อก (ปลอดภัย 100%)
        //    ใช้ Console ใน Browser DevTools เพื่อดู Violation Report
        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.datatables.net",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net https://cdn.jsdelivr.net",
                "img-src 'self' data: blob:",
                "connect-src 'self'",
                "frame-ancestors 'self'",
            ])
        );

        // 6. X-XSS-Protection (Legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}

<?php

namespace App\Domain\Tenant;

final class Branding
{
    public function __construct(
        public readonly string $systemName,
        public readonly ?string $sidebarName,
        public readonly ?string $logoUrl,
        public readonly ?string $loginLogoUrl,
        public readonly ?string $faviconUrl,
        /** @var array{primary:string,secondary:string} */
        public readonly array $colors,
        public readonly ?string $fontFamily,
        public readonly bool $darkModeEnabled,
        /** @var array{from:string,to:string} */
        public readonly array $loginGradient,
    ) {
    }

    /** @return array{gc-primary:string,gc-secondary:string,gc-font:string,gc-login-from:string,gc-login-to:string} */
    public function cssVars(): array
    {
        return [
            'gc-primary' => $this->colors['primary'] ?? '79 70 229', // indigo-600 (RGB space-separated)
            'gc-secondary' => $this->colors['secondary'] ?? '14 165 233', // sky-500
            'gc-font' => $this->fontFamily ?: 'Figtree',
            'gc-login-from' => $this->loginGradient['from'] ?? '16 185 129', // emerald-500
            'gc-login-to' => $this->loginGradient['to'] ?? '2 6 23', // slate-950
        ];
    }
}

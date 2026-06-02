<x-guest-layout>
    @php
        $branding = app(\App\Domain\Tenant\TenantContext::class)->branding();
        $logo = file_url($branding->loginLogoUrl ?: $branding->logoUrl);
    @endphp

    <div class="w-full max-w-md">
        <div class="rounded-[28px] border border-white/10 bg-slate-950/50 shadow-2xl shadow-black/30 backdrop-blur-xl"
             x-data="{ loading: false }">
            <div class="flex items-center justify-between px-8 pt-7">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-400/15 text-[11px] font-bold text-emerald-200">PE</span>
                    Perú
                </div>
                <div class="text-right text-xs text-white/60">
                    <div class="font-semibold text-white/80">{{ $branding->systemName }}</div>
                </div>
            </div>

            <div class="px-8 pb-8 pt-6">
                <div class="flex items-center justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 ring-1 ring-inset ring-white/10">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="Logo" class="h-10 w-10 object-contain" />
                        @else
                            <svg class="h-10 w-10 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3l7 4v6c0 5-3 8-7 8s-7-3-7-8V7l7-4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12a3 3 0 016 0v3a3 3 0 11-6 0v-3z" />
                            </svg>
                        @endif
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <div class="text-lg font-semibold text-white/90">Bienvenido</div>
                    <div class="mt-1 text-sm text-white/60">Ingresa con tu número de documento.</div>
                </div>

                <x-auth-session-status class="mt-5" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4"
                      @submit="loading=true">
                    @csrf

                    <div>
                        <label for="documento" class="text-sm font-semibold text-white/80">Número de documento</label>
                        <div class="mt-2">
                            <input id="documento" name="documento" type="text" required autofocus autocomplete="username"
                                   value="{{ old('documento') }}"
                                   placeholder="DNI / CE / RUC / PAS"
                                   class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-white/40 shadow-sm outline-none ring-1 ring-inset ring-transparent focus:border-white/20 focus:ring-primary/30" />
                        </div>
                        <x-input-error :messages="$errors->get('documento')" class="mt-2 text-rose-200" />
                    </div>

                    <div>
                        <label for="password" class="text-sm font-semibold text-white/80">Contraseña</label>
                        <div class="mt-2">
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-white/40 shadow-sm outline-none ring-1 ring-inset ring-transparent focus:border-white/20 focus:ring-primary/30" />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-200" />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-emerald-200/90 hover:text-emerald-200">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit" :disabled="loading"
                            class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-primary/20 hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-70">
                        <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="loading ? 'Ingresando…' : 'Ingresar'">Ingresar</span>
                    </button>

                    <div class="pt-2 text-center text-xs text-white/40">
                        Acceso seguro • Sesión protegida
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $docErr = $errors->get('documento');
        $passErr = $errors->get('password');
        $msg = $errors->any()
            ? (string) (collect($docErr)->merge($passErr)->first() ?: collect($errors->all())->first())
            : '';
    @endphp
    @if ($errors->any() && $msg !== '')
        <script>
            window.addEventListener('load', () => {
                window.GCToast?.error?.('No se pudo iniciar sesión', @js($msg));
            });
        </script>
    @endif
</x-guest-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Configuración</div>
            <div class="text-sm text-slate-500">Personaliza el sistema por tenant ({{ $tenantId }}).</div>
        </div>
    </x-slot>

    @php
        $primaryRgb = old('primary_rgb', $branding->colors['primary'] ?? '79 70 229');
        $secondaryRgb = old('secondary_rgb', $branding->colors['secondary'] ?? '14 165 233');
        $loginFromRgb = old('login_from_rgb', $branding->loginGradient['from'] ?? '16 185 129');
        $loginToRgb = old('login_to_rgb', $branding->loginGradient['to'] ?? '2 6 23');
    @endphp

    <div
        x-data="{
            tab: 'branding',
            primaryRgb: @js($primaryRgb),
            secondaryRgb: @js($secondaryRgb),
            loginFromRgb: @js($loginFromRgb),
            loginToRgb: @js($loginToRgb),
            rgbToHex(rgb) {
                const parts = String(rgb || '').trim().split(/\\s+/).map(x => Math.max(0, Math.min(255, parseInt(x, 10) || 0)));
                if (parts.length !== 3) return '#000000';
                return '#' + parts.map(n => n.toString(16).padStart(2,'0')).join('');
            },
            hexToRgb(hex) {
                const h = String(hex || '').replace('#','').trim();
                if (h.length !== 6) return '0 0 0';
                const r = parseInt(h.slice(0,2),16) || 0;
                const g = parseInt(h.slice(2,4),16) || 0;
                const b = parseInt(h.slice(4,6),16) || 0;
                return `${r} ${g} ${b}`;
            }
        }"
        class="space-y-6"
    >
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="gc-card overflow-hidden">
            <div class="border-b border-slate-200 bg-white/80 px-5 py-4 backdrop-blur">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="rounded-xl px-3 py-1.5 text-sm font-semibold ring-1 ring-inset"
                            :class="tab==='branding' ? 'bg-primary/10 text-primary ring-primary/15' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'"
                            @click="tab='branding'">Branding</button>
                    <button type="button" class="rounded-xl px-3 py-1.5 text-sm font-semibold ring-1 ring-inset"
                            :class="tab==='ui' ? 'bg-primary/10 text-primary ring-primary/15' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'"
                            @click="tab='ui'">UI</button>
                    <button type="button" class="rounded-xl px-3 py-1.5 text-sm font-semibold ring-1 ring-inset"
                            :class="tab==='assets' ? 'bg-primary/10 text-primary ring-primary/15' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'"
                            @click="tab='assets'">Logos & íconos</button>
                    <div class="ms-auto text-xs text-slate-500">Driver: <code class="rounded bg-slate-100 px-1 py-0.5">{{ config('gc.config_store_driver') }}</code></div>
                </div>
            </div>

            <form method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data" class="grid gap-6 p-5 lg:grid-cols-12">
                @csrf

                <div class="lg:col-span-8 space-y-6">
                    <div x-show="tab==='branding'" class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">Paleta GC Domotic</div>
                                    <div class="mt-0.5 text-xs text-slate-500">Base sugerida (personalizable).</div>
                                </div>
                                <button type="button"
                                        class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                        @click="primaryRgb='12 175 125'; secondaryRgb='12 20 68'; loginFromRgb='12 175 125'; loginToRgb='12 20 68'">
                                    Aplicar preset
                                </button>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs font-semibold text-slate-700">Primario</div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl ring-1 ring-inset ring-slate-200" style="background:#0CAF7D"></div>
                                        <div class="text-sm font-semibold text-slate-900">#0CAF7D</div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs font-semibold text-slate-700">Secundario</div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl ring-1 ring-inset ring-slate-200" style="background:#0C1444"></div>
                                        <div class="text-sm font-semibold text-slate-900">#0C1444</div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-xs font-semibold text-slate-700">Base</div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl ring-1 ring-inset ring-slate-200" style="background:#FEFEFE"></div>
                                        <div class="text-sm font-semibold text-slate-900">#FEFEFE</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Nombre del sistema</label>
                                <input name="system_name" value="{{ old('system_name', $branding->systemName) }}"
                                       class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                @error('system_name') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                                <div class="mt-1 text-xs text-slate-500">Se muestra en título, login y navegación.</div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Nombre en sidebar</label>
                                <input name="sidebar_name" value="{{ old('sidebar_name', $branding->sidebarName ?? $branding->systemName) }}"
                                       class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                @error('sidebar_name') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                                <div class="mt-1 text-xs text-slate-500">Más corto si deseas (ej. “GC Domotic”).</div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-semibold text-slate-700">Color primario</div>
                                    <div class="h-6 w-10 rounded-lg bg-primary ring-1 ring-inset ring-slate-200"></div>
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <input type="color" class="h-10 w-12 rounded-lg border border-slate-200 bg-white p-1"
                                           :value="rgbToHex(primaryRgb)"
                                           @input="primaryRgb = hexToRgb($event.target.value)" />
                                    <div class="flex-1">
                                        <input name="primary_rgb" x-model="primaryRgb"
                                               class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                               placeholder="79 70 229" />
                                        <div class="mt-1 text-xs text-slate-500">Formato: <code class="rounded bg-slate-100 px-1 py-0.5">R G B</code></div>
                                    </div>
                                </div>
                                @error('primary_rgb') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-semibold text-slate-700">Color secundario</div>
                                    <div class="h-6 w-10 rounded-lg bg-secondary ring-1 ring-inset ring-slate-200"></div>
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <input type="color" class="h-10 w-12 rounded-lg border border-slate-200 bg-white p-1"
                                           :value="rgbToHex(secondaryRgb)"
                                           @input="secondaryRgb = hexToRgb($event.target.value)" />
                                    <div class="flex-1">
                                        <input name="secondary_rgb" x-model="secondaryRgb"
                                               class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                               placeholder="14 165 233" />
                                    </div>
                                </div>
                                @error('secondary_rgb') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div x-show="tab==='ui'" class="space-y-6" x-cloak>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold text-slate-700">Tipografía</label>
                                <select name="font_family" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary">
                                    @php($ff = old('font_family', $branding->fontFamily))
                                    @foreach (['Figtree','Inter','Plus Jakarta Sans','DM Sans'] as $opt)
                                        <option value="{{ $opt }}" {{ $ff === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @error('font_family') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                                <div class="mt-1 text-xs text-slate-500">Se aplica a todo el dashboard.</div>
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="dark_mode_enabled" value="1" {{ old('dark_mode_enabled', $branding->darkModeEnabled) ? 'checked' : '' }}
                                           class="rounded border-slate-300 text-primary focus:ring-primary" />
                                    Habilitar modo oscuro
                                </label>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold text-slate-700">Fondo del login (gradiente)</div>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div class="flex items-center gap-3">
                                    <input type="color" class="h-10 w-12 rounded-lg border border-slate-200 bg-white p-1"
                                           :value="rgbToHex(loginFromRgb)"
                                           @input="loginFromRgb = hexToRgb($event.target.value)" />
                                    <div class="flex-1">
                                        <div class="text-xs font-medium text-slate-600">Desde</div>
                                        <input name="login_from_rgb" x-model="loginFromRgb" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                        @error('login_from_rgb') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" class="h-10 w-12 rounded-lg border border-slate-200 bg-white p-1"
                                           :value="rgbToHex(loginToRgb)"
                                           @input="loginToRgb = hexToRgb($event.target.value)" />
                                    <div class="flex-1">
                                        <div class="text-xs font-medium text-slate-600">Hasta</div>
                                        <input name="login_to_rgb" x-model="loginToRgb" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                        @error('login_to_rgb') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 rounded-xl border border-slate-200 p-4"
                                 :style="`background: radial-gradient(1200px circle at 10% 10%, rgb(${loginFromRgb.replaceAll(' ', ',')}) 0%, rgba(0,0,0,0) 45%), radial-gradient(1200px circle at 90% 90%, rgb(${loginToRgb.replaceAll(' ', ',')}) 0%, rgba(0,0,0,0) 45%), linear-gradient(135deg, rgb(${loginFromRgb.replaceAll(' ', ',')}) 0%, rgb(${loginToRgb.replaceAll(' ', ',')}) 100%);`">
                                <div class="text-xs font-semibold text-white/90">Vista previa</div>
                                <div class="mt-1 text-xs text-white/70">Usado en la pantalla de acceso.</div>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab==='assets'" class="space-y-6" x-cloak>
                        @php($logoNow = file_url($branding->logoUrl))
                        @php($loginLogoNow = file_url($branding->loginLogoUrl))
                        @php($faviconNow = file_url($branding->faviconUrl))

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-semibold text-slate-700">Logo (header/sidebar)</div>
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-primary focus:ring-primary" />
                                        Quitar
                                    </label>
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                </div>
                                @error('logo_file') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
                                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs text-slate-500">Actual</div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-2xl bg-white ring-1 ring-inset ring-slate-200 overflow-hidden flex items-center justify-center">
                                            @if ($logoNow)
                                                <img src="{{ $logoNow }}" alt="Logo" class="h-full w-full object-contain" />
                                            @else
                                                <div class="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold">GC</div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-slate-900 truncate">{{ $branding->sidebarName ?? $branding->systemName }}</div>
                                            <div class="text-xs text-slate-500">Miniatura del logo actual</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-semibold text-slate-700">Logo login</div>
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="remove_login_logo" value="1" class="rounded border-slate-300 text-primary focus:ring-primary" />
                                        Quitar
                                    </label>
                                </div>
                                <div class="mt-3">
                                    <input type="file" name="login_logo_file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                </div>
                                @error('login_logo_file') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
                                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs text-slate-500">Actual</div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-2xl bg-white ring-1 ring-inset ring-slate-200 overflow-hidden flex items-center justify-center">
                                            @if ($loginLogoNow)
                                                <img src="{{ $loginLogoNow }}" alt="Logo login" class="h-full w-full object-contain" />
                                            @else
                                                <div class="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold">GC</div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-slate-900 truncate">Login</div>
                                            <div class="text-xs text-slate-500">Miniatura del logo de login</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-slate-500">Recomendado: PNG/WebP con fondo transparente.</div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-semibold text-slate-700">Favicon</div>
                                <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" name="remove_favicon" value="1" class="rounded border-slate-300 text-primary focus:ring-primary" />
                                    Quitar
                                </label>
                            </div>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <input type="file" name="favicon_file" accept="image/png,image/webp,image/jpeg" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs text-slate-500">Actual</div>
                                            <div class="mt-1 text-sm text-slate-700">Ideal: PNG/WebP 64×64 o 128×128.</div>
                                        </div>
                                        <div class="h-12 w-12 rounded-2xl bg-white ring-1 ring-inset ring-slate-200 overflow-hidden flex items-center justify-center">
                                            @if ($faviconNow)
                                                <img src="{{ $faviconNow }}" alt="Favicon" class="h-8 w-8 object-contain" />
                                            @else
                                                <div class="text-xs font-semibold text-slate-500">—</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('favicon_file') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button class="inline-flex items-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90">
                            Guardar cambios
                        </button>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="text-sm font-semibold text-slate-900">Vista previa rápida</div>
                        <div class="mt-3 space-y-3">
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="text-xs text-slate-500">Primario</div>
                                <div class="mt-2 h-10 rounded-xl bg-primary"></div>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="text-xs text-slate-500">Secundario</div>
                                <div class="mt-2 h-10 rounded-xl bg-secondary"></div>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="text-xs text-slate-500">Tipografía</div>
                                <div class="mt-2 text-sm" style="font-family: var(--gc-font), ui-sans-serif, system-ui;">
                                    The quick brown fox jumps over the lazy dog.
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="text-xs text-slate-500">Login</div>
                                <div class="mt-2 h-16 rounded-xl"
                                     :style="`background: linear-gradient(135deg, rgb(${loginFromRgb.replaceAll(' ', ',')}) 0%, rgb(${loginToRgb.replaceAll(' ', ',')}) 100%);`"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="text-sm font-semibold text-slate-900">Notas</div>
                        <ul class="mt-2 space-y-1 text-sm text-slate-600">
                            <li>• Los cambios afectan al tenant actual.</li>
                            <li>• Logos/Favicon se guardan en <code class="rounded bg-slate-100 px-1">GC_UPLOAD_ROOT/settings</code>.</li>
                            <li>• En modo subdominio, cada subdominio puede tener su propio branding.</li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

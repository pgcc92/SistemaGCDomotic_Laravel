<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Perfil</div>
            <div class="text-sm text-slate-500">Actualiza tus datos, contraseña y seguridad.</div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="gc-card p-5">
                    <div class="flex items-center gap-3">
                        @php($initial = mb_strtoupper(mb_substr((string) Auth::user()->name, 0, 1)))
                        <div class="h-12 w-12 rounded-2xl bg-primary/10 text-primary font-bold flex items-center justify-center ring-1 ring-inset ring-primary/10">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900 truncate">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <div class="flex items-center justify-between">
                            <span>Sesión</span>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Activa</span>
                        </div>
                        <div class="text-xs text-slate-500">Los cambios se aplican inmediatamente.</div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-8 space-y-6">
                <div class="gc-card p-5">
                    @include('profile.partials.update-profile-information-form')
                </div>
                <div class="gc-card p-5">
                    @include('profile.partials.update-password-form')
                </div>
                <div class="gc-card p-5">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

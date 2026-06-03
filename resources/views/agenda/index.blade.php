@php
    $perms = app(\App\Infrastructure\Remote\RemoteRbacClient::class)->myPermissions();
    $isAdmin = (bool) (($perms['*']['*'] ?? false) === true);
    $canViewAllAgenda = $isAdmin || (bool) ($perms['agenda']['ver_general'] ?? false);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Agenda</div>
            <div class="text-sm text-slate-500">Programación de instalaciones y postventa.</div>
        </div>
    </x-slot>

	    <div
	        x-data="agendaPage({
	            urls: {
	                data: '{{ route('agenda.data') }}',
	                show: (id) => `/agenda/${id}`,
	                create: '{{ route('agenda.store') }}',
	                update: (id) => `/agenda/${id}/editar`,
	                complete: (id) => `/agenda/${id}/completar`,
	                destroy: (id) => `/agenda/${id}/eliminar`,
	                clientes: '{{ route('clientes.data') }}',
                    ventasByCliente: (id) => `/clientes/${id}?only=ventas`,
                    ventas: '{{ route('ventas.data') }}',
	                tecnicos: '{{ route('agenda.tecnicos-data') }}',
                    tickets: '{{ route('tickets.data') }}',
                    productos: '{{ route('productos.data') }}',
	            },
	            currentUid: {{ (int) (auth()->user()->remote_usuario_id ?? 0) }},
	            isAdmin: {{ $isAdmin ? 1 : 0 }},
                canViewAllAgenda: {{ $canViewAllAgenda ? 1 : 0 }},
	        })"
	        x-init="init()"
	        class="space-y-6"
	    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="gc-card p-5 bg-gradient-to-br from-primary/10 to-white">
                <div class="text-xs font-semibold text-primary/80">Pendientes</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.pendientes"></div>
                <div class="mt-1 text-xs text-slate-500">Por realizar.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                <div class="text-xs font-semibold text-sky-700/80">Programadas</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.programadas"></div>
                <div class="mt-1 text-xs text-slate-500">Con fecha definida.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="text-xs font-semibold text-emerald-700/80">Realizadas</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.realizadas"></div>
                <div class="mt-1 text-xs text-slate-500">Completadas.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="text-xs font-semibold text-amber-700/80">Hoy</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.hoy"></div>
                <div class="mt-1 text-xs text-slate-500">Actividades del día.</div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="page=0"
                       placeholder="Buscar por cliente, ticket, venta, título…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <select x-model="viewMode" class="sm:hidden rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <option value="calendar">Calendario</option>
                    <option value="list">Lista</option>
                </select>
                <div class="hidden sm:flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">
                    <button type="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-semibold"
                            :class="viewMode === 'calendar' ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50'"
                            @click="viewMode='calendar'">
                        Calendario
                    </button>
                    <button type="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-semibold"
                            :class="viewMode === 'list' ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50'"
                            @click="viewMode='list'">
                        Lista
                    </button>
                </div>
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                        @click="filterEstado=''">Todos</button>
                <select x-model="filterEstado" class="rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <option value="">Estado (todos)</option>
                    <option value="PENDIENTE">PENDIENTE</option>
                    <option value="PROGRAMADA">PROGRAMADA</option>
                    <option value="REALIZADA">REALIZADA</option>
                    <option value="CANCELADA">CANCELADA</option>
                </select>
                <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        @click="openNewForDate()">
                    Nuevo
                </button>
            </div>
        </div>

        <!-- Calendario mensual -->
        <div x-show="viewMode==='calendar'" class="gc-card p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                            @click="prevMonth()">
                        ←
                    </button>
                    <div class="text-sm font-semibold text-slate-900" x-text="monthLabel"></div>
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                            @click="nextMonth()">
                        →
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Mes</label>
                    <input type="month" x-model="month"
                           class="rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                            @click="month=todayMonth()">
                        Hoy
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <!-- Mobile: lista por día (más legible/táctil) -->
                <div class="sm:hidden space-y-3">
                    <template x-for="day in monthDaysWithItems" :key="day.iso">
                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 bg-slate-50">
                                <div class="text-sm font-semibold text-slate-900" x-text="day.label"></div>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                      x-text="day.items.length"></span>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <template x-for="it in day.items" :key="it.id">
                                    <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                            @click="openDetail(it.id)">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold text-slate-700">
                                                <span x-text="fmtTime(it.fecha_programada) || '—'"></span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-semibold text-slate-900" x-text="it.titulo || `Agenda #${it.id}`"></div>
                                                <div class="mt-0.5 truncate text-xs text-slate-500" x-text="it.cliente_wa || it.cliente_id || (it.ticket_id ? `Ticket ${it.ticket_id}` : (it.venta_id ? `Venta #${it.venta_id}` : '—'))"></div>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                                          :class="estadoClass(it.estado)"
                                                          x-text="String(it.estado||'').toUpperCase() || '—'"></span>
                                                    <span x-show="it.prioridad" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                                          :class="prioClass(it.prioridad)"
                                                          x-text="String(it.prioridad||'').toUpperCase()"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="monthDaysWithItems.length===0" class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
                        No hay actividades para este mes.
                    </div>
                </div>

                <!-- sm+: cuadrícula tipo Apple/Google calendar -->
                <div class="hidden sm:block">
                    <div class="rounded-2xl border border-slate-200 bg-slate-200/70 overflow-hidden">
                        <div class="grid grid-cols-7 gap-px bg-slate-200/70 text-[11px] font-semibold text-slate-600">
                            <div class="bg-slate-50 px-3 py-2">Lun</div>
                            <div class="bg-slate-50 px-3 py-2">Mar</div>
                            <div class="bg-slate-50 px-3 py-2">Mié</div>
                            <div class="bg-slate-50 px-3 py-2">Jue</div>
                            <div class="bg-slate-50 px-3 py-2">Vie</div>
                            <div class="bg-slate-50 px-3 py-2">Sáb</div>
                            <div class="bg-slate-50 px-3 py-2">Dom</div>
                        </div>

                        <div class="grid grid-cols-7 gap-px bg-slate-200/70">
                            <template x-for="cell in calendarCells" :key="cell.key">
                                <div class="bg-white p-2.5 h-28 md:h-32 lg:h-40"
                                     :class="cell.inMonth ? '' : 'bg-slate-50/70'">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="inline-flex items-center justify-center h-7 w-7 rounded-full text-xs font-semibold"
                                             :class="cell.isToday ? 'bg-primary text-white shadow-sm' : (cell.inMonth ? 'text-slate-700' : 'text-slate-500')"
                                             x-text="cell.day"></div>
                                        <span x-show="cell.count>0"
                                              class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                              x-text="cell.count"></span>
                                    </div>

                                    <div class="mt-2 space-y-1 overflow-hidden">
                                        <template x-for="it in cell.itemsPreview" :key="it.id">
                                            <button type="button"
                                                    class="w-full rounded-lg px-2 py-1 text-left text-[12px] leading-4 hover:bg-slate-50"
                                                    :class="eventPillClass(it)"
                                                    @click="openDetail(it.id)">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="shrink-0 text-[11px] font-semibold opacity-80" x-text="fmtTime(it.fecha_programada)"></span>
                                                    <span class="truncate font-semibold" x-text="it.titulo || `Agenda #${it.id}`"></span>
                                                </div>
                                            </button>
                                        </template>

                                        <button x-show="cell.moreCount>0"
                                                type="button"
                                                class="mt-0.5 w-full rounded-lg px-2 py-1 text-left text-[12px] font-semibold text-primary hover:bg-primary/5"
                                                @click="q=''; filterEstado=''; viewMode='list'; jumpToDate(cell.isoDate)">
                                            + <span x-text="cell.moreCount"></span> más
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista -->
        <div x-show="viewMode==='list'" class="gc-card p-5">
            <x-table class="border-0 shadow-none">
                <thead class="bg-slate-50/60">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Prioridad</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pagedRows" :key="row.id">
                        <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer" @click="openDetail(row.id)">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900" x-text="fmtDate(row.fecha_programada)"></div>
                                <div class="text-xs text-slate-500" x-text="(row.duracion_min ? `${row.duracion_min} min` : '')"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900" x-text="row.cliente_wa || row.cliente_id || '—'"></div>
                                <div class="text-xs text-slate-500" x-text="row.titulo || (row.ticket_id ? `Ticket ${row.ticket_id}` : (row.venta_id ? `Venta #${row.venta_id}` : '—'))"></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="row.tipo || '—'"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                      :class="estadoClass(row.estado)"
                                      x-text="row.estado || '—'"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                      :class="prioClass(row.prioridad)"
                                      x-text="row.prioridad || '—'"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filteredRows.length === 0">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="5">No hay actividades.</td>
                    </tr>
                </tbody>
            </x-table>
        </div>

        <div x-show="viewMode==='list'">
            <x-pagination page="page" pages="pages"></x-pagination>
        </div>

        <x-modal name="agenda-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 bg-white/80 backdrop-blur">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="text-base font-semibold text-slate-900 truncate" x-text="detail?.titulo || `Agenda #${detail?.id || ''}`"></div>
                        <div class="mt-0.5 text-xs text-slate-500" x-text="fmtDate(detail?.fecha_programada)"></div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                  x-text="detail?.tipo || '—'"></span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="estadoClass(detail?.estado)"
                                  x-text="detail?.estado || '—'"></span>
                            <span x-show="detail?.prioridad" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="prioClass(detail?.prioridad)"
                                  x-text="detail?.prioridad || ''"></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                                x-show="canCompleteDetail()"
                                @click="openComplete(detail); $dispatch('open-modal','agenda-completar')">
                            Confirmar / evidencia
                        </button>
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                @click="fillForm(detail); $dispatch('open-modal','agenda-form')">Editar</button>
                        <x-icon-button @click="$dispatch('close-modal','agenda-detalle')" aria-label="Cerrar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </x-icon-button>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-4">
                <template x-if="detailError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="detailError"></div>
                </template>
                <div class="grid gap-4 lg:grid-cols-12" x-show="detail">
                    <!-- Columna izquierda: Cliente + referencias -->
                    <div class="lg:col-span-4 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Cliente</div>
                            <div class="mt-3 flex items-start gap-3">
                                <div class="h-10 w-10 shrink-0 rounded-2xl bg-primary/10 text-primary ring-1 ring-inset ring-primary/15 flex items-center justify-center text-sm font-bold">
                                    <span x-text="(detailCliente?.nombre || detailCliente?.razon_social || detail?.cliente_wa || 'C').toString().trim().slice(0,1).toUpperCase()"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900" x-text="detailCliente?.nombre || detailCliente?.razon_social || '—'"></div>
                                    <div class="mt-0.5 text-xs text-slate-500" x-text="detailCliente?.telefono || detail?.cliente_wa || '—'"></div>
                                    <div x-show="detailCliente?.razon_social && detailCliente?.nombre" class="mt-2 text-xs text-slate-500">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Razón social</span>
                                        <span class="ml-2" x-text="detailCliente?.razon_social"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Referencias</div>
                            <div class="mt-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Ticket</span>
                                    <span class="font-medium text-slate-900" x-text="detail?.ticket_id || '—'"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Venta</span>
                                    <span class="font-medium text-slate-900" x-text="detailVenta?.venta_codigo || (detail?.venta_id || '—')"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Técnico</span>
                                    <span class="font-medium text-slate-900" x-text="tecnicoLabel(detail?.tecnico_id)"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3" x-show="detail?.evidencia_dispositivo_id">
                                    <span class="text-slate-500">Evidencia</span>
                                    <span class="font-medium text-slate-900" x-text="`#${detail?.evidencia_dispositivo_id}`"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3" x-show="detail?.terminado_at">
                                    <span class="text-slate-500">Terminado</span>
                                    <span class="font-medium text-slate-900" x-text="fmtDate(detail?.terminado_at)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Título / Descripción / Notas -->
                    <div class="lg:col-span-8 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-900">Detalle</div>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                      x-text="detail?.duracion_min ? `${detail.duracion_min} min` : '—'"></span>
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200">
                                    <div class="text-xs font-semibold text-slate-600">Título</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="detail?.titulo || '—'"></div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200">
                                    <div class="text-xs font-semibold text-slate-600">Programación</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="fmtDate(detail?.fecha_programada)"></div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="text-xs font-semibold text-slate-600">Descripción</div>
                                <div class="mt-1 whitespace-pre-wrap text-sm text-slate-800" x-text="detail?.descripcion || '—'"></div>
                            </div>

                            <div class="mt-4">
                                <div class="text-xs font-semibold text-slate-600">Notas</div>
                                <div class="mt-1 whitespace-pre-wrap text-sm text-slate-800" x-text="stripInstallMeta(detail?.notas || '') || '—'"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>

        <x-modal name="agenda-form" maxWidth="4xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submit()">
                <div class="px-6 py-4 flex items-start justify-between gap-3 bg-white/80 backdrop-blur">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="form.id ? 'Editar agenda' : 'Nueva agenda'"></div>
                        <div class="mt-0.5 text-xs text-slate-500">Instalaciones y postventa.</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','agenda-form')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <template x-if="formError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="formError"></div>
                    </template>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Tipo</label>
                            <select x-model="form.tipo" @change="syncTipoFields()" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="VENTA">VENTA</option>
                                <option value="POSTVENTA">POSTVENTA</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Estado</label>
                            <select x-model="form.estado" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="PENDIENTE">PENDIENTE</option>
                                <option value="PROGRAMADA">PROGRAMADA</option>
                                <option value="REALIZADA">REALIZADA</option>
                                <option value="CANCELADA">CANCELADA</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Fecha programada</label>
                            <input x-model="form.fecha_programada" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Duración (min)</label>
                            <input x-model="form.duracion_min" type="number" min="5" max="1440" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Cliente (WhatsApp)</label>
                            <div class="relative mt-1">
                                <input x-model.debounce.200ms="form.cliente_wa"
                                       class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                       placeholder="Buscar por teléfono o nombre…" />
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400" x-show="cliLoading" x-cloak>
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                    </svg>
                                </div>
                                <div x-show="cliOpen" x-cloak class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-64 overflow-auto">
                                        <template x-for="c in cliResults" :key="c.id">
                                            <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                                    @click="pickCliente(c)">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-900 truncate" x-text="c.telefono"></div>
                                                        <div class="text-xs text-slate-500 truncate" x-text="c.nombre || c.razon_social || '—'"></div>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">cliente</span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="cliResults.length===0" class="px-4 py-6 text-sm text-slate-500">Sin resultados.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <template x-if="String(form.tipo||'').toUpperCase()==='POSTVENTA'">
                            <div>
                                <label class="text-xs font-medium text-slate-700">Ticket ID</label>
                                <div class="relative mt-1">
                                    <input x-model.debounce.250ms="form.ticket_id"
                                           class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                           placeholder="Buscar por ticket o teléfono…" />
                                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400" x-show="tckLoading" x-cloak>
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                        </svg>
                                    </div>
                                    <div x-show="tckOpen" x-cloak class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-64 overflow-auto">
                                            <template x-for="t in tckResults" :key="t.ticket_id || t.id">
                                                <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                                        @click="pickTicket(t)">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="text-sm font-semibold text-slate-900 truncate" x-text="t.ticket_id || t.id || '—'"></div>
                                                            <div class="text-xs text-slate-500 truncate" x-text="t.cliente_wa || t.telefono || t.cliente_id || '—'"></div>
                                                        </div>
                                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="t.estado || 'ticket'"></span>
                                                    </div>
                                                </button>
                                            </template>
                                            <div x-show="tckResults.length===0" class="px-4 py-6 text-sm text-slate-500">Sin resultados.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="String(form.tipo||'').toUpperCase()==='VENTA'">
                            <div>
                                <label class="text-xs font-medium text-slate-700">Venta ID</label>
                                <template x-if="ventasCliente.length > 0">
                                    <select x-model="form.venta_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                        <option value="">—</option>
                                        <template x-for="v in ventasCliente" :key="v.id">
                                            <option :value="v.id" x-text="`${v.venta_codigo || ('#'+v.id)} • ${fmtDate(v.fecha_venta || v.created_at || '')}`"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="ventasCliente.length === 0">
                                    <div class="relative mt-1">
                                        <input x-model.debounce.300ms="ventaQuery"
                                               class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                               placeholder="Buscar por código o ID de venta…" />
                                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400" x-show="ventaLoading" x-cloak>
                                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                            </svg>
                                        </div>
                                        <div x-show="ventaOpen" x-cloak class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                                            <div class="max-h-64 overflow-auto">
                                                <template x-for="v in ventaResults" :key="v.id">
                                                    <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                                            @click="pickVenta(v)">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <div class="text-sm font-semibold text-slate-900 truncate" x-text="v.venta_codigo || ('#'+v.id)"></div>
                                                                <div class="text-xs text-slate-500 truncate" x-text="fmtDate(v.fecha_venta || v.created_at || '')"></div>
                                                            </div>
                                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="v.estado || 'venta'"></span>
                                                        </div>
                                                    </button>
                                                </template>
                                                <div x-show="ventaResults.length===0" class="px-4 py-6 text-sm text-slate-500">Sin resultados.</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-500" x-show="form.venta_id">
                                        Seleccionado: <span class="font-medium text-slate-700" x-text="form.venta_id"></span>
                                    </div>
                                </template>
                                <div class="mt-1 text-[11px] text-slate-500" x-show="selectedCliente">
                                    Cliente: <span class="font-medium text-slate-700" x-text="selectedCliente?.telefono || selectedCliente?.nombre || ''"></span>
                                </div>
                            </div>
                        </template>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Título</label>
                            <input x-model="form.titulo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Descripción</label>
                            <textarea x-model="form.descripcion" rows="3" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Modelo (cerradura)</label>
                            <div class="relative mt-1">
                                <input x-model.debounce.250ms="form.modelo_cerradura"
                                       class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                       placeholder="Buscar por SKU, nombre o modelo…" />
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400" x-show="mdlLoading" x-cloak>
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                    </svg>
                                </div>
                                <div x-show="mdlOpen" x-cloak class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-64 overflow-auto">
                                        <template x-for="p in mdlResults" :key="p.id">
                                            <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                                    @click="pickModelo(p)">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-900 truncate" x-text="p.modelo || p.sku || '—'"></div>
                                                        <div class="text-xs text-slate-500 truncate" x-text="p.nombre || '—'"></div>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="p.sku || 'producto'"></span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="mdlResults.length===0" class="px-4 py-6 text-sm text-slate-500">Sin resultados.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-1 text-[11px] text-slate-500">Se reutiliza al completar como instalación.</div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Serial (opcional)</label>
                            <input x-model="form.serial_cerradura" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                   placeholder="Ej: SN-001" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Dirección (instalación)</label>
                            <input x-model="form.direccion" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                   placeholder="Ej: Av. ..." />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Prioridad</label>
                            <select x-model="form.prioridad" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="">—</option>
                                <option value="BAJA">BAJA</option>
                                <option value="MEDIA">MEDIA</option>
                                <option value="ALTA">ALTA</option>
                                <option value="URGENTE">URGENTE</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Técnico</label>
                            <select x-model="form.tecnico_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="">—</option>
                                <template x-for="u in tecnicos" :key="u.id">
                                    <option :value="u.id" x-text="u.nombre || u.name || ('Usuario #' + u.id)"></option>
                                </template>
                            </select>
                            <div class="mt-1 text-[11px] text-slate-500">Se carga desde Usuarios.</div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Notas</label>
                            <textarea x-model="form.notas" rows="2" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-between gap-2 bg-white/80 backdrop-blur">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50"
                            @click="$dispatch('close-modal','agenda-form')">Cancelar</button>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-60"
                                :disabled="!form.id || saving"
                                @click="destroy()">
                            Eliminar
                        </button>
                        <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
                                :disabled="saving"
                                x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                    </div>
                </div>
            </form>
        </x-modal>

        <!-- Completar / evidencia -->
        <x-modal name="agenda-completar" maxWidth="3xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitComplete()">
                <div class="px-6 py-4 flex items-start justify-between gap-3 bg-white/80 backdrop-blur">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Confirmar servicio</div>
                        <div class="mt-0.5 text-xs text-slate-500">Registra hora de término y evidencia.</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','agenda-completar')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <template x-if="completeError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="completeError"></div>
                    </template>

                    <div class="grid gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-5 space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="text-sm font-semibold text-slate-900">Cliente</div>
                                <div class="mt-3 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900" x-text="detailCliente?.nombre || detailCliente?.razon_social || '—'"></div>
                                    <div class="mt-0.5 text-xs text-slate-500" x-text="detailCliente?.telefono || detail?.cliente_wa || '—'"></div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="text-sm font-semibold text-slate-900">Referencia</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                                        <span class="text-slate-500">Venta:</span>
                                        <span class="ml-2" x-text="detailVenta?.venta_codigo || (detail?.venta_id ? ('#' + detail?.venta_id) : '—')"></span>
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                                        <span class="text-slate-500">Técnico:</span>
                                        <span class="ml-2" x-text="tecnicoLabel(detail?.tecnico_id)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-7">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <div class="text-sm font-semibold text-slate-900">Evidencia</div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-medium text-slate-700">Hora de término</label>
                                        <input x-model="complete.terminado_at" type="datetime-local" required
                                               class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-700">Fotos (evidencia)</label>
                                        <input x-ref="completeFoto" type="file" accept="image/jpeg,image/png,image/webp" multiple
                                               class="mt-1 block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800" />
                                        <div class="mt-1 text-[11px] text-slate-500">Puedes subir hasta 5 imágenes. En móvil puedes elegir cámara o galería.</div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-700">GPS (lat)</label>
                                        <input x-model="complete.gps_lat" inputmode="decimal" placeholder="—"
                                               class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-700">GPS (lng)</label>
                                        <input x-model="complete.gps_lng" inputmode="decimal" placeholder="—"
                                               class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <div class="text-[11px] text-slate-500">Opcional: usar ubicación del dispositivo.</div>
                                    <button type="button"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50 disabled:opacity-60"
                                            :disabled="completeSaving"
                                            @click="fillMyLocation()">
                                        Usar mi ubicación
                                    </button>
                                </div>
                                <div class="mt-4">
                                    <label class="text-xs font-medium text-slate-700">Notas</label>
                                    <textarea x-model="complete.notas" rows="3"
                                              class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                              placeholder="Detalle, observaciones, etc."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 flex items-center justify-between gap-2 bg-white/80 backdrop-blur">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50"
                            @click="$dispatch('close-modal','agenda-completar')">Cancelar</button>
                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60 inline-flex items-center gap-2"
                            :disabled="completeSaving">
                        <svg x-show="completeSaving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="completeSaving ? 'Guardando…' : 'Confirmar'"></span>
                    </button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function agendaPage({ urls, currentUid, isAdmin, canViewAllAgenda }) {
            return {
                urls,
                currentUid: parseInt(currentUid || 0, 10) || 0,
                isAdmin: !!isAdmin,
                canViewAllAgenda: !!canViewAllAgenda,
                rows: [],
                loading: false,
                q: '',
                filterEstado: '',
                viewMode: 'calendar',
                month: '',
                page: 0,
                perPage: 25,
                detail: null,
                detailError: '',
                form: {
                    id: null,
                    tipo: 'VENTA',
                    estado: 'PENDIENTE',
                    fecha_programada: '',
                    duracion_min: 60,
                    cliente_wa: '',
                    ticket_id: '',
                    venta_id: '',
                    titulo: '',
                    descripcion: '',
                    modelo_cerradura: '',
                    serial_cerradura: '',
                    direccion: '',
                    prioridad: '',
                    tecnico_id: '',
                    notas: '',
                },
                formError: '',
                saving: false,
                cliOpen: false,
                cliResults: [],
                cliCache: [],
                cliCacheAt: 0,
                cliLoading: false,
                cliAbort: null,
                tecnicos: [],
                selectedCliente: null,
                ventasCliente: [],
                selectedModelo: null,
                tckOpen: false,
                tckResults: [],
                tckLoading: false,
                tckAbort: null,
                ventaQuery: '',
                ventaOpen: false,
                ventaResults: [],
                ventaLoading: false,
                ventaAbort: null,
                mdlOpen: false,
                mdlResults: [],
                mdlLoading: false,
                mdlAbort: null,
                lastAutoTitulo: '',
                detailCliente: null,
                detailVenta: null,
                complete: {
                    terminado_at: '',
                    notas: '',
                    gps_lat: '',
                    gps_lng: '',
                },
                completeError: '',
                completeSaving: false,
                suppressClienteWatch: false,

                async init() {
                    this.month = this.todayMonth();
                    this.resetForm();
                    this.resetComplete();
                    await this.loadTecnicos();
                    this.$watch('month', () => this.reload());
                    this.$watch('filterEstado', () => this.reload());
                    this.$watch('q', () => this.reload());
                    this.bindWatchers();
                    await this.reload();
                },

                bindWatchers() {
                    // Cliente (WA) typeahead
                    this.$watch('form.cliente_wa', (v) => {
                        if (this.suppressClienteWatch) return;
                        const query = String(v || '').trim();
                        if (query.length < 2) {
                            this.cliOpen = false;
                            this.cliResults = [];
                            this.cliLoading = false;
                            this.selectedCliente = null;
                            this.ventasCliente = [];
                            this.abortCli();
                            if (query === '') {
                                this.form.venta_id = '';
                            }
                            return;
                        }
                        if (this.selectedCliente && query === String(this.selectedCliente.telefono || '').trim()) {
                            this.cliOpen = false;
                            return;
                        }
                        this.searchClientes(query);
                    });

                    // Ticket typeahead (POSTVENTA)
                    this.$watch('form.ticket_id', (v) => {
                        if (String(this.form.tipo || '').toUpperCase() !== 'POSTVENTA') return;
                        const query = String(v || '').trim();
                        if (query.length < 3) {
                            this.tckOpen = false;
                            this.tckResults = [];
                            this.tckLoading = false;
                            this.abortTck();
                            return;
                        }
                        this.searchTickets(query);
                    });

                    // Modelo typeahead
                    this.$watch('form.modelo_cerradura', (v) => {
                        const query = String(v || '').trim();
                        if (query.length < 2) {
                            this.mdlOpen = false;
                            this.mdlResults = [];
                            this.mdlLoading = false;
                            this.abortMdl();
                            return;
                        }
                        if (this.selectedModelo && query === String(this.selectedModelo.modelo || '').trim()) {
                            this.mdlOpen = false;
                            return;
                        }
                        this.searchModelos(query);
                    });

                    // Autocomplete por venta seleccionada
                    this.$watch('form.venta_id', (v) => {
                        if (String(this.form.tipo || '').toUpperCase() !== 'VENTA') return;
                        const id = parseInt(String(v || ''), 10);
                        if (!id) return;
                        this.autofillByVenta(id);
                    });

                    this.$watch('ventaQuery', (v) => {
                        const query = String(v || '').trim();
                        if (query.length < 2) {
                            this.ventaOpen = false;
                            this.ventaResults = [];
                            this.ventaLoading = false;
                            this.abortVenta();
                            return;
                        }
                        this.searchVentas(query);
                    });

                    // Al cambiar tipo, limpiar campos que no correspondan
                    this.$watch('form.tipo', () => this.syncTipoFields());
                },

                abortCli() { try { this.cliAbort?.abort?.(); } catch {} this.cliAbort = null; },
                abortTck() { try { this.tckAbort?.abort?.(); } catch {} this.tckAbort = null; },
                abortVenta() { try { this.ventaAbort?.abort?.(); } catch {} this.ventaAbort = null; },
                abortMdl() { try { this.mdlAbort?.abort?.(); } catch {} this.mdlAbort = null; },

                monthRange() {
                    const m = String(this.month || this.todayMonth());
                    const [yy, mm] = m.split('-').map(x => parseInt(x, 10));
                    if (!yy || !mm) return { from: null, to: null };
                    const pad = (n) => String(n).padStart(2, '0');
                    const lastDay = new Date(yy, mm, 0).getDate(); // 0 => último día del mes anterior (mm ya es 1-based)
                    return {
                        from: `${yy}-${pad(mm)}-01 00:00:00`,
                        to: `${yy}-${pad(mm)}-${pad(lastDay)} 23:59:59`,
                    };
                },

                async reload() {
                    this.loading = true;
                    try {
                        const { from, to } = this.monthRange();
                        const params = {
                            limit: 200,
                            from: from || undefined,
                            to: to || undefined,
                            estado: this.filterEstado || undefined,
                            q: (this.q || '').trim() || undefined,
                            all: this.canViewAllAgenda ? 1 : undefined,
                        };
                        const res = await window.axios.get(this.urls.data, { headers: { 'Accept': 'application/json' }, params });
                        this.rows = res.data?.data || [];
                        this.page = 0;
                    } catch (e) {
                        this.rows = [];
                        const msg = e?.response?.data?.error || e?.message || 'No se pudo cargar la agenda.';
                        window.GCToast?.error?.('Agenda', msg);
                    } finally {
                        this.loading = false;
                    }
                },

                get filteredRows() {
                    // La búsqueda y filtros principales ya se aplican server-side en /agenda/data.
                    return this.rows || [];
                },

                get monthLabel() {
                    const m = String(this.month || '');
                    if (!m) return '—';
                    const [y, mm] = m.split('-').map(x => parseInt(x, 10));
                    if (!y || !mm) return m;
                    const d = new Date(y, mm - 1, 1);
                    return d.toLocaleDateString('es-PE', { month: 'long', year: 'numeric' }).replace(/^\w/, c => c.toUpperCase());
                },

                todayMonth() {
                    const d = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
                },

	                async loadTecnicos() {
	                    try {
	                        const res = await window.axios.get(this.urls.tecnicos, {
	                            headers: { 'Accept': 'application/json' },
	                            params: { limit: 200, dashboard_activo: 1, role_codes: 'tecnico,instalador' },
	                        });
	                        const rows = res.data?.data || [];
	                        this.tecnicos = rows.map(r => ({
	                            id: r.id,
                            nombre: r.nombre || r.name || '',
                            name: r.name || r.nombre || '',
                        }));
                    } catch {
                        this.tecnicos = [];
                    }
                },

                async searchClientes(q) {
                    const query = String(q || '').trim();
                    if (query.length < 2) { this.cliOpen = false; this.cliResults = []; return; }
                    this.abortCli();
                    const ac = new AbortController();
                    this.cliAbort = ac;
                    this.cliLoading = true;
                    try {
                        const res = await window.axios.get(this.urls.clientes, {
                            headers: { 'Accept': 'application/json' },
                            params: { q: query, limit: 20 },
                            signal: ac.signal,
                        });
                        if (String(this.form.cliente_wa || '').trim() !== query) return;
                        const rows = res.data?.data || [];
                        this.cliResults = (Array.isArray(rows) ? rows : []).slice(0, 12);
                        this.cliOpen = true;
                    } catch {
                        if (ac.signal.aborted) return;
                        this.cliOpen = false;
                        window.GCToast?.error?.('Clientes', 'No se pudo buscar clientes (verifica permisos o conexión).');
                    } finally {
                        if (!ac.signal.aborted) this.cliLoading = false;
                    }
                },

                pickCliente(c) {
                    this.suppressClienteWatch = true;
                    this.form.cliente_wa = String(c.telefono || '');
                    this.cliOpen = false;
                    this.selectedCliente = c || null;
                    this.$nextTick(() => { this.suppressClienteWatch = false; });
                    this.loadVentasCliente();
                },

                async loadVentasCliente() {
                    this.ventasCliente = [];
                    const id = this.selectedCliente?.id;
                    if (!id) return;
                    try {
                        const res = await window.axios.get(this.urls.ventasByCliente(id), { headers: { 'Accept': 'application/json' } });
                        const ventas = res.data?.data?.ventas || res.data?.data || [];
                        const rows = Array.isArray(ventas) ? ventas : [];
                        rows.sort((a, b) => String(b.fecha_venta || b.created_at || '').localeCompare(String(a.fecha_venta || a.created_at || '')));
                        this.ventasCliente = rows.slice(0, 10);
                        if (this.ventasCliente.length > 0 && !this.form.venta_id) {
                            this.form.venta_id = String(this.ventasCliente[0].id || '');
                        }
                    } catch {
                        // Silencioso: si no hay permisos, igual permite ingresar ID manual.
                    }
                },

                async searchTickets(q) {
                    const query = String(q || '').trim();
                    if (query.length < 3) return;
                    this.abortTck();
                    const ac = new AbortController();
                    this.tckAbort = ac;
                    this.tckLoading = true;
                    try {
                        const res = await window.axios.get(this.urls.tickets, {
                            headers: { 'Accept': 'application/json' },
                            params: { q: query, limit: 20 },
                            signal: ac.signal,
                        });
                        if (String(this.form.ticket_id || '').trim() !== query) return;
                        const rows = res.data?.data || [];
                        this.tckResults = (Array.isArray(rows) ? rows : []).slice(0, 12);
                        this.tckOpen = true;
                    } catch {
                        if (ac.signal.aborted) return;
                        this.tckOpen = false;
                        window.GCToast?.error?.('Tickets', 'No se pudo buscar tickets.');
                    } finally {
                        if (!ac.signal.aborted) this.tckLoading = false;
                    }
                },

                pickTicket(t) {
                    const id = String(t?.ticket_id || t?.id || '').trim();
                    this.form.ticket_id = id;
                    this.tckOpen = false;
                    const wa = String(t?.cliente_wa || t?.telefono || '').trim();
                    if (wa && !String(this.form.cliente_wa || '').trim()) {
                        this.form.cliente_wa = wa;
                    }
                },

                async searchVentas(q) {
                    const query = String(q || '').trim();
                    if (query.length < 2) return;
                    this.abortVenta();
                    const ac = new AbortController();
                    this.ventaAbort = ac;
                    this.ventaLoading = true;
                    try {
                        const res = await window.axios.get(this.urls.ventas, {
                            headers: { 'Accept': 'application/json' },
                            params: { q: query, limit: 20 },
                            signal: ac.signal,
                        });
                        if (String(this.ventaQuery || '').trim() !== query) return;
                        const rows = res.data?.data || [];
                        this.ventaResults = (Array.isArray(rows) ? rows : []).slice(0, 12);
                        this.ventaOpen = true;
                    } catch {
                        if (ac.signal.aborted) return;
                        this.ventaOpen = false;
                        window.GCToast?.error?.('Ventas', 'No se pudo buscar ventas.');
                    } finally {
                        if (!ac.signal.aborted) this.ventaLoading = false;
                    }
                },

                pickVenta(v) {
                    this.form.venta_id = String(v?.id || '');
                    this.ventaQuery = v?.venta_codigo || (v?.id ? String(v.id) : '');
                    this.ventaOpen = false;
                    if (!String(this.form.cliente_wa || '').trim() && (v?.cliente_wa || v?.cliente_doc_num)) {
                        this.form.cliente_wa = String(v.cliente_wa || v.cliente_doc_num || '');
                    }
                },

                async searchModelos(q) {
                    const query = String(q || '').trim();
                    if (query.length < 2) return;
                    this.abortMdl();
                    const ac = new AbortController();
                    this.mdlAbort = ac;
                    this.mdlLoading = true;
                    try {
                        const res = await window.axios.get(this.urls.productos, {
                            headers: { 'Accept': 'application/json' },
                            params: { q: query, limit: 20 },
                            signal: ac.signal,
                        });
                        if (String(this.form.modelo_cerradura || '').trim() !== query) return;
                        const rows = res.data?.data || [];
                        this.mdlResults = (Array.isArray(rows) ? rows : []).slice(0, 12);
                        this.mdlOpen = true;
                    } catch {
                        if (ac.signal.aborted) return;
                        this.mdlOpen = false;
                        window.GCToast?.error?.('Productos', 'No se pudo buscar modelos.');
                    } finally {
                        if (!ac.signal.aborted) this.mdlLoading = false;
                    }
                },

                pickModelo(p) {
                    this.selectedModelo = p || null;
                    const model = String(p?.modelo || p?.sku || '').trim();
                    if (model) this.form.modelo_cerradura = model;
                    this.mdlOpen = false;
                },

                normalizeDireccion(v) {
                    return String(v || '')
                        .replace(/\s+/g, ' ')
                        .replace(/\s+,/g, ',')
                        .trim();
                },

                async autofillByVenta(ventaId) {
                    const id = parseInt(ventaId || 0, 10) || 0;
                    if (!id) return;
                    try {
                        const res = await window.axios.get(`/ventas/${id}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const raw = res.data?.data || {};
                        const data = raw?.data || raw;
                        const venta = data?.venta || data || null;
                        const items = data?.items || data?.detalles || data?.venta_items || [];
                        if (venta) {
                            // Dirección: preferimos la de cliente (si existe) pero no pisamos si el usuario ya escribió algo.
                            const dir = this.normalizeDireccion(venta.cliente_direccion || venta.direccion || venta.cliente?.direccion || '');
                            if (dir && !String(this.form.direccion || '').trim()) {
                                this.form.direccion = dir;
                            }
                        }

                        const arr = Array.isArray(items) ? items : [];
                        const firstWithPid = arr.find(it => parseInt(it?.producto_id || 0, 10) > 0) || null;
                        const first = firstWithPid || arr[0] || null;
                        const inlineModel = String(first?.modelo || first?.producto_modelo || first?.sku || first?.descripcion || '').trim();
                        const pid = firstWithPid ? (parseInt(firstWithPid.producto_id || 0, 10) || 0) : 0;
                        if (pid > 0) {
                            const pres = await window.axios.get(`/productos/${pid}`, { headers: { 'Accept': 'application/json' } });
                            const p = pres.data?.data?.producto || pres.data?.data || null;
                            const model = String(p?.modelo || '').trim() || String(p?.sku || '').trim() || inlineModel;
                            if (model) {
                                this.form.modelo_cerradura = model;
                                this.selectedModelo = p;
                                // Título autogenerado: solo si está vacío o coincide con el auto anterior.
                                const auto = `INSTALACION DE CERRADURA ${model}`.trim();
                                const cur = String(this.form.titulo || '').trim();
                                if (!cur || cur === this.lastAutoTitulo) {
                                    this.form.titulo = auto;
                                    this.lastAutoTitulo = auto;
                                }
                            }
                        } else if (inlineModel) {
                            this.form.modelo_cerradura = inlineModel;
                            const auto = `INSTALACION DE CERRADURA ${inlineModel}`.trim();
                            const cur = String(this.form.titulo || '').trim();
                            if (!cur || cur === this.lastAutoTitulo) {
                                this.form.titulo = auto;
                                this.lastAutoTitulo = auto;
                            }
                        } else {
                            // Si no hay producto, al menos sugerimos título por venta.
                            const cur = String(this.form.titulo || '').trim();
                            if (!cur) {
                                const auto = 'INSTALACION DE CERRADURA'.trim();
                                this.form.titulo = auto;
                                this.lastAutoTitulo = auto;
                            }
                        }
                    } catch {
                        // silencioso: no bloquear el modal si falla la consulta
                    }
                },

                defaultDateTimeLocal(isoDate = null) {
                    const now = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    const base = isoDate ? new Date(String(isoDate) + 'T09:00:00') : now;
                    if (Number.isNaN(base.getTime())) return '';
                    return `${base.getFullYear()}-${pad(base.getMonth()+1)}-${pad(base.getDate())}T${pad(base.getHours())}:${pad(base.getMinutes())}`;
                },

                normalizeDateTimeForSubmit(value) {
                    const raw = String(value || '').trim();
                    const pad = (n) => String(n).padStart(2, '0');
                    if (!raw) return '';

                    const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
                    if (iso) {
                        return `${iso[1]}-${iso[2]}-${iso[3]}T${iso[4]}:${iso[5]}`;
                    }

                    const cleaned = raw
                        .toLowerCase()
                        .replace(/\s+/g, ' ')
                        .replace(/\ba\.?\s*m\.?\b/g, 'am')
                        .replace(/\bp\.?\s*m\.?\b/g, 'pm');
                    const local = cleaned.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4}),?\s+(\d{1,2}):(\d{2})(?:\s*(am|pm))?$/);
                    if (local) {
                        let hour = parseInt(local[4], 10);
                        const meridian = local[6] || '';
                        if (meridian === 'pm' && hour < 12) hour += 12;
                        if (meridian === 'am' && hour === 12) hour = 0;
                        return `${local[3]}-${pad(local[2])}-${pad(local[1])}T${pad(hour)}:${pad(local[5])}`;
                    }

                    const d = new Date(raw.replace(' ', 'T'));
                    if (!Number.isNaN(d.getTime())) {
                        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    }

                    return raw;
                },

                responseError(data, fallback = 'No se pudo completar la operación.') {
                    if (data?.error) return String(data.error);
                    if (data?.errors && typeof data.errors === 'object') {
                        const first = Object.values(data.errors).flat().find(Boolean);
                        if (first) return String(first);
                    }
                    if (data?.message) return String(data.message);
                    return fallback;
                },

                openNewForDate(isoDate = null) {
                    this.resetForm();
                    if (isoDate) {
                        this.form.fecha_programada = this.defaultDateTimeLocal(isoDate);
                    }
                    this.$dispatch('open-modal','agenda-form');
                },

                syncTipoFields() {
                    const tipo = String(this.form.tipo || '').toUpperCase();
                    if (tipo === 'VENTA') {
                        this.form.ticket_id = '';
                    } else if (tipo === 'POSTVENTA') {
                        this.form.venta_id = '';
                    }
                },

                prevMonth() {
                    const [y, m] = String(this.month || this.todayMonth()).split('-').map(x => parseInt(x, 10));
                    const d = new Date(y, (m - 1) - 1, 1);
                    const pad = (n) => String(n).padStart(2, '0');
                    this.month = `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
                },

                nextMonth() {
                    const [y, m] = String(this.month || this.todayMonth()).split('-').map(x => parseInt(x, 10));
                    const d = new Date(y, (m - 1) + 1, 1);
                    const pad = (n) => String(n).padStart(2, '0');
                    this.month = `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
                },

                jumpToDate(isoDate) {
                    if (!isoDate) return;
                    const d = new Date(String(isoDate) + 'T00:00:00');
                    if (Number.isNaN(d.getTime())) return;
                    const pad = (n) => String(n).padStart(2, '0');
                    this.month = `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
                    const start = new Date(d.getFullYear(), d.getMonth(), 1);
                    const to = new Date(d.getFullYear(), d.getMonth() + 1, 0);
                    const fromIso = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
                    const toIso = `${to.getFullYear()}-${pad(to.getMonth() + 1)}-${pad(to.getDate())}`;
                    // filtra para que la lista arranque en el mes mostrado
                    this.q = '';
                    this.filterEstado = '';
                    this.page = 0;
                    // Solo UI: el data() ya trae límite 200, así que filtramos en frontend.
                    this.rows = (this.rows || []).filter(r => {
                        const s = String(r.fecha_programada || '').slice(0, 10);
                        return s >= fromIso && s <= toIso;
                    });
                },

                get pages() { return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage)); },
                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                get calendarCells() {
                    const m = String(this.month || this.todayMonth());
                    const [y, mm] = m.split('-').map(x => parseInt(x, 10));
                    if (!y || !mm) return [];

                    const first = new Date(y, mm - 1, 1);
                    const last = new Date(y, mm, 0);
                    // JS: 0=Dom..6=Sáb. Queremos semana Lun..Dom.
                    const firstDow = (first.getDay() + 6) % 7; // Lun=0
                    const totalDays = last.getDate();

                    // Preparamos rango completo (42 celdas)
                    const cells = [];
                    const start = new Date(y, mm - 1, 1 - firstDow);
                    const today = new Date();
                    const pad = (n) => String(n).padStart(2, '0');

                    // agrupar items por fecha (YYYY-MM-DD)
                    const byDay = {};
                    const inMonthIsoPrefix = `${y}-${String(mm).padStart(2, '0')}-`;
                    const monthRows = (this.filteredRows || []).filter(r => String(r.fecha_programada || '').startsWith(inMonthIsoPrefix));
                    for (const r of monthRows) {
                        const iso = String(r.fecha_programada || '').slice(0, 10);
                        if (!iso) continue;
                        if (!byDay[iso]) byDay[iso] = [];
                        byDay[iso].push(r);
                    }
                    for (const iso in byDay) {
                        byDay[iso].sort((a, b) => String(a.fecha_programada || '').localeCompare(String(b.fecha_programada || '')));
                    }

                    // items visibles por celda según breakpoint: tablet compacto, desktop más alto
                    const itemsLimit = window.matchMedia('(min-width: 1024px)').matches ? 3 : 2;

                    for (let i = 0; i < 42; i++) {
                        const d = new Date(start);
                        d.setDate(start.getDate() + i);
                        const inMonth = d.getMonth() === (mm - 1);
                        const iso = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        const items = byDay[iso] || [];
                        const isToday = d.toDateString() === today.toDateString();
                        cells.push({
                            key: iso,
                            isoDate: iso,
                            day: d.getDate(),
                            inMonth,
                            isToday,
                            count: items.length,
                            itemsPreview: items.slice(0, itemsLimit),
                            moreCount: Math.max(0, items.length - itemsLimit),
                        });
                    }
                    // si el mes necesita solo 35 celdas, igual dejamos 42 para consistencia.
                    return cells;
                },

                get monthDaysWithItems() {
                    const m = String(this.month || this.todayMonth());
                    const [y, mm] = m.split('-').map(x => parseInt(x, 10));
                    if (!y || !mm) return [];

                    const pad = (n) => String(n).padStart(2, '0');
                    const prefix = `${y}-${pad(mm)}-`;
                    const rows = (this.filteredRows || []).filter(r => String(r.fecha_programada || '').startsWith(prefix));

                    const byIso = {};
                    for (const r of rows) {
                        const iso = String(r.fecha_programada || '').slice(0, 10);
                        if (!iso) continue;
                        if (!byIso[iso]) byIso[iso] = [];
                        byIso[iso].push(r);
                    }
                    const isos = Object.keys(byIso).sort();
                    return isos.map(iso => {
                        const d = new Date(iso + 'T00:00:00');
                        const label = Number.isNaN(d.getTime())
                            ? iso
                            : d.toLocaleDateString('es-PE', { weekday: 'short', day: '2-digit', month: 'short' }).replace('.', '');
                        const items = (byIso[iso] || []).slice().sort((a, b) => String(a.fecha_programada || '').localeCompare(String(b.fecha_programada || '')));
                        return { iso, label, items };
                    });
                },

                get kpis() {
                    const rows = this.rows || [];
                    const is = (r, v) => String(r?.estado || '').toUpperCase() === v;
                    const pendientes = rows.filter(r => is(r, 'PENDIENTE')).length;
                    const programadas = rows.filter(r => is(r, 'PROGRAMADA')).length;
                    const realizadas = rows.filter(r => is(r, 'REALIZADA')).length;
                    const hoy = rows.filter(r => this.isToday(r.fecha_programada)).length;
                    return { pendientes, programadas, realizadas, hoy };
                },

                isToday(v) {
                    if (!v) return false;
                    const d = new Date(String(v).replace(' ', 'T'));
                    if (Number.isNaN(d.getTime())) return false;
                    return d.toDateString() === (new Date()).toDateString();
                },

                fmtDate(v) {
                    if (!v) return '—';
                    const d = new Date(String(v).replace(' ', 'T'));
                    if (Number.isNaN(d.getTime())) return String(v);
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mi = String(d.getMinutes()).padStart(2, '0');
                    return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
                },

                fmtTime(v) {
                    if (!v) return '';
                    const d = new Date(String(v).replace(' ', 'T'));
                    if (Number.isNaN(d.getTime())) return '';
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mi = String(d.getMinutes()).padStart(2, '0');
                    return `${hh}:${mi}`;
                },

                estadoClass(v) {
                    const s = String(v || '').toUpperCase();
                    if (s === 'REALIZADA') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                    if (s === 'PROGRAMADA') return 'bg-sky-50 text-sky-700 ring-sky-200';
                    if (s === 'CANCELADA') return 'bg-slate-100 text-slate-700 ring-slate-200';
                    return 'bg-amber-50 text-amber-700 ring-amber-200';
                },

                eventPillClass(it) {
                    const estado = String(it?.estado || '').toUpperCase();
                    const prio = String(it?.prioridad || '').toUpperCase();
                    // Base: Apple/Google-like chips (subtle fill + colored dot)
                    if (estado === 'REALIZADA') return 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200';
                    if (estado === 'PROGRAMADA') {
                        if (prio === 'URGENTE' || prio === 'ALTA') return 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-200';
                        return 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200';
                    }
                    if (estado === 'CANCELADA') return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200';
                    // PENDIENTE u otros
                    if (prio === 'URGENTE') return 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-200';
                    if (prio === 'ALTA') return 'bg-amber-50 text-amber-900 ring-1 ring-inset ring-amber-200';
                    return 'bg-slate-50 text-slate-800 ring-1 ring-inset ring-slate-200';
                },

                prioClass(v) {
                    const s = String(v || '').toUpperCase();
                    if (s === 'URGENTE') return 'bg-rose-600 text-white ring-rose-700';
                    if (s === 'ALTA') return 'bg-rose-50 text-rose-700 ring-rose-200';
                    if (s === 'MEDIA') return 'bg-amber-50 text-amber-700 ring-amber-200';
                    if (s === 'BAJA') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                    return 'bg-slate-100 text-slate-700 ring-slate-200';
                },

                resetForm() {
                    this.formError = '';
                    this.selectedCliente = null;
                    this.ventasCliente = [];
                    this.form = {
                        id: null,
                        tipo: 'VENTA',
                        estado: 'PENDIENTE',
                        fecha_programada: this.defaultDateTimeLocal(),
                        duracion_min: 60,
                        cliente_wa: '',
                        ticket_id: '',
                        venta_id: '',
                        titulo: '',
                        descripcion: '',
                        modelo_cerradura: '',
                        serial_cerradura: '',
                        direccion: '',
                        prioridad: '',
                        tecnico_id: '',
                        notas: '',
                    };
                },

                resetComplete() {
                    this.completeError = '';
                    this.completeSaving = false;
                    this.complete = {
                        terminado_at: this.defaultDateTimeLocal(),
                        notas: '',
                        gps_lat: '',
                        gps_lng: '',
                    };
                    if (this.$refs?.completeFoto) {
                        this.$refs.completeFoto.value = '';
                    }
                },

                canCompleteDetail() {
                    const d = this.detail || null;
                    if (!d) return false;
                    const st = String(d.estado || '').toUpperCase();
                    if (st === 'REALIZADA' || st === 'CANCELADA') return false;
                    const techId = parseInt(d.tecnico_id || 0, 10) || 0;
                    if (!this.isAdmin && this.currentUid > 0 && techId > 0 && techId !== this.currentUid) return false;
                    return true;
                },

                openComplete(row) {
                    this.completeError = '';
                    const base = this.defaultDateTimeLocal();
                    this.complete = {
                        terminado_at: base,
                        notas: '',
                        gps_lat: '',
                        gps_lng: '',
                    };
                    if (this.$refs?.completeFoto) {
                        this.$refs.completeFoto.value = '';
                    }
                },

                async fillMyLocation() {
                    if (!navigator.geolocation) {
                        window.GCToast?.error?.('Agenda', 'Este dispositivo no soporta geolocalización.');
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.complete.gps_lat = String(pos.coords.latitude);
                            this.complete.gps_lng = String(pos.coords.longitude);
                        },
                        () => {
                            window.GCToast?.error?.('Agenda', 'No se pudo obtener la ubicación.');
                        },
                        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
                    );
                },

                async submitComplete() {
                    const d = this.detail || null;
                    if (!d?.id) return;

                    this.completeError = '';
                    this.completeSaving = true;
                    try {
                        const fd = new FormData();
                        fd.append('terminado_at', this.normalizeDateTimeForSubmit(this.complete.terminado_at || ''));
                        if (this.complete.notas) fd.append('notas', this.complete.notas);
                        if (this.complete.gps_lat) fd.append('gps_lat', this.complete.gps_lat);
                        if (this.complete.gps_lng) fd.append('gps_lng', this.complete.gps_lng);
                        const files = Array.from(this.$refs?.completeFoto?.files || []);
                        if (files.length > 5) {
                            this.completeError = 'Puedes subir máximo 5 imágenes.';
                            window.GCToast?.error?.('Agenda', this.completeError);
                            return;
                        }
                        files.forEach((file) => fd.append('fotos[]', file));
                        if (files[0]) fd.append('foto', files[0]);

                        const res = await window.axios.post(this.urls.complete(d.id), fd, {
                            headers: { 'Accept': 'application/json' },
                        });

                        if (res.data?.ok !== true) {
                            this.completeError = this.responseError(res.data, 'No se pudo confirmar.');
                            window.GCToast?.error?.('Agenda', this.completeError);
                            return;
                        }

                        const agenda = res.data?.data?.agenda || null;
                        if (agenda) {
                            this.detail = agenda;
                        }

                        window.GCToast?.success?.('Agenda', 'Servicio confirmado.');
                        this.$dispatch('close-modal', 'agenda-completar');
                        await this.reload();
                    } catch (e) {
                        const msg = this.responseError(e?.response?.data, e?.message || 'No se pudo confirmar.');
                        this.completeError = msg;
                        window.GCToast?.error?.('Agenda', msg);
                    } finally {
                        this.completeSaving = false;
                    }
                },

                fillForm(row) {
                    this.formError = '';
                    const parsed = this.parseInstallMeta(row?.notas || '');
                    this.form = {
                        id: row?.id || null,
                        tipo: row?.tipo || 'VENTA',
                        estado: row?.estado || 'PENDIENTE',
                        fecha_programada: this.toLocal(row?.fecha_programada),
                        duracion_min: row?.duracion_min ?? 60,
                        cliente_wa: row?.cliente_wa || '',
                        ticket_id: row?.ticket_id || '',
                        venta_id: row?.venta_id || '',
                        titulo: row?.titulo || '',
                        descripcion: row?.descripcion || '',
                        modelo_cerradura: parsed.modelo || '',
                        serial_cerradura: parsed.serial || '',
                        direccion: parsed.direccion || '',
                        prioridad: row?.prioridad || '',
                        tecnico_id: row?.tecnico_id || '',
                        notas: this.stripInstallMeta(row?.notas || ''),
                    };
                },

                parseInstallMeta(notas) {
                    const s = String(notas || '');
                    const m = s.match(/\[INSTALACION\]([^\n]*)/i);
                    if (!m) return { modelo: '', serial: '', direccion: '' };
                    try {
                        const json = JSON.parse(m[1].trim());
                        return {
                            modelo: String(json?.modelo || ''),
                            serial: String(json?.serial || ''),
                            direccion: String(json?.direccion || ''),
                        };
                    } catch {
                        return { modelo: '', serial: '', direccion: '' };
                    }
                },

                stripInstallMeta(notas) {
                    return String(notas || '').replace(/\n?\[INSTALACION\][^\n]*\n?/ig, '').trim();
                },

                withInstallMeta(notas) {
                    const clean = this.stripInstallMeta(notas || '');
                    const meta = {
                        modelo: String(this.form.modelo_cerradura || '').trim(),
                        serial: String(this.form.serial_cerradura || '').trim(),
                        direccion: String(this.form.direccion || '').trim(),
                    };
                    const has = meta.modelo || meta.serial || meta.direccion;
                    if (!has) return clean;
                    return (clean ? (clean + "\n") : "") + `[INSTALACION]${JSON.stringify(meta)}`;
                },

                toLocal(v) {
                    if (!v) return '';
                    const d = new Date(String(v).replace(' ', 'T'));
                    if (Number.isNaN(d.getTime())) return '';
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                },

                async openDetail(id) {
                    this.detail = null;
                    this.detailError = '';
                    this.detailCliente = null;
                    this.detailVenta = null;
                    this.$dispatch('open-modal', 'agenda-detalle');
                    try {
                        const res = await window.axios.get(this.urls.show(id), { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.detailError = res.data?.error || 'No se pudo cargar.';
                            return;
                        }
                        this.detail = res.data?.data || null;
                        await this.loadDetailCliente();
                        await this.loadDetailVenta();
                    } catch (e) {
                        this.detailError = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                async loadDetailCliente() {
                    this.detailCliente = null;
                    const d = this.detail || null;
                    if (!d) return;

                    const id = d.cliente_id ? parseInt(d.cliente_id, 10) : 0;
                    if (id > 0) {
                        try {
                            const res = await window.axios.get(`/clientes/${id}`, { headers: { 'Accept': 'application/json' } });
                            const cliente = res.data?.data?.cliente || null;
                            if (cliente) { this.detailCliente = cliente; return; }
                        } catch {
                            // ignore
                        }
                    }

                    const tel = String(d.cliente_wa || '').trim();
                    if (!tel) return;
                    try {
                        const res = await window.axios.get(this.urls.clientes, {
                            headers: { 'Accept': 'application/json' },
                            params: { q: tel, limit: 5 },
                        });
                        const rows = res.data?.data || [];
                        const found = (Array.isArray(rows) ? rows : []).find(r => String(r.telefono || '') === tel);
                        if (found) this.detailCliente = found;
                    } catch {
                        // ignore
                    }
                },

                async loadDetailVenta() {
                    this.detailVenta = null;
                    const d = this.detail || null;
                    const id = d?.venta_id ? parseInt(d.venta_id, 10) : 0;
                    if (id <= 0) return;
                    try {
                        const res = await window.axios.get(`/ventas/${id}`, { headers: { 'Accept': 'application/json' } });
                        const venta = res.data?.data?.venta || res.data?.data?.data?.venta || res.data?.data?.venta || null;
                        // VentasController::show devuelve {ok, data: <remote>}; el remote normalmente trae {venta: {...}}
                        if (res.data?.data?.venta) {
                            this.detailVenta = res.data.data.venta;
                            return;
                        }
                        if (res.data?.data?.venta_codigo || res.data?.data?.id) {
                            this.detailVenta = res.data.data;
                            return;
                        }
                        const remote = res.data?.data || null;
                        if (remote?.venta) this.detailVenta = remote.venta;
                    } catch {
                        // ignore
                    }
                },

                tecnicoLabel(id) {
                    const n = id ? parseInt(id, 10) : 0;
                    if (n <= 0) return '—';
                    const u = (this.tecnicos || []).find(x => parseInt(x.id, 10) === n);
                    const name = u?.nombre || u?.name || '';
                    return name ? name : `Usuario #${n}`;
                },

	                async submit() {
	                    this.formError = '';
	                    this.saving = true;
	                    try {
	                        const payload = { ...this.form };
	                        delete payload.id;
                            payload.notas = this.withInstallMeta(payload.notas || '');
                            delete payload.modelo_cerradura;
                            delete payload.serial_cerradura;
                            delete payload.direccion;
	                        const tipo = String(this.form.tipo || '').toUpperCase();
	                        if (tipo === 'VENTA') payload.ticket_id = null;
	                        if (tipo === 'POSTVENTA') payload.venta_id = null;
	                        if (!payload.venta_id) payload.venta_id = null;
	                        if (!payload.ticket_id) payload.ticket_id = null;
	                        if (!payload.tecnico_id) payload.tecnico_id = null;
	                        const url = this.form.id ? this.urls.update(this.form.id) : this.urls.create;
	                        const res = await window.axios.post(url, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.formError = res.data?.error || 'No se pudo guardar.';
                            window.GCToast?.error?.(this.formError);
                            return;
                        }
                        await this.reload();
                        window.GCToast?.success?.('Agenda guardada');
                        this.$dispatch('close-modal', 'agenda-form');
                    } catch (e) {
                        this.formError = e?.response?.data?.error || e?.message || 'Error';
                        window.GCToast?.error?.(this.formError);
                    } finally {
                        this.saving = false;
                    }
                },

                async destroy() {
                    if (!this.form?.id) return;
                    this.saving = true;
                    try {
                        const res = await window.axios.post(this.urls.destroy(this.form.id), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            window.GCToast?.error?.(res.data?.error || 'No se pudo eliminar.');
                            return;
                        }
                        await this.reload();
                        window.GCToast?.success?.('Agenda eliminada');
                        this.$dispatch('close-modal', 'agenda-form');
                    } catch (e) {
                        window.GCToast?.error?.(e?.response?.data?.error || e?.message || 'Error');
                    } finally {
                        this.saving = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>

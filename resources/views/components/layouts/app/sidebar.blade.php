<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-950 dark:bg-zinc-950 dark:text-zinc-100">
        <flux:sidebar sticky collapsible class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <flux:sidebar.header>
                <a href="{{ route('beranda') }}" class="flex min-w-0 items-center gap-2 in-data-flux-sidebar-collapsed-desktop:hidden" wire:navigate>
                    <x-app-logo class="size-8" href="#"></x-app-logo>
                </a>
                <flux:sidebar.collapse class="max-lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group expandable :expanded="request()->routeIs('beranda', 'tugas-saya', 'papan', 'kalender')" heading="Pekerjaan Saya" icon="briefcase">
                    <flux:sidebar.item icon="home" :href="route('beranda')" :current="request()->routeIs('beranda')" wire:navigate>Beranda</flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('tugas-saya')" :current="request()->routeIs('tugas-saya')" wire:navigate>Tugas Saya</flux:sidebar.item>
                    <flux:sidebar.item icon="view-columns" :href="route('papan')" :current="request()->routeIs('papan')" wire:navigate>Papan Kanban</flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('kalender')" :current="request()->routeIs('kalender')" wire:navigate>Kalender</flux:sidebar.item>
                </flux:sidebar.group>

                @canany(['kelola_agenda', 'kelola_tugas', 'kelola_pr_plan'])
                    <flux:sidebar.group expandable :expanded="request()->routeIs('agenda.*', 'tugas.index', 'pr-plan.*')" heading="Perencanaan" icon="calendar-days">
                        @can('kelola_agenda')
                            <flux:sidebar.item icon="calendar-days" :href="route('agenda.index')" :current="request()->routeIs('agenda.*')" wire:navigate>Kelola Agenda</flux:sidebar.item>
                        @endcan
                        @can('kelola_tugas')
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('tugas.index')" :current="request()->routeIs('tugas.index')" wire:navigate>Kelola Tugas</flux:sidebar.item>
                        @endcan
                        @can('kelola_pr_plan')
                            <flux:sidebar.item icon="document-chart-bar" :href="route('pr-plan.index')" :current="request()->routeIs('pr-plan.*')" wire:navigate>PR Plan</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany

                @canany(['kelola_konten', 'upload_publikasi'])
                    <flux:sidebar.group expandable :expanded="request()->routeIs('produksi.*', 'visual.carousel', 'visual.video', 'publikasi.*')" heading="Produksi & Tayang" icon="film">
                        @can('kelola_konten')
                            <flux:sidebar.item icon="pencil-square" :href="route('produksi.index')" :current="request()->routeIs('produksi.*')" wire:navigate>Meja Produksi</flux:sidebar.item>
                            <flux:sidebar.item icon="photo" :href="route('visual.carousel')" :current="request()->routeIs('visual.carousel')" wire:navigate>Studio Carousel</flux:sidebar.item>
                            <flux:sidebar.item icon="film" :href="route('visual.video')" :current="request()->routeIs('visual.video')" wire:navigate>Studio Video</flux:sidebar.item>
                        @endcan
                        @can('upload_publikasi')
                            <flux:sidebar.item icon="arrow-up-on-square" :href="route('publikasi.index')" :current="request()->routeIs('publikasi.*')" wire:navigate>Publikasi & Arsip</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany

                @canany(['kelola_monitoring', 'lihat_laporan'])
                    <flux:sidebar.group expandable :expanded="request()->routeIs('monitoring.*', 'laporan.*')" heading="Pemantauan" icon="chart-bar-square">
                        @can('kelola_monitoring')
                            <flux:sidebar.item icon="magnifying-glass" :href="route('monitoring.index')" :current="request()->routeIs('monitoring.*')" wire:navigate>Monitoring</flux:sidebar.item>
                        @endcan
                        @can('lihat_laporan')
                            <flux:sidebar.item icon="chart-bar-square" :href="route('laporan.index')" :current="request()->routeIs('laporan.*')" wire:navigate>Pusat Laporan</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany

                <flux:sidebar.group expandable :expanded="request()->routeIs('pustaka.*', 'visual.template', 'tim.*', 'settings.ai')" heading="Referensi & Pengaturan" icon="cog-6-tooth">
                    <flux:sidebar.item icon="book-open" :href="route('pustaka.index')" :current="request()->routeIs('pustaka.*')" wire:navigate>Pustaka</flux:sidebar.item>
                    @can('kelola_template_visual')
                        <flux:sidebar.item icon="swatch" :href="route('visual.template')" :current="request()->routeIs('visual.template')" wire:navigate>Template Visual</flux:sidebar.item>
                    @endcan
                    @can('kelola_pengguna')
                        <flux:sidebar.item icon="user-group" :href="route('tim.index')" :current="request()->routeIs('tim.*')" wire:navigate>Kelola Tim</flux:sidebar.item>
                    @endcan
                    @can('kelola_ai')
                        <flux:sidebar.item icon="sparkles" :href="route('settings.ai')" :current="request()->routeIs('settings.ai')" wire:navigate>Pengaturan AI</flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->nama"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->nama }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->nama }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>

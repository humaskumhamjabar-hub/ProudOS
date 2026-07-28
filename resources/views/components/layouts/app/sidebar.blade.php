<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('beranda') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="PROUD" class="grid">
                    <flux:navlist.item icon="home" :href="route('beranda')" :current="request()->routeIs('beranda')" wire:navigate>Beranda</flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-list" :href="route('tugas-saya')" :current="request()->routeIs('tugas-saya')" wire:navigate>Tugas Saya</flux:navlist.item>
                    <flux:navlist.item icon="view-columns" :href="route('papan')" :current="request()->routeIs('papan')" wire:navigate>Papan Kanban</flux:navlist.item>
                    <flux:navlist.item icon="calendar" :href="route('kalender')" :current="request()->routeIs('kalender')" wire:navigate>Kalender</flux:navlist.item>
                    <flux:navlist.item icon="book-open" :href="route('pustaka.index')" :current="request()->routeIs('pustaka.*')" wire:navigate>Pustaka</flux:navlist.item>
                    @can('kelola_pengguna')
                        <flux:navlist.item icon="user-group" :href="route('tim.index')" :current="request()->routeIs('tim.*')" wire:navigate>Kelola Tim</flux:navlist.item>
                    @endcan
                    @can('kelola_agenda')
                        <flux:navlist.item icon="calendar-days" :href="route('agenda.index')" :current="request()->routeIs('agenda.*')" wire:navigate>Kelola Agenda</flux:navlist.item>
                    @endcan
                    @can('kelola_tugas')
                        <flux:navlist.item icon="clipboard-document-check" :href="route('tugas.index')" :current="request()->routeIs('tugas.index')" wire:navigate>Kelola Tugas</flux:navlist.item>
                    @endcan
                    @can('kelola_pr_plan')
                        <flux:navlist.item icon="document-chart-bar" :href="route('pr-plan.index')" :current="request()->routeIs('pr-plan.*')" wire:navigate>PR Plan</flux:navlist.item>
                    @endcan
                    @can('kelola_konten')
                        <flux:navlist.item icon="pencil-square" :href="route('produksi.index')" :current="request()->routeIs('produksi.*')" wire:navigate>Meja Produksi</flux:navlist.item>
                        <flux:navlist.item icon="photo" :href="route('visual.carousel')" :current="request()->routeIs('visual.carousel')" wire:navigate>Studio Carousel</flux:navlist.item>
                        <flux:navlist.item icon="film" :href="route('visual.video')" :current="request()->routeIs('visual.video')" wire:navigate>Studio Video</flux:navlist.item>
                    @endcan
                    @can('kelola_template_visual')
                        <flux:navlist.item icon="swatch" :href="route('visual.template')" :current="request()->routeIs('visual.template')" wire:navigate>Template Visual</flux:navlist.item>
                    @endcan
                    @can('upload_publikasi')
                        <flux:navlist.item icon="arrow-up-on-square" :href="route('publikasi.index')" :current="request()->routeIs('publikasi.*')" wire:navigate>Publikasi & Arsip</flux:navlist.item>
                    @endcan
                    @can('kelola_monitoring')
                        <flux:navlist.item icon="magnifying-glass" :href="route('monitoring.index')" :current="request()->routeIs('monitoring.*')" wire:navigate>Monitoring</flux:navlist.item>
                    @endcan
                    @can('lihat_laporan')
                        <flux:navlist.item icon="chart-bar-square" :href="route('laporan.index')" :current="request()->routeIs('laporan.*')" wire:navigate>Pusat Laporan</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            </flux:navlist>

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

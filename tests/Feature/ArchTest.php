<?php

// Batas modul ditegakkan di sini, bukan lewat niat baik.
// Kalau ada batas yang perlu ditembus, ubah rancangannya dulu — jangan matikan testnya.

arch('modul tidak saling menyentuh model internal')
    ->expect('Modules\Scheduling')
    ->not->toUse([
        'Modules\People\Models',
        'Modules\Agenda\Models',
        'Modules\Work\Models',
        'Modules\Publishing\Models',
        'Modules\Library\Models',
    ])
    ->group('arch');

arch('tidak ada modul yang bergantung pada lapisan baca')
    ->expect(['App\Livewire'])
    ->not->toBeUsedIn('Modules')
    ->group('arch');

arch('agenda tidak mengenal sumbernya')
    ->expect('Modules\Agenda')
    ->not->toUse(['Modules\Planning', 'Modules\Work', 'Modules\Content'])
    ->group('arch');

arch('planning tidak menyentuh model modul lain')
    ->expect('Modules\Planning')
    ->not->toUse([
        'Modules\Agenda\Models',
        'Modules\People\Models',
        'Modules\Publishing\Models',
        'Modules\Scheduling\Models',
        'Modules\Work\Models',
    ])
    ->group('arch');

arch('work tidak menyentuh model modul lain')
    ->expect('Modules\Work')
    ->not->toUse([
        'Modules\Agenda\Models',
        'Modules\People\Models',
        'Modules\Scheduling\Models',
        'Modules\Publishing\Models',
    ])
    ->group('arch');

arch('content tidak menyentuh model internal modul lain')
    ->expect('Modules\Content')
    ->not->toUse([
        'Modules\Agenda\Models',
        'Modules\People\Models',
        'Modules\Planning\Models',
        'Modules\Publishing\Models',
        'Modules\Scheduling\Models',
        'Modules\Work\Models',
    ])
    ->group('arch');

arch('ai tidak menyentuh model internal modul lain')
    ->expect('Modules\Ai')
    ->not->toUse([
        'Modules\Agenda\Models',
        'Modules\Content\Models',
        'Modules\People\Models',
        'Modules\Planning\Models',
        'Modules\Publishing\Models',
        'Modules\Scheduling\Models',
        'Modules\Work\Models',
    ])
    ->group('arch');

arch('visual tidak mengenal agenda atau internal modul lain')
    ->expect('Modules\Visual')
    ->not->toUse([
        'Modules\Agenda',
        'Modules\Content\Models',
        'Modules\People\Models',
        'Modules\Planning\Models',
        'Modules\Publishing\Models',
        'Modules\Scheduling\Models',
        'Modules\Work\Models',
    ])
    ->group('arch');

arch('publishing berdiri sendiri dari modul hulu')
    ->expect('Modules\Publishing')
    ->not->toUse([
        'Modules\Agenda',
        'Modules\Content\Models',
        'Modules\People\Models',
        'Modules\Planning',
        'Modules\Scheduling',
        'Modules\Work',
    ])
    ->group('arch');

arch('library hampir tanpa ketergantungan')
    ->expect('Modules\Library')
    ->not->toUse(['Modules\Agenda', 'Modules\Work', 'Modules\Scheduling', 'Modules\Publishing', 'Modules\People\Models'])
    ->group('arch');

arch('people tidak melihat siapa pun')
    ->expect('Modules\People')
    ->not->toUse(['Modules\Agenda', 'Modules\Work', 'Modules\Scheduling', 'Modules\Publishing', 'Modules\Library'])
    ->group('arch');

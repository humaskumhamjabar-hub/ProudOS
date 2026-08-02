<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        html, body { width: 1080px; height: 1350px; margin: 0; overflow: hidden; }
        body { background: #f8f8f6; color: #14265b; font-family: Roboto, Arial, Helvetica, sans-serif; }
        .canvas { position: relative; width: 1080px; height: 1350px; overflow: hidden; background: #f8f8f6; }
        .brand { position: absolute; z-index: 5; top: 34px; left: 67px; display: flex; align-items: center; gap: 15px; }
        .brand-mark { display: grid; width: 68px; height: 68px; place-items: center; background: #12234f; color: #ffcc16; }
        .brand-copy { color: #111318; font-size: 17px; line-height: 1.14; letter-spacing: .01em; }
        .brand-copy strong { display: block; font-size: 18px; }
        .brand-copy span { display: block; }
        .yellow-field { position: absolute; top: 101px; left: 194px; width: 886px; height: 541px; border-top-left-radius: 92px; background: #ffca15; }
        .gray-field { position: absolute; top: 233px; left: 0; width: 194px; height: 408px; background: #c5c9d3; }
        .navy-field { position: absolute; top: 232px; left: 0; width: 949px; height: 553px; border-bottom-right-radius: 94px; background: #172a5d; }
        .photo { position: absolute; z-index: 3; overflow: hidden; background: #d5d7dc; }
        .photo img { width: 100%; height: 100%; object-fit: cover; transform-origin: center; }
        .photo-main { top: 167px; left: 63px; width: 473px; height: 573px; border-radius: 48px; }
        .photo-top { top: 167px; left: 559px; width: 472px; height: 268px; border-radius: 47px; }
        .photo-bottom { top: 466px; left: 559px; width: 472px; height: 274px; border-radius: 47px; }
        .meta { position: absolute; top: 805px; left: 63px; z-index: 4; display: flex; width: 858px; height: 90px; align-items: center; border-radius: 45px; background: linear-gradient(90deg, #fff3c9 0 61%, #d3d6de 100%); overflow: hidden; }
        .city { padding-left: 30px; width: 265px; color: #172a5d; font-size: 45px; font-weight: 800; letter-spacing: -.04em; text-transform: uppercase; }
        .date { display: flex; height: 74px; flex: 1; align-items: center; padding: 0 52px; border-radius: 38px 0 0 38px; background: #172a5d; color: #ffca15; font-size: 36px; font-weight: 800; letter-spacing: -.04em; white-space: nowrap; }
        .copy { position: absolute; top: 916px; left: 63px; right: 58px; }
        h1 { max-width: 930px; margin: 0; color: #172a5d; font-size: 62px; font-weight: 800; line-height: 1.01; letter-spacing: -.055em; }
        .subtitle { max-width: 945px; margin: 25px 0 0; color: #172a5d; font-size: 32px; font-weight: 700; line-height: 1.05; letter-spacing: -.035em; }
        .website { position: absolute; right: 0; bottom: 32px; display: flex; width: 420px; height: 67px; align-items: center; justify-content: center; border-radius: 34px 0 0 34px; background: #203b8d; color: #f7f7f4; font-size: 24px; font-style: italic; letter-spacing: .02em; }
        .website-icon { position: absolute; right: 0; display: grid; width: 100px; height: 67px; place-items: center; background: #ffca15; color: #172a5d; }
        .website span:first-child { margin-right: 80px; }
        .content-photo { position: absolute; z-index: 2; overflow: hidden; background: #d5d7dc; }
        .content-photo img { width: 100%; height: 100%; object-fit: cover; transform-origin: center; }
        .content-copy { position: absolute; z-index: 3; overflow: hidden; color: #172a5d; }
        .content-brand { display: flex; justify-content: space-between; color: #4f46e5; font-size: 22px; font-weight: 700; letter-spacing: .12em; }
        .content-brand span:last-child { color: #71717a; }
        .content-copy p { white-space: pre-line; margin: 0; color: #172a5d; line-height: 1.18; letter-spacing: -.035em; }
    </style>
</head>
<body>
@php
    $ukuranCanvaPx = static fn (int|float $ukuran): float => round((float) $ukuran * 1.35, 3);
    $styleFoto = static function (array $editor): string {
        $rotasi = (int) ($editor['rotasi'] ?? 0);
        $zoom = (float) ($editor['zoom'] ?? 1);
        $skala = $zoom * (abs(cos(deg2rad($rotasi))) + 1.76 * abs(sin(deg2rad($rotasi))));
        return 'object-position: '.((int) ($editor['fokus_x'] ?? 50)).'% '.((int) ($editor['fokus_y'] ?? 50)).'%; transform: rotate('.$rotasi.'deg) scale('.$skala.');';
    };
@endphp
<main class="canvas">
    @if ($background)<img src="{{ $background }}" alt="" style="position:absolute;inset:0;width:1080px;height:1350px;object-fit:cover;z-index:0">@endif
    @if ((int) $slide['urutan'] === 1)
        @if (! $background)<header class="brand">
            <div class="brand-mark" aria-hidden="true">
                <svg width="55" height="55" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 23V42H46V23M12 23C13 13 20 8 27.5 8S42 13 43 23M18 23C19 16 22 13 27.5 13S36 16 37 23M27.5 8V3M8 23H47M14 42V47H41V42" stroke="currentColor" stroke-width="2.5"/><circle cx="27.5" cy="27" r="4" fill="currentColor"/><text x="27.5" y="53" fill="currentColor" font-size="4.2" font-family="Arial" text-anchor="middle">PENGAYOMAN</text></svg>
            </div>
            <div class="brand-copy"><strong>KEMENTERIAN HUKUM</strong><span>KANTOR WILAYAH JAWA BARAT</span></div>
        </header>
        <div class="yellow-field"></div><div class="gray-field"></div><div class="navy-field"></div>@endif
        @foreach ([['photo-main', 0], ['photo-top', 1], ['photo-bottom', 2]] as [$kelas, $index])
            @php($editor = $slide['foto_slots'][$index] ?? [])
            @php($kotakFoto = $penempatan['foto_slots'][$index])
            <div class="photo {{ $kelas }}" style="left:{{ $kotakFoto['x'] }}px;top:{{ $kotakFoto['y'] }}px;width:{{ $kotakFoto['lebar'] }}px;height:{{ $kotakFoto['tinggi'] }}px;border-radius:{{ $kotakFoto['radius'] }}px"><img src="{{ $foto[$index] ?? '' }}" alt="" style="{{ $styleFoto($editor) }}"></div>
        @endforeach
        <div class="meta"><div class="city" style="font-size: {{ $ukuranCanvaPx($slide['ukuran_kota'] ?? 35) }}px">{{ $slide['kota'] }}</div><div class="date" style="font-size: {{ $ukuranCanvaPx($slide['ukuran_tanggal'] ?? 30) }}px">{{ $slide['tanggal'] }}</div></div>
        <section class="copy"><h1 style="font-size: {{ $ukuranCanvaPx($slide['ukuran_judul'] ?? 50) }}px">{{ $slide['judul'] }}</h1><p class="subtitle" style="font-size: {{ $ukuranCanvaPx($slide['ukuran_isi'] ?? 30) }}px">{{ $slide['isi'] }}</p></section>
        @if (! $background)<footer class="website"><span>jabar.kemenkum.go.id</span><span class="website-icon" aria-hidden="true">◎</span></footer>@endif
    @else
        @php($editor = $slide)
        @php($kotakFoto = $penempatan['foto_slots'][0])
        @php($kotakTeks = $penempatan['teks'])
        <div class="content-photo" style="left:{{ $kotakFoto['x'] }}px;top:{{ $kotakFoto['y'] }}px;width:{{ $kotakFoto['lebar'] }}px;height:{{ $kotakFoto['tinggi'] }}px;border-radius:{{ $kotakFoto['radius'] }}px"><img src="{{ $foto[0] ?? '' }}" alt="" style="{{ $styleFoto($editor) }}"></div>
        <section class="content-copy" style="left:{{ $kotakTeks['x'] }}px;top:{{ $kotakTeks['y'] }}px;width:{{ $kotakTeks['lebar'] }}px;height:{{ $kotakTeks['tinggi'] }}px"><p style="font-size: {{ $ukuranCanvaPx($slide['ukuran_isi'] ?? 35) }}px">{{ $slide['isi'] }}</p></section>
    @endif
</main>
</body>
</html>

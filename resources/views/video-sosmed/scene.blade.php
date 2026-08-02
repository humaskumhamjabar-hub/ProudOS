<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        html, body { width: 1080px; height: 1920px; margin: 0; overflow: hidden; background: #f8f8f6; }
        body { position: relative; font-family: Roboto, Arial, sans-serif; color: #172a5d; }
        .layer { position: absolute; overflow: hidden; }
        .layer img { width: 100%; height: 100%; object-fit: contain; }
        .foto img { object-fit: cover; }
        .teks { display: flex; align-items: center; padding: 18px 24px; white-space: pre-line; overflow-wrap: anywhere; }
        .judul { font-size: 68px; font-weight: 800; line-height: 1.04; letter-spacing: -.035em; }
        .paragraf { font-size: 42px; font-weight: 600; line-height: 1.18; letter-spacing: -.02em; }
        .tanggal { font-size: 36px; font-weight: 800; text-transform: uppercase; }
    </style>
</head>
<body>
@php
    $teksLayer = static fn (array $layer): string => \App\Support\IsiLayerVideoTemplate::teks($layer, $slide);
@endphp
@foreach (collect($scene['layers'] ?? [])->sortBy('urutan') as $layer)
    @php($jenisAset = 'video_scene_'.($sceneIndex + 1).'_'.$layer['id'])
    @php($style = 'left:'.$layer['x'].'px;top:'.$layer['y'].'px;width:'.$layer['lebar'].'px;height:'.$layer['tinggi'].'px;z-index:'.$layer['urutan'])
    @if ($layer['jenis'] === 'png' && isset($aset[$jenisAset]))
        <div class="layer" style="{{ $style }}"><img src="{{ $aset[$jenisAset] }}" alt=""></div>
    @elseif ($layer['jenis'] === 'foto')
        <div class="layer foto" style="{{ $style }}"><img src="{{ $gambar }}" alt=""></div>
    @elseif (in_array($layer['jenis'], ['judul', 'paragraf'], true))
        <div class="layer teks {{ $layer['jenis'] }} {{ $layer['id'] === 'tanggal' ? 'tanggal' : '' }}" style="{{ $style }}">{{ $teksLayer($layer) }}</div>
    @endif
@endforeach
</body>
</html>

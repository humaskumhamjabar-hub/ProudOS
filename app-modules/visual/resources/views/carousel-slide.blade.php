<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; width: 1080px; height: 1350px; overflow: hidden; }
        body { font-family: Georgia, 'Times New Roman', serif; background: #efe9da; color: #10242e; }
        .slide { position: relative; width: 1080px; height: 1350px; overflow: hidden; background: #efe9da; }
        .photo { position: absolute; inset: 0; background-size: cover; background-position: center; transform: translate({{ $slide->posisi_foto['x'] ?? 0 }}px, {{ $slide->posisi_foto['y'] ?? 0 }}px) scale({{ $slide->posisi_foto['zoom'] ?? 1 }}); }
        .wash { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(5,24,34,.05) 16%, rgba(5,24,34,.78) 73%, #051822 100%); }
        .masthead { position: absolute; left: 62px; right: 62px; top: 52px; display: flex; align-items: center; justify-content: space-between; color: white; font: 700 21px/1 Arial, sans-serif; letter-spacing: .16em; }
        .mark { display: flex; align-items: center; gap: 14px; }
        .seal { width: 42px; height: 42px; display: grid; place-items: center; border: 2px solid #d4a82f; border-radius: 50%; color: #d4a82f; font-size: 17px; }
        .number { opacity: .72; font-weight: 600; letter-spacing: .08em; }
        .copy { position: absolute; left: 62px; right: 62px; bottom: 68px; color: white; }
        .kicker { display: inline-block; margin-bottom: 22px; border-top: 4px solid #d4a82f; padding-top: 10px; font: 800 19px/1.2 Arial, sans-serif; letter-spacing: .18em; text-transform: uppercase; color: #f4ce65; }
        h1 { margin: 0; max-width: 900px; font-size: {{ $slide->jenis === 'cover' ? '74px' : '62px' }}; line-height: .98; letter-spacing: -.035em; text-wrap: balance; }
        p { max-width: 840px; margin: 25px 0 0; font: 400 28px/1.42 Arial, sans-serif; color: #e7edf0; }
        .rule { position: absolute; left: 0; bottom: 0; width: 100%; height: 15px; background: linear-gradient(90deg, #d4a82f 0 24%, #f4ce65 24% 34%, #d4a82f 34% 100%); }
        .no-photo { position: absolute; inset: 0; background: radial-gradient(circle at 80% 20%, rgba(212,168,47,.35), transparent 25%), linear-gradient(135deg, #153642, #061820); }
    </style>
</head>
<body>
    <main class="slide">
        @if ($gambar)
            <div class="photo" style="background-image: url('{{ $gambar }}')"></div>
        @else
            <div class="no-photo"></div>
        @endif
        <div class="wash"></div>
        <header class="masthead">
            <div class="mark"><span class="seal">K</span><span>KEMENKUM JAWA BARAT</span></div>
            <span class="number">{{ str_pad((string) $slide->urutan, 2, '0', STR_PAD_LEFT) }}</span>
        </header>
        <section class="copy">
            @if ($slide->isi_teks['kicker'] ?? null)<span class="kicker">{{ $slide->isi_teks['kicker'] }}</span>@endif
            <h1>{{ $slide->isi_teks['judul'] ?? '' }}</h1>
            @if ($slide->isi_teks['isi'] ?? null)<p>{{ $slide->isi_teks['isi'] }}</p>@endif
        </section>
        <div class="rule"></div>
    </main>
</body>
</html>

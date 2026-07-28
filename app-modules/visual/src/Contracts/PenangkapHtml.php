<?php

namespace Modules\Visual\Contracts;

interface PenangkapHtml
{
    public function tangkap(string $htmlPath, string $pngPath, int $lebar, int $tinggi): void;
}

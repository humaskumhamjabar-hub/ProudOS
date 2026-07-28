<?php

namespace Modules\Ai;

final readonly class HasilAi
{
    public function __construct(
        public string $isi,
        public string $model,
        public string $promptVersi,
    ) {}
}

<?php

namespace App\Dto;

final readonly class SlackButtons
{
    /**
     * @var array<SlackButton>
     */
    public array $buttons;

    public function __construct(
        public string $id,
        public string $text,
        public ?string $color = null,
        SlackButton ...$buttons,
    ) {
        $this->buttons = $buttons;
    }
}

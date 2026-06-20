<?php

use App\Models\Attendee;

it('formats a public display name as first name + last initial', function () {
    expect((new Attendee(['name' => 'Ada Lovelace']))->displayName())->toBe('Ada L.')
        ->and((new Attendee(['name' => 'Grace Brewster Hopper']))->displayName())->toBe('Grace H.')
        ->and((new Attendee(['name' => 'Madonna']))->displayName())->toBe('Madonna');
});

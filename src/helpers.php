<?php

function nestable(array $data = null)
{
    $nestable = \App::make('Nestable\Services\NestableService');

    if (is_array($data)) {
        $nestable = $nestable->make($data);
    }

    return $nestable;
}

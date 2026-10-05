<?php
// GET /wall — public page, no login needed.
function wall_index(): void
{
    view('wall', [
        'title' => 'Public wall',
        'nav' => 'wall',
        'capsules' => capsules_on_wall(),
    ]);
}

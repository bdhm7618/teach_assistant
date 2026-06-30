<?php

namespace Modules\ParentPortal\App\Events;

use Modules\ParentPortal\App\Models\ParentAccount;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class ParentPasswordResetRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ParentAccount $parent) {}
}

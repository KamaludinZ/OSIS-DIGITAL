<?php

namespace App\Filament\Resources\Voters\Pages;

use App\Filament\Resources\Voters\VoterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVoter extends CreateRecord
{
    protected static string $resource = VoterResource::class;
}

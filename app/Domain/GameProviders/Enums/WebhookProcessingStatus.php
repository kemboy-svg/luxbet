<?php

namespace App\Domain\GameProviders\Enums;

enum WebhookProcessingStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Rejected = 'rejected';
    case Failed = 'failed';
}

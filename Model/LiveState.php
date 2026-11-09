<?php

namespace Omnishow\Model;

enum LiveState: string
{
    /** Scheduled, not started. */
    case Upcoming = 'upcoming';
    /** On air now. */
    case Live = 'live';
    /** Over: its recording, if any, is a video. */
    case Ended = 'ended';
    /** A scheduled broadcast called off (Twitch's canceled_until). */
    case Cancelled = 'cancelled';
}

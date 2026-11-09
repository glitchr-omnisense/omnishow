<?php

namespace Omnishow\Model;

enum ImageKind: string
{
    case Poster = 'poster';
    case Backdrop = 'backdrop';
    /** A frame of an episode. */
    case Still = 'still';
    case Logo = 'logo';
    /** A video's, a channel's or a playlist's picture at its platform. */
    case Thumbnail = 'thumbnail';
    case Other = 'other';
}

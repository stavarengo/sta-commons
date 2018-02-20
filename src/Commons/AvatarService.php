<?php
/**
 * clap Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace Sta\Commons;

abstract class AvatarService
{
    /**
     * Generates a URI for the service http://avatars.adorable.io
     * While other placeholder services provide purely random images, Adorable Avatars renders a unique image based on
     * the URL. Our service takes your request (with your identifier) and procedurally generates a consistent avatar,
     * just for you.
     *
     * @param string $avatarIdentifier
     *      Any string to identify our avatar. It does not need to be URL encoded.
     *
     * @param bool $pngExtension
     *
     * @return \string
     */
    public static function adorableIo(string $avatarIdentifier, bool $pngExtension = true): string
    {
        return sprintf(
            'https://api.adorable.io/avatars/285/%s%s',
            rawurlencode($avatarIdentifier),
            $pngExtension ? '.png' : ''
        );
    }
}

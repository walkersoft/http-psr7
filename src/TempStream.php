<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http;

class TempStream extends Stream
{

    /**
     * Constructs a temporary stream within the `php://temp` stream.
     */
    public function __construct()
    {
        $stream = fopen('php://temp', 'w+');
        parent::__construct($stream);
    }

}
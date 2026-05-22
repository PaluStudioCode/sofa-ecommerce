<?php

namespace Tests\Unit;

use App\Support\MediaUrl;
use Tests\TestCase;

class MediaUrlTest extends TestCase
{
    public function test_external_media_url_is_returned_as_is_and_local_path_uses_storage_asset(): void
    {
        $external = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

        $this->assertSame($external, MediaUrl::fromPath($external));
        $this->assertSame(asset('storage/products/sofa.jpg'), MediaUrl::fromPath('products/sofa.jpg'));
        $this->assertNull(MediaUrl::fromPath(null));
    }
}

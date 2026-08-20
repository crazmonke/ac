<?php

namespace Tests\Unit;

use App\Services\ContentModerationService;
use Tests\TestCase;

class ContentModerationServiceTest extends TestCase
{
    public function test_it_detects_configured_objectionable_terms(): void
    {
        config(['community.blocked_terms' => '나쁜말,금칙어']);

        $service = app(ContentModerationService::class);

        $this->assertSame('나쁜말', $service->findBlockedTerm('이 문장에는 나 쁜 말이 포함됩니다.'));
        $this->assertNull($service->findBlockedTerm('건전한 커뮤니티 글입니다.'));
    }

}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OpenApiDocsTest extends TestCase
{
    #[Test]
    public function it_renders_swagger_ui(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('swagger-ui', false)
            ->assertSee('/docs/openapi.yaml', false);
    }

    #[Test]
    public function it_serves_the_openapi_yaml(): void
    {
        $this->get('/docs/openapi.yaml')
            ->assertOk()
            ->assertHeader('content-type', 'application/yaml; charset=UTF-8')
            ->assertSee('Fleet Booking API', false)
            ->assertSee('/register', false);
    }
}

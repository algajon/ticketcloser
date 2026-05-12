<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsPageTest extends TestCase
{
    public function test_docs_page_is_public_and_contains_guides(): void
    {
        $response = $this->get('/docs');

        $response->assertOk();
        $response->assertSee('Learn tickIt without getting lost in technical details.');
        $response->assertSee('Get from signup to first live call');
        $response->assertSee('Create an assistant that sounds natural');
    }

    public function test_sitemap_includes_docs_page(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('docs'), false);
    }
}

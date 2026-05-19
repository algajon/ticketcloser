<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMarketingPagesTest extends TestCase
{
    public function test_homepage_contains_new_marketing_copy_and_hub_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('AI phone answering that turns calls into tickets and booked follow-up.');
        $response->assertSee(route('features.index'), false);
        $response->assertSee(route('industries.index'), false);
    }

    public function test_feature_and_industry_pages_are_public(): void
    {
        $featureResponse = $this->get(route('features.show', ['page' => 'ai-phone-answering']));
        $industryResponse = $this->get(route('industries.show', ['page' => 'property-management']));

        $featureResponse->assertOk();
        $featureResponse->assertSee('AI phone answering that captures the details, not just the call.');

        $industryResponse->assertOk();
        $industryResponse->assertSee('AI phone answering for property management and maintenance request intake.');
    }

    public function test_llms_file_lists_core_public_resources(): void
    {
        $response = $this->get(route('llms'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('## Core feature pages');
        $response->assertSee(route('features.show', ['page' => 'ai-phone-answering']), false);
        $response->assertSee(route('industries.show', ['page' => 'property-management']), false);
    }

    public function test_sitemap_includes_feature_and_industry_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('features.index'), false);
        $response->assertSee(route('industries.index'), false);
        $response->assertSee(route('features.show', ['page' => 'ai-phone-answering']), false);
        $response->assertSee(route('industries.show', ['page' => 'property-management']), false);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_endpoint_returns_the_exact_public_contract(): void
    {
        $this->getJson('/api/v1')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' => 'ok',
                    'version' => 'v1',
                ],
            ]);
    }

    public function test_missing_api_route_returns_the_safe_error_contract(): void
    {
        $this->getJson('/api/v1/missing')
            ->assertNotFound()
            ->assertContent('{"error":{"code":"not_found","message":"The requested API resource was not found.","details":{}}}');
    }

    public function test_exact_api_path_with_json_accept_returns_the_safe_error_contract_without_a_debug_trace(): void
    {
        config(['app.debug' => true]);

        $this->get('/api', ['Accept' => 'application/json'])
            ->assertNotFound()
            ->assertContent('{"error":{"code":"not_found","message":"The requested API resource was not found.","details":{}}}')
            ->assertDontSee('trace');
    }

    public function test_api_method_not_allowed_returns_the_safe_error_contract(): void
    {
        $this->postJson('/api/v1')
            ->assertMethodNotAllowed()
            ->assertContent('{"error":{"code":"method_not_allowed","message":"The requested HTTP method is not allowed.","details":{}}}');
    }

    public function test_api_http_exception_returns_the_safe_error_contract(): void
    {
        Route::get('/api/v1/teapot', static fn (): never => abort(418));

        $this->getJson('/api/v1/teapot')
            ->assertStatus(418)
            ->assertContent('{"error":{"code":"http_error","message":"The API request could not be completed.","details":{}}}');
    }

    public function test_web_method_not_allowed_response_remains_html(): void
    {
        $this->post('/')
            ->assertMethodNotAllowed()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');
    }

    public function test_public_api_limiter_returns_the_rate_limited_error_contract(): void
    {
        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/v1')->assertOk();
        }

        $this->getJson('/api/v1')
            ->assertTooManyRequests()
            ->assertContent('{"error":{"code":"rate_limited","message":"Too many requests. Please try again later.","details":{}}}');
    }

    public function test_feature_profile_uses_the_disposable_mysql_service(): void
    {
        $this->assertSame('mysql-test', config('database.connections.mysql.host'));
        $this->assertSame(1, (int) DB::scalar('SELECT 1'));
    }
}

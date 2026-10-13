<?php

namespace Tests\Unit;

use App\Helpers\LogDataMasker;
use PHPUnit\Framework\TestCase;

class LogDataMaskerTest extends TestCase
{
    public function test_sensitive_fields_are_masked_recursively_and_keep_their_names(): void
    {
        $data = ['password' => 'private-password', 'email_verified_token' => 'private-token',
            'nested' => ['apiKey' => 'private-api', 'CLIENT-SECRET' => 'private-secret', 'name' => 'Visible'],
            'payload' => '{"remember_token":"private-remember","title":"Visible"}', 'status' => 'active'];
        $masked = LogDataMasker::mask($data);
        $this->assertSame('***', $masked['password']);
        $this->assertSame('***', $masked['email_verified_token']);
        $this->assertSame(['apiKey' => '***', 'CLIENT-SECRET' => '***', 'name' => 'Visible'], $masked['nested']);
        $this->assertSame(['remember_token' => '***', 'title' => 'Visible'], json_decode($masked['payload'], true));
        $this->assertSame('active', $masked['status']);
        $this->assertStringNotContainsString('private-', json_encode($masked));
    }

    public function test_named_setting_value_is_masked_and_normal_values_are_unchanged(): void
    {
        $this->assertSame(['name' => 'smtpPassword', 'value' => '***'], LogDataMasker::mask(['name' => 'smtpPassword', 'value' => 'private-smtp']));
        $data = ['name' => 'siteTitle', 'value' => 'Visible', 'data' => '{ "title": "Visible" }', 'count' => 0];
        $this->assertSame($data, LogDataMasker::mask($data));
    }
}

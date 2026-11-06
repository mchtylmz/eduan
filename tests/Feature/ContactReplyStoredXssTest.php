<?php

namespace Tests\Feature;

use App\Livewire\Contacts\ContactReplyForm;
use App\Mail\ContactReplyMail;
use App\Models\Contact;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContactReplyStoredXssTest extends TestCase
{
    use DatabaseTransactions;

    public function test_real_database_reply_is_escaped_and_legacy_reply_remains_sandboxed(): void
    {
        Mail::fake();
        $actor = User::factory()->create();
        foreach (['dashboard:access', 'contacts:view'] as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($actor);
        $payload = '<img src=x onerror=window.__contactXss=1>';
        $contact = Contact::create(['locale' => 'nl', 'name' => $payload, 'email' => 'qa@example.invalid', 'phone' => $payload, 'message' => 'Example contact message']);
        (new ContactReplyMail($contact, 'nl', 'Example reply', '<p>Hallo [ad_soyad]; [phone]</p>'))->build();
        $reply = html_entity_decode($contact->fresh()->reply);
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$reply);
        $this->assertSame(0, (new DOMXPath($dom))->query('//script|//*[@onerror or @onload]')->length);
        $this->assertStringContainsString('&lt;img', $reply);
        $contact->update(['reply' => htmlentities('<p>Old reply</p><script>parent.__contactXss=2</script>')]);
        Livewire::test(ContactReplyForm::class, ['contact' => $contact])->assertOk()->assertSee('sandbox', false)
            ->assertSee('referrerpolicy="no-referrer"', false)->assertSee('srcdoc=', false);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }
}

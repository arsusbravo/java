<?php

use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function contactForm(array $overrides = []): array
{
    return [
        'name' => 'Lena',
        'email' => 'lena@mail.test',
        'subject' => 'Ijen',
        'message' => 'Can I visit Ijen crater in the rainy season?',
        ...$overrides,
    ];
}

test('the contact page shows a form and no email address', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertSee('Send message')
        ->assertSee('name="website"', false)
        ->assertDontSee('mailto:', false)
        ->assertDontSee('hello@javasunrise.com');

    $this->get('/')->assertSee(route('contact'), false)->assertDontSee('hello@javasunrise.com');
    $this->get('/about')->assertSee(route('contact'), false)->assertDontSee('mailto:', false);
});

test('a message is saved and the visitor sees a thank you', function () {
    $this->post('/contact', contactForm())
        ->assertRedirect('/contact')
        ->assertSessionHasNoErrors();

    $message = ContactMessage::sole();
    expect($message->only(['name', 'email', 'subject', 'message', 'status']))->toBe([
        'name' => 'Lena', 'email' => 'lena@mail.test', 'subject' => 'Ijen',
        'message' => 'Can I visit Ijen crater in the rainy season?', 'status' => 'new',
    ]);

    $this->get('/contact')->assertSee('Thank you, your message has been sent.');
});

test('invalid messages are refused with a clear reason', function (array $input, string $field) {
    $this->post('/contact', contactForm($input))->assertSessionHasErrors($field);

    expect(ContactMessage::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
    'too short' => [['message' => 'Hi'], 'message'],
    'too long' => [['message' => str_repeat('a', 5001)], 'message'],
    'bot filled the hidden field' => [['website' => 'https://spam.example'], 'website'],
]);

test('one visitor cannot flood the inbox', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/contact', contactForm())->assertRedirect();
    }

    $this->post('/contact', contactForm())->assertTooManyRequests();
    expect(ContactMessage::count())->toBe(5);
});

test('messages are read and handled in the admin', function () {
    $message = ContactMessage::create(contactForm());
    $this->actingAs(User::firstOrFail());

    $this->get('/admin/contact-messages')->assertOk();
    $this->get('/admin/contact-messages/create')->assertNotFound();
    $this->get("/admin/contact-messages/{$message->id}/edit")->assertOk();

    $this->post("/admin/contact-messages/{$message->id}/actions/replied")->assertRedirect();
    expect($message->fresh()->status)->toBe('replied');
});

test('guests cannot read messages', function () {
    $this->get('/admin/contact-messages')->assertRedirect('/login');
});

<?php

namespace Schwartzmj\StatamicAkismet\Akismet;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Forms\Submission;
use Statamic\Facades\File;

class SpamCheck {

    private array $spamCheckData;
    private bool $isSpam;
    private string $akismet_api_key;

    public function __construct(
        private Submission $submission,
        private string $type = 'contact-form',
    )
    {
        $this->spamCheckData = [
            'blog' => config('app.url') ?: 'https://www.wemaketechsimple.com',
            'user_ip' => $submission->get('_user_ip'),
            'user_agent' => $submission->get('_user_agent'),
            'referrer' => $submission->get('_referrer'),
            'comment_type' => $this->type,
            'comment_author' => $submission->get($this->getNameField(), config('app.name') . ' User'), // need to get this from the form. what if form has first & last? we need to probably define it somewhere
            'comment_author_email' => $submission->get('email'), // also must be a field in the form
            'comment_content' => $submission->get('message'), // also must be a field in the form
        ];
        $this->akismet_api_key = config('statamic.akismet.api_key');
        // validate that spamCheckData has all the required fields ?
    }

    private function getNameField()
    {
        $potentialNameFields = collect(['name', 'first_name', 'full_name']);

        return $potentialNameFields->map(function ($field) {
            return $this->submission->has($field) ? $field : null;
        })
            ->filter()
            ->first();
    }

    public function checkIfSpam(): bool {

        $response = Http::asForm()
            ->post('https://' . $this->akismet_api_key . '.rest.akismet.com/1.1/comment-check', $this->spamCheckData);

        if (!$response->ok() ) {
            return $this->handleUnsuccessfulResponse($response);
        }

        $this->isSpam = $response->body() === 'true'; // response returns literal strings 'true' or 'false'

        if ($this->isSpam) {
            $this->handleSpam();
            return true;
        }
        $this->handleNonSpam();
        return false;

    }

    private function handleSpam(): void {
        Log::info('Akismet flagged submission ID ' . $this->submission->id() . ' as spam');
        $this->submission->set('_akismet_spam', true);

        $path = '/_spam/'.$this->submission->form->handle().'/'.$this->submission->id().'.yaml';
        Storage::put(
            $path,
            \Statamic\Facades\YAML::dump($this->submission->data()->all())
        );
    }

    private function getSpamStoragePath(): string {

        return config('statamic.forms.submissions').'/_spam/'.$this->submission->form->handle().'/'.$this->submission->id().'.yaml';
    }

    private function handleNonSpam(): void {
        $this->submission->set('_akismet_spam', false);
    }

    private function handleUnsuccessfulResponse(\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response): bool {
        Log::info('Error in response from Akismet. Form Submission ID: ' . $this->submission->id());
        Log::info('Akismet $response->body(): ', $response->body());
        $this->submission->set('_akismet_error', 'Error in response from Akismet: ', $response->body());
        return true;
    }

    public function submission(): Submission
    {
        return $this->submission;
    }

    public function isSpam(): bool
    {
        return $this->isSpam;
    }
}

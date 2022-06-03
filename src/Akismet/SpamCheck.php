<?php

namespace Schwartzmj\StatamicAkismet\Akismet;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Statamic\Contracts\Forms\Submission;
use Statamic\Facades\File;

class SpamCheck {

    private Collection $spamCheckData;
    private bool $isSpam;
    private string $akismet_api_key;

    public function __construct(
        private Submission $submission,
        private string $type = 'contact-form',
    )
    {
        $this->addRequestDataToSubmission();

        $this->spamCheckData = collect([
            'blog' => config('app.url') ?: 'https://www.wemaketechsimple.com',
            'user_ip' => $submission->get('_user_ip'),
            'user_agent' => $submission->get('_user_agent'),
            'referrer' => $submission->get('_referrer'),
            'comment_type' => $this->type,
            'comment_author' => $this->getSubmissionValueByFieldSearch('name'),
            'comment_author_email' => $this->getSubmissionValueByFieldSearch('email'),
            'comment_content' => $this->getSubmissionValueByFieldSearch(['message', 'body', 'content']),
        ])
            ->filter();
        $hasRequiredFields = $this->validateRequiredFields();
        if (!$hasRequiredFields) {
            throw new Exception('Missing required fields for Akismet check.');
        }
        $this->akismet_api_key = config('statamic.akismet.api_key');

        if (!$this->akismet_api_key) {
            throw new Exception('Missing Akismet API key.');
        }
    }

    public function validateRequiredFields(): bool {
        // Akismet required fields are "blog" and "user_ip"
        // We also want to always include 'comment_type' and 'comment_content'
        $requiredKeys = ['blog','user_ip','comment_type','comment_content'];
        $providedRequiredKeys = $this->spamCheckData->filter(function($value, $key) use ($requiredKeys) {
            $requiredKey = Str::is($requiredKeys, $key);
            if ($requiredKey && $value) {
                return true;
            }
            return false;
        })
            ->keys()
            ->toArray();
        $diff = array_diff($requiredKeys, $providedRequiredKeys);

        return count($diff) === 0;
    }

    private function addRequestDataToSubmission(): void
    {
        $this->submission->set('_user_ip', request()->ip());
        $this->submission->set('_user_agent', request()->userAgent());
        $this->submission->set('_referrer', request()->headers->get('referer'));
    }

    // Checks if the submission has a form field that matches or is similar to the given field name
    // Then returns the field name that exists, or null if no field matches
    // TODO: these getField methods should just become some helper class that can be used in multiple components of statamic / addons
    public function getField(string|array $fieldName): string|null {
        if (is_array($fieldName)) {
            return $this->getFieldByArray($fieldName);
        }
        $submission = collect($this->submission);
        if ($submission->has($fieldName)) {
            return $fieldName;
        }
        return $submission->keys()->first(function($key) use ($fieldName) {
            return Str::contains($key, $fieldName);
         });
    }

    public function getFieldByArray(array $fieldNames): string|null {
        $submission = collect($this->submission);

        $firstMatchedFieldName = collect($fieldNames)->first(function($fieldName) use ($submission) {
            if ($submission->has($fieldName)) {
                return true;
            }
            return $submission->keys()->first(function($key) use ($fieldName) {
                return Str::contains($key, $fieldName);
             });
        });
        if (is_string($firstMatchedFieldName)) {
            return $this->getField($firstMatchedFieldName);
        }
        return null;
    }

    public function getSubmissionValueByFieldSearch(string|array $fieldName): string|null {
        return $this->submission->get($this->getField($fieldName));
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

        $submissionData = $this->submission->data()->all();
        Storage::put(
            $path,
            \Statamic\Facades\YAML::dump($submissionData)
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

    public function spamCheckData(): Collection {
        return $this->spamCheckData;
    }
}

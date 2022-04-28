<?php

namespace Schwartzmj\StatamicAkismet\Listeners;

use Schwartzmj\StatamicAkismet\Akismet\SpamCheck;
use Statamic\Events\FormSubmitted as StatamicFormSubmitted;

class FormSubmitted
{
    private \Statamic\Contracts\Forms\Submission $submission;

    public function handle(StatamicFormSubmitted $event)
    {
        $this->submission = $event->submission;
        $this->addRequestDataToSubmission();

        $spamCheck = new SpamCheck($this->submission);
        $spamCheck->checkIfSpam();

        $isSpam = $spamCheck->isSpam();
        $this->submission = $spamCheck->submission();

        // construct akismet data
        // handle errors and/or set defaults if data not valid (idea: set akismet_error field and have those in another queue? show widget on dashboard if errors? email us?)
        // ask akismet if it is spam or not
        // if not spam, set field akismet_spam: false and return true from event
        // if spam, set field akismet_spam: true and save to separate spam/{handle}/ directory on file system ?

        return !$isSpam; // return true if not spam
    }

    private function addRequestDataToSubmission(): void
    {
        $this->submission->set('_user_ip', request()->ip());
        $this->submission->set('_user_agent', request()->userAgent());
        $this->submission->set('_referrer', request()->headers->get('referer'));
    }

}
// Event::listen(function (FormSubmitted $event) {

//     $potentialNameFields = collect(['name', 'first_name', 'full_name']);

//     $nameField = $potentialNameFields->map(function ($field) use ($event) {
//             return $event->submission->has($field) ? $field : null;
//         })
//         ->filter()
//         ->first();

//     // See for required params, etc: https://akismet.com/development/api/#comment-check
//     $spamCheckData = [
//         'blog' => config('app.url'),
//         'user_ip' => request()->ip(),
//         'user_agent' => request()->userAgent(),
//         'referrer' => request()->headers->get('referer'),
//         'comment_type' => 'contact-form',
//         'comment_author' => $event->submission->get($nameField, config('app.name') . ' User'), // need to get this from the form. what if form has first & last? we need to probably define it somewhere
//         'comment_author_email' => $event->submission->get('email'), // also must be a field in the form
//         'comment_content' => $event->submission->get('message'), // also must be a field in the form
//     ];

//     $validator = Validator::make($spamCheckData, [
//         'blog' => 'required',
//         'user_ip' => 'required',
//         'user_agent' => 'required',
//         'referrer' => 'required',
//         'comment_type' => 'required',
//         'comment_author' => 'required',
//         'comment_author_email' => 'required',
//         'comment_content' => 'required',
//     ]);

//     if ($validator->fails()) {
//         Log::info('Error building Akismet $spamCheckData for submission ID ' . $event->submission->id());
//         $event->submission->set('akismet_error', 'Error building Akismet $spamCheckData');
//         return false;
//     }

//     $akismet_key = '98e4c67569f7';

//     $response = Http::asForm()->post('https://' . $akismet_key . '.rest.akismet.com/1.1/comment-check', $spamCheckData);

//     if ($response->ok()) {

//         $isSpamString = $response->body();

//         if ($isSpamString === 'false') {
//             $event->submission->set('akismet_spam', false);
//             return true;
//         } else {
//             Log::info('Akismet flagged submission ID ' . $event->submission->id() . ' as spam');
//             $event->submission->set('akismet_spam', true);

//             $path = \Statamic\Facades\Path::assemble('spam', $event->submission->form->handle(), $event->submission->id().'.yaml');
//             Storage::put(
//                 $path,
//                 \Statamic\Facades\YAML::dump($event->submission->data())
//             );
//             return false;
//         }
//     } else {
//         Log::info('Error in response from Akismet. Form Submission ID: ' . $event->submission->id());
//         Log::info('Akismet $response->body(): ', $response->body());
//         $event->submission->set('akismet_error', 'Error in response from Akismet: ', $response->body());
//         return true;
//     }
// });

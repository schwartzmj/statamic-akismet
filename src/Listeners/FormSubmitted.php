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

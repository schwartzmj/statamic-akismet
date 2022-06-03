<?php

namespace Schwartzmj\StatamicAkismet\Listeners;

use Schwartzmj\StatamicAkismet\Akismet\SpamCheck;
use Statamic\Events\FormSubmitted as StatamicFormSubmitted;

class FormSubmitted
{


    public function handle(StatamicFormSubmitted $event)
    {
        $spamCheck = new SpamCheck($event->submission);
        $spamCheck->checkIfSpam();

        $isSpam = $spamCheck->isSpam();

        return !$isSpam; // return true if not spam

//        $this->submission = $spamCheck->submission();

        // construct akismet data
        // handle errors and/or set defaults if data not valid (idea: set akismet_error field and have those in another queue? show widget on dashboard if errors? email us?)
        // ask akismet if it is spam or not
        // if not spam, set field akismet_spam: false and return true from event
        // if spam, set field akismet_spam: true and save to separate spam/{handle}/ directory on file system ?

    }



}

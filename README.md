# Statamic Akismet

> Akismet spam detection for Statamic forms.

## How it Works

- Visitor submits form on front-end
- Addon catches it and sends the data to Akismet
- Akismet responds whether the submission is spam or not
- Any submission detected as NOT spam is processed normally (saved, email notification sent if set up, etc.)
- Any submission detected as spam is instead saved in Storage and can be viewed in the CP

## How to Install

``` bash
composer require schwartzmj/statamic-akismet
```

## How to Use

Create a new .env variable called `AKISMET_API_KEY` and enter your Akismet API key.

## Future features / WIP
- Enter API key from front-end
  - Update/Edit
- Better spam entry view
- Allow ability to move from spam back to original form submission, if not spam
- Allow ability to move from original form submission to spam, if spam
- Right now we're guessing at the proper fields, i.e. form needs the following or breaks:
  - Name field called one of: 'name', 'full_name', 'first_name'
  - Message field called 'message'
  - Email field called 'email'
- Should create ability to configure each form or select which forms should be processed, or some notification on the form page if the form is missing any of these fields
- ? Can Akismet function without any of those fields? Maybe we just **require** some sort of 'content' field, and the others are highly recommended but not required.

## Down the road, separate repo:
- Add reCAPTCHA v3 and allow either reCAPTCHA or Akismet, or none

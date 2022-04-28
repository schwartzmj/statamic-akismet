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

Enter your Akismet API key in the published config under `config/statamic/akismet.php`

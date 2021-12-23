Internal tool only. Probably not interesting to you :)

# Technologies

- Bootstrap 5
- FontAwesome 6
- Mustache

# PHP Framework

Develop using the php internal server.

```bash
cd web
php -S localhost:8080
```

Then you should be able to go to http://localhost:8080 and see the site

## URL Layout

http://url/{page}/{action}[/key:value/key2:val2/some:setting/...]

- For each page there is a folder in /web/{page}
- A file from that folder called `controller.php` will be loaded (eg: /home will load /web/home/controller.php and execute whatever is in the function `index()`)
- The action is a function inside the page php file. (eg: /home/login will execute code in /web/home/controller.php function `login`)

All key value options are available from the entire code base via `$GLOBALS['vars']`

## Pages

As mentioned above every page has its own folder in /web/{page}

The page will automatically detect the following files on the example `home` site

- /web/home/home.js - If it exists, it will be appended to the page script and will be run on page load
- /web/home/home.css - If exists, will be loaded to the page only on this site

### Templates

Templates are using [moustache-syntax](https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags) (like handlebars).

If a template is to be loaded from within a page, all variables that are stored in `$this->variables` can be used in the moustache template.

So for example in file /web/page/demo/controller.php function `loadtemplate()` we can use 

```php

```

## Translations

Language files are stored as JSON. Each language has its own file in /translations (eg: /translations/en.json)

On page load the right file will be loaded and parsed and stored in `$GLOBALS['translations']`.

A specific translation can be accessed by it's JSON key name. Eg `$GLOBALS['translations']['Good Morning']`


# Interesting links:

1. Making ICS invites in PHP
https://gist.github.com/jakebellacera/635416/3c81643cc236a5efdf535fcbf3f876eaaa6c4787


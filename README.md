# Saffyre php framework

Saffyre is a small php framework that routes requests to your php files
using a convenient, flexible filename-matching technique. These files,
called "controllers" (as in, model-view-**controller**), don't require any
boiler-plate code, class definitions, route tables, or other fanciness.
Just write your normal php code and `echo` the response.




## Getting started

1. To get started, add Saffyre to your composer.json file:

    ```json
    {
        "require": {
            "alexbrombal/saffyre": "^1.0"
        }
    }
    ```

2. Then, create a php file that will handle all requests that you want Saffyre to deal with. It can be named anything — `index.php` is a good choice:

    index.php:

    ```php
    <?php

    Saffyre\Saffyre::execute(__DIR__ . '/controllers');
    ```

    The first parameter to the `execute` method is the directory where your controller files are located (it is simply a shortcut for a single call to `Saffyre\Controller::registerDirectory()`).

3. In your `.htaccess` file, add the following lines:

    ```
    RewriteEngine On
    RewriteBase /

    RewriteCond %{REQUEST_URI} !^/images [NC]
    RewriteRule .* index.php [QSA,L]
    ```

    In this configuration, all requests whose path *does not* begin with `/images` will be handled by your controller files.

4. Finally, create the directory that you specified in step 2, and your first controller file, `controllers/hello-world.php`:

    ```php
    <?php

    echo "Hello, world!";
    ```

    When you request `http://example.com/hello-world`, you will see "Hello, world!" in your browser.


## Request mapping

Request mapping is *the* core feature of Saffyre and is therefore the
only feature described on this readme page. For more complex topics,
check out the wiki.

The flexibility that Saffyre offers is in how requests are mapped to your controller files.
Mapping happens automatically using a simple filename matching technique.
There is no "route table" or other global configuration like other frameworks require.

Essentially, Saffyre matches as much of the request's path as it can to a file in your controllers directory.
Any additional path segments are available in the file
using `$this->args()`.
If Saffyre is only able to match the request path to a folder,
it looks for a file called `_default.php`.

The following table shows some common examples of how request paths might map
to real php files:

URL request (http://example.com/...)       | Controller file
-------------------------------------------|-------------------------------------
/                                          | `/_default.php`
/about                                     | `/about.php`
/products/widget                           | `/products/widget.php`
/profiles                                  | `/profiles/_default.php`
/profiles/john-smith/contact               | `/profiles/_default.php` <br/> `$this->args(0)` will contain "john-smith" <br/> `$this->args(1)` will contain "contact".
/profiles/create                           | `/profiles/create.php`
/asdf                                      | `/_default.php` <br/> `$this->args(0)` will contain "asdf".





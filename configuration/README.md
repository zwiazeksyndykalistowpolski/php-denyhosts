Configuration
=============

There is a possibility to create multiple configurations.
The configuration can be per environment, website.

### Naming convention

`config.{environmentName}.php`

- {environmentName} characters can include A-Z, a-z, 0-9, -, . or _

### Calling the application with a proper configuration

Let's assume we have `otto.zsp.net.pl` website, and a `config.otto.zsp.net.pl.php` file for it.
We can do a `GET /?env=otto.zsp.net.pl&token=some-token-from-the-config-file` using a HTTP request,
or execute a command from shell: `env=otto.zsp.net.pl php index.php`

### Operating remotely on FTP or SFTP

This project is using a Flysystem library that allows to operate
on files that are not only placed locally but remotely also.

Please take a look if your adapter is supported at this page:
https://flysystem.thephpleague.com/

To use a FTP, SFTP or other remote connection you need to provide
a working object of Flysystem Filesystem.

Example:
```
    // ftp account where is an access to the application root directory (public_html?)
    'filesystem_blocker_root' => '/',
    'filesystem_blocker' => function () {
        return new \League\Flysystem\Filesystem(new \League\Flysystem\Adapter\Ftp([
            'username' => 'my_application',
            'root'     => '/',
            'ssl'      => true,
            'timeout'  => 300,
            'host'     => 'example.org',
            'password' => '',
        ]));
    },

    // ftp account where is an access to access.log
    'filesystem_parser_root' => '/',
    'filesystem_parser' => function () {
        return new \League\Flysystem\Filesystem(new \League\Flysystem\Adapter\Ftp([
            'host'     => 'example.org',
            'root'     => '/',
            'ssl'      => true,
            'timeout'  => 300,
            'username' => 'my_logs_user',
            'password' => '',
        ]));
    },
```

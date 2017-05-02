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

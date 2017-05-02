PhpDenyhosts
============

Inspired by the _denyhosts_ and _fail2ban_ projects created **to secure cheap shared hostings** where is no
access to the shell, but there is still an **access.log** and **Apache htaccess** accessible from PHP side.

#### History of creation

Creation of simple Wordpress blog to allow adding information about pickets, strikes and other direct actions by a regional/brand section of workers union
is an easier way to create an elegant card, a personalized portfolio.

The problem is that Wordpress is very often attacked by various bots, they are brute forcing different parts 
like the administration panel, the login page, xmlrpc just to gain the access and infect and send their shitty spam.

Created originally by [Wolnościowiec team](https://github.com/Wolnosciowiec) for [Związek Syndykalistów Polski](http://zsp.net.pl) (Polish section of [International Workers Association](http://iwa-ait.org/) ).

## Installation

```
# via git
git clone https://github.com/zwiazeksyndykalistowpolski/php-denyhosts.git

# via composer
composer require create-project zwiazeksyndykalistowpolski/phpdenyhosts phpdenyhosts
```

## Configuration

In `configuration` directory there is a possibility to place multiple configuration files
for every domain/project, please use the `config.default.php.example` as an example.

## Good practices

To secure installation of PhpDenyhosts you may want to generate a strong token
in every of your environment. Proposed length is 64 characters.

Other thing - you can place this application in a directory with random prefix or suffix.
Example: `denyhosts_9zbnKILG7e9HnVhW`. 
So the bots would have it more difficult to find out that you are using this project.

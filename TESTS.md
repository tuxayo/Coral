# Setup
## Install composer
`mkdir bin`

`wget -O bin/composer https://getcomposer.org/download/1.2.0/composer.phar && chmod +x bin/composer`

## Install dependencies
`bin/composer install`

# Running the tests
## Provide the url and credentials for your local Coral instance
`BASE_URL=http://localhost/coral/ CORAL_LOGIN=my_login CORAL_PASS="my passphrase" bin/phpunit tests --colors=auto`

# Test architecture
![stack-experimental-php-unit-browser-emulator](https://cloud.githubusercontent.com/assets/2678215/17976916/cbe6317a-6aef-11e6-8e79-46a798bd65b1.png)
[source (ODG format)](https://github.com/Coral-erm/Coral/files/437498/stack-experimental-php-unit-browser-emulator.zip)

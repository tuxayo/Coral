# Setup
## Install composer
`mkdir bin`

`wget -O bin/composer https://getcomposer.org/download/1.2.0/composer.phar && chmod +x bin/composer`

## php.ini: enable the bz2 extension (used to extract PhantomJS)
`extension=bz2.so` on Linux
## Install dependencies
`bin/composer install`

# Running the tests
## 0. Important things to know
As of today, the tests don't run in a separate database.
It's just automating some checks that you would do manually on your local instance.
Nothing more. This means that when a test fails, it might leave some test data which could make fail subsequent test runs.
Therefore manual cleanup might be required.
This is a big limitation, hopefully database separation for the tests will be implemented soon enough.
## 1. Launch PhantomJS in a terminal
`bin/phantomjs vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1280 900`
## 2. Run the test suite (provide the url and credentials for your local Coral instance)
`BASE_URL=http://localhost/coral/ CORAL_LOGIN=my_login CORAL_PASS="my passphrase" bin/phpunit tests --colors=auto`

# Test architecture
![stack-experimental-php-unit-real-browser](https://cloud.githubusercontent.com/assets/2678215/17975819/72dcf522-6aeb-11e6-8bc4-692b9024262e.png)
[source (ODG format)](https://github.com/Coral-erm/Coral/files/437424/stack-experimental-php-unit-real-browser.zip)

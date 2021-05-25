# Codeception acceptance tests

## Prerequisites

+ These acceptance tests expect Silvershop to be installed
+ You have an environment for testing set up and configured
+ You have [installed chromedriver](./#Install-chromedriver)
+ You have [started chromedriver](./#Start-chromedriver)

### Environment

1. Ensure the testing website environment is set up.
1. Add a category page + publish
1. Add at least one product under this category + publish it
1. Add the following values to your project's `.env` file

```shell
CODECEPTION_WEBSITE_URL="https://my.install.host"
CODECEPTION_START_PATH="/category-page/"
```

Where:
+ `CODECEPTION_WEBSITE_URL` is the URL including scheme for the site you are testing
+ `CODECEPTION_START_PATH` is the path to the category page you added above.

### Install chromedriver

Download from: https://chromedriver.chromium.org/downloads

OR

On Linux systems using apt, `apt install chromium-chromedriver` should do the trick. Note that on snap-enabled distros such as Ubuntu, this is a snap package.

[Install from source at https://chromedriver.chromium.org/downloads](https://chromedriver.chromium.org/downloads) if you can't / won't use snaps.

If you install from source, remember to ensure chromium-browser is installed.

#### Test installation

```shell
$ which chromedriver
/usr/bin/chromedriver
$ chromedriver --version
ChromeDriver 90.... etc
$ chromedriver --help
// display commands and options
```

Read the following and make adjustments as required: https://chromedriver.chromium.org/security-considerations



#### Start chromedriver

> wd/hub allows communication when using Selenium

```shell
$ chromedriver --url-base=/wd/hub
Starting ChromeDriver ...... etc
Only local connections are allowed.
Please see https://chromedriver.chromium.org/security-considerations for suggestions on keeping ChromeDriver safe.
ChromeDriver was started successfully.
```

Don't see the above? See Troubleshooting at https://chromedriver.chromium.org/home#h.p_ID_60


## Running tests

Installing this package will allow you to run tests with the version of codeception supported by the version of phpunit installed.

Your project level codeception.dist.yml file should have an include pointing at the tests directory, something like:

```yml
include:
  - vendor/nswdpc/silverstripe-nsw-customerpaymentsplatform/tests/codeception
paths:
  log: /path/to/codeception-logs
```

Then run the tests:

Run codeception from the **project** directory
```shell
./vendor/bin/codecept run
```

To run a specific test file or a test within a test file eg. mySpecificTest, suffix the test name after the test path:

```shell
./vendor/bin/codecept run vendor/nswdpc/silverstripe-nsw-customerpaymentsplatform/tests/codeception/tests/acceptance/PaymentTest.php:mySpecificTest
```

## Output

Codeception outputs debug data like screenshots and failure output to `./tests/_output`

Have a look there to determine what might be going wrong if/when tests fail

## Troubleshooting

+ You get the error `Can't connect to Webdriver at ....` - have you started ChromeDriver ?
+ You get the error `unknown error: no chrome binary at /path/to/chromium-browser` - was Chromium installed and available at the path shown?
+ You get various codeception errors about `codeception/module-****` - make sure these are installed
+ You get DB connection errors - is the DB accessible and are the credentials correct? Check the host is accessible.
+ You get certificate errors - use localhost as the host name or use http:// to help chromedriver work around that

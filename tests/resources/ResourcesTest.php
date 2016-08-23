<?php
require __DIR__ . "/../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;

class ResourcesTest extends TestCase {
	private static $session;
	private static $baseUrl;

	/**
	 * Once before running the tests, logs the user in.
	 * The session will be shared among tests. Each test can count on being
	 * logged in but nothing else. Don't count on the page you begin the test
	 * with.
	 */
	public static function setUpBeforeClass() {
		self::$baseUrl = getenv("BASE_URL");
		$loginId = getenv("CORAL_LOGIN");
		$password = getenv("CORAL_PASS");
		self::assertNotFalse($loginId, "CORAL_LOGIN not in environment variables");
		self::assertNotFalse($password, "CORAL_PASS not in environment variables");

		$driver = new Zumba\Mink\Driver\PhantomJSDriver("http://localhost:8510");
		self::$session = new \Behat\Mink\Session($driver);
		self::$session->start();
		// prevent PhantomJS to keep the last session between two tests runs
		self::$session->reset();
		self::$session->visit(self::$baseUrl . "/auth/");
		$page = self::$session->getPage();
		$page->find("css", "#lang")->selectOption("Français");
		waitForPageToBeReady($page);
		// waitElementInPage("#lang", $page);
		$page->find("css", "#lang")->selectOption("English");
		waitContentInPage("eRM Authentication", $page);
		$page->fillField("loginID", $loginId);
		$page->fillField("password", $password);
		$page->pressButton("loginbutton");

		// check that we are redirected on the main menu (login success)
		self::assertContent("Resources", $page);
		self::assertContent("Licensing", $page);
	}


	public function testResources() {
		self::$session->visit(self::$baseUrl . "/resources/");
		$page = self::$session->getPage();

		// This link is strange, browsers dev tools show that the link itself
		// doesn have an area. That's why it's not directly clickable and one
		// must use click on the inner div. Hopefully "only" the links of the
		// menus are like this.
		$page->find("css", "#newLicense div")->click();
		waitForPageToBeReady($page);

		$page->fillField("titleText", "test resource");
		$page->pressButton("submit");
		waitContentInPage("In Progress", $page);

		self::$session->visit(self::$baseUrl . "/resources/");
		waitForPageToBeReady($page);

		waitContentInPage("test resource", $page);
		$page->clickLink("test resource");
		waitForPageToBeReady($page);

		$page->clickLink("remove resource");
		waitForPageToBeReady($page);
		waitContentInPage("Date Created", $page); // ensures that the list has loaded by Ajax
		// So the next check can't do a false positive (classic trap when asserting that
		// something is not here). Also, be carefull, in this case, the other columns
		// names are also in the search form so checking for those would be useless.

		$this->assertNotContent("test resource", $page);
		file_put_contents("./tests/example_screenshot.jpg",
						  self::$session->getDriver()->getScreenshot());
	}


	/**
	 * @param string $content XPath escaped string
	 */
	private function assertContent($content, $page) {
		$result = $page->find("named", ["content", $content]);
		self::assertNotEquals(NULL, $result, "$content not found on page");
	}

	/**
	 * @param string $content XPath escaped string
	 */
	private function assertNotContent($content, $page) {
		$result = $page->find("named", ["content", $content]);
		$this->assertEquals(NULL, $result, "$content shouldn't have been found on page");
	}
}


/**
 * Wait until the selector matches. Useful to deal with Ajax.
 * @param string $cssSelector
 * @param DocumentElement $page
 */
function waitElementInPage($cssSelector, $page) {
	$isFound = $page->getSession()->
			 wait(5000, "document.readyState === 'complete' && $('$cssSelector').length");
	TestCase::assertTrue($isFound, "element $cssSelector not found");
}


/**
 * Wait until the content is found on the page. Useful to deal with Ajax.
 * @param string $content
 * @param DocumentElement $page
 */
function waitContentInPage($content, $page) {
	$javaScriptExpression = <<<JS
document.evaluate("count(//*[contains(text(),'$content')])",
					document,
					null,
					XPathResult.ANY_TYPE,
					null
				).numberValue;
JS;
	$isFound = $page->getSession()->wait(5000, $javaScriptExpression);
	TestCase::assertTrue($isFound, "content '$content' not found");
}


/**
 * Wait until all page resources loaded + Ajax + callbacks.
 * Not 100% bulletproof but avoids most cases where one would have to wait for
 * a selector or a content to be here before clicking, filling, etc
 * @param DocumentElement $page
 */
function waitForPageToBeReady($page) {
    // What follows is a multiline JS string in a multiline PHP string.
    // Backslashes at end of lines are for the multiline JS string
    // because the multiline PHP string keeps the newlines.
	$javaScriptExpression = <<<JS
eval(" \
setTimeout(function() { window.allEventsFinished = true;}, 0);	\
/* All events: GUI, onLoad, Ajax and maybe more */ \
/* var stored in window because eval() isn't in the global scope so 'var myVar' woudn't work */ \
window.allEventsFinished === true && \
/* Check that page + resources loaded. Which isn't covered by previous check */ \
document.readyState === 'complete'; \
");
/* TODO comment*/
JS;
	$ajaxFinishedBeforeTimeout = $page->getSession()->wait(25000, $javaScriptExpression);
	TestCase::assertTrue($ajaxFinishedBeforeTimeout, "Timeout: some Ajax request is still running");
}


/**
 * Wait until the #div_feedback becomes empty. Meaning that the loading ended.
 * @param string $content
 * @param DocumentElement $page
 */
function waitLoadingEnd($page) {
	$hasLoadingEnded = $page->getSession()->wait(5000, "$('#div_feedback').children().length == 0");
	TestCase::assertTrue($hasLoadingEnded, "Loading should have ended");
}

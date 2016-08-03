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
		self::$session->visit(self::$baseUrl . "/auth/");
		$page = self::$session->getPage();

		$page->fillField("loginID", $loginId);
		$page->fillField("password", $password);
		$page->pressButton("loginbutton");

		// check that we are redirected on the main menu (login success)
		self::assertNotEquals(NULL, $page->find("named", ["content", "eResource Management"]),
							  "Login error, we should have been in the main menu if login succeeded");
	}


	public function testResources() {
		self::$session->visit(self::$baseUrl . "/resources/");
		$page = self::$session->getPage();

		// This link is strange, browsers dev tools show that the link itself
		// doesn have an area. That's why it's not direclty clickable and one
		// must use click on the inner div. Hopefully "only" the links of the
		// menus are like this.
		$page->find("css", "#newLicense div")->click();
		waitElementInPage("#titleText", $page);

		$page->fillField("titleText", "test resource");
		$page->pressButton("progress");  // submit button
		waitElementInPage(".removeResource", $page);

		self::$session->visit(self::$baseUrl . "/resources/");
		waitContentInPage("test resource", $page);

		$page->clickLink("test resource");
		waitElementInPage(".removeResource", $page);

		$page->find("css", ".removeResource")->click();
		waitElementInPage(".dataTable", $page); // wait resource list is here
		file_put_contents("./tests/example_screenshot.jpg", $page->getSession()->getDriver()->getScreenshot());
		$this->assertNotContent("test resource", $page);
	}


	/**
	 * @param string $content XPath escaped string
	 */
	private function assertContent($content, $page) {
		$result = $page->find("named", ["content", $content]);
		$this->assertNotEquals(NULL, $result, "$content not found on page");
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
	$isFound = $page->getSession()->wait(5000, "$('$cssSelector').length");
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
 * Wait until the #div_feedback becomes empty. Meaning that the loading ended.
 * @param string $content
 * @param DocumentElement $page
 */
function waitLoadingEnd($page) {
	$hasLoadingEnded = $page->getSession()->wait(5000, "$('#div_feedback').children().length == 0");
	TestCase::assertTrue($hasLoadingEnded, "Loading should have ended");
}

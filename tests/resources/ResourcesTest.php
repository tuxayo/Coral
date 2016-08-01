<?php
require __DIR__ . "/../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;

class ResourcesTest extends TestCase {
	protected static $goutteClient;
	protected static $baseUrl;

	/**
	 * Once before running the tests, creates a browser emulator (wrapper of an
	 * HTTP request lib + HTML parser, but no JS) and logs the user in.
	 * The browser emulator will be shared among tests. Each test can count on
	 * being logged in but nothing else. Don't count on the page you begin the
	 * test with.
	 */
	public static function setUpBeforeClass() {
		self::$goutteClient = new Goutte\Client();
		self::$baseUrl = getenv("BASE_URL");
		$loginId = getenv("CORAL_LOGIN");
		$password = getenv("CORAL_PASS");
		self::assertNotFalse($loginId, "CORAL_LOGIN not in environment variables");
		self::assertNotFalse($password, "CORAL_PASS not in environment variables");

		$crawler = self::$goutteClient->request("GET", self::$baseUrl . "/auth/");
		$form = $crawler->selectButton("Login")->form();
		$crawler = self::$goutteClient->submit($form, array("loginID" => $loginId, "password" => $password));
		$isOnMainPage = textIsInPage("eResource Management", $crawler);
		self::assertTrue($isOnMainPage); // login successful
	}


	public function testCreateAndDelete() {
		// To find the url, search in the JS or use the browser network tool to see where the request goes.
		$newResourceUrl = self::$baseUrl . "/resources/ajax_processing.php?action=submitNewResource";
		// To find the params, search in the JS or the HTML
		// or use the browser network tool to see the request details.
		// Examining a real request is more reliable as most (all?) of the validation is client side.
		// So guessing the params from the JS/HTML can lead to invalid state.
		self::$goutteClient->request("POST", $newResourceUrl,
							   [
								   "titleText" => "test resource",
								   "resourceFormatID" => "2",
								   "acquisitionTypeID" => "1",
							   ]
		);
		$response = self::$goutteClient->getResponse();
		$this->assertEquals(200, $response->getStatus());

		// check that the resource is in the list ////////////////////////////
		$crawler = self::$goutteClient->request("GET", self::$baseUrl . "/resources/ajax_htmldata.php?action=getSearchResources");
		$this->assertEquals(1, occurencesOfText("test resource", $crawler));

		// delete the resource /////////////////////////////////////////////////
		$resourceRelativeUrl = filterNodesContainingText("test resource", $crawler)->first()->attr("href");
		$regexExtractResourceId = "/.+?(?=\d)(\d*)/";
		// example of the url "resource.php?resourceID=135"
		preg_match($regexExtractResourceId, $resourceRelativeUrl, $matches);
		$resourceId = $matches[1];
		$crawler = self::$goutteClient->request("GET", self::$baseUrl . "/resources/ajax_processing.php?action=deleteResource&resourceID=" . $resourceId);
		$response = self::$goutteClient->getResponse();
		$this->assertEquals(200, $response->getStatus());

		// check that the resource is not anymore in the list ////////////////////////
		$crawler = self::$goutteClient->request("GET", self::$baseUrl . "/resources/ajax_htmldata.php?action=getSearchResources");
		$this->assertFalse(textIsInPage("test resource", $crawler));
	}


	public function testAnotherThing() {
		$this->assertEquals(true, true);
	}
}


// helper functions ////////////////////////////////////////

function textIsInPage($text, $crawler) {
	return occurencesOfText($text, $crawler) != 0;
}


// useful to check if only once
function occurencesOfText($text, $crawler) {
	return filterNodesContainingText($text, $crawler)->count();
}

function filterNodesContainingText($text, $crawler) {
	return $crawler->filterXPath("//*[contains(text(),'$text')]");
}

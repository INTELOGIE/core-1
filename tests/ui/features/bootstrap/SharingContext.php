<?php
/**
* ownCloud
*
* @author Artur Neumann
* @copyright 2017 Artur Neumann info@individual-it.net
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Page\FilesPage;
use Page\SharingDialog;

require_once 'bootstrap.php';

/**
 * SharingContext context.
 */
class SharingContext extends RawMinkContext implements Context
{
	private $filesPage;
	private $sharingDialog;
	private $regularUserName;
	private $regularUserNames;

	public function __construct(FilesPage $filesPage)
	{
		$this->filesPage = $filesPage;
	}

	/**
	 * @Given the file/folder :folder is shared with the user :user
	 */
	public function theFileFolderIsSharedWithTheUser($folder, $user)
	{
		$this->filesPage->waitTillPageIsloaded($this->getSession());
		$this->sharingDialog = $this->filesPage->openSharingDialog(
			$folder, $this->getSession()
		);
		$this->sharingDialog->shareWithUser($user, $this->getSession());
	}

	/**
	 * @Given the share dialog for the file/folder :name is open
	 */
	public function theShareDialogForTheFileFolderIsOpen($name)
	{
		$this->filesPage->waitTillPageIsloaded($this->getSession());
		$this->sharingDialog = $this->filesPage->openSharingDialog(
			$name, $this->getSession()
		);
	}

	/**
	 * @Given I type :input in the share-with-field
	 */
	public function iTypeInTheShareWithField($input)
	{
		$this->sharingDialog->fillShareWithField($input, $this->getSession());
	}

	/**
	 * @Then all users that contain the string :requiredString in their username should be listed in the autocomplete list
	 */
	public function allUsersThatContainTheStringInTheirUsernameShouldBeListedInTheAutocompleteList($requiredString)
	{
		$this->allUsersThatContainTheStringInTheirUsernameShouldBeListedInTheAutocompleteListExcept($requiredString, '');
	}

	/**
	 * @Then all users that contain the string :requiredString in their username should be listed in the autocomplete list except :notToBeListed
	 */
	public function allUsersThatContainTheStringInTheirUsernameShouldBeListedInTheAutocompleteListExcept($requiredString, $notToBeListed)
	{
		$autocompleteUsers = $this->sharingDialog->getAutocompleteUsersList();
		foreach ( $this->regularUserNames as $regularUser ) {
			if (strpos($regularUser, $requiredString) !== false
				&& $regularUser !== $notToBeListed) {
				PHPUnit_Framework_Assert::assertContains(
					$regularUser,
					$autocompleteUsers,
					"'" . $regularUser . "' not in autocomplete list");
			}
		}
		PHPUnit_Framework_Assert::assertNotContains(
			$notToBeListed,
			$this->sharingDialog->getAutocompleteUsersList()
		);
	}

	/**
	 * @Then my own name should not be listed in the autocomplete list
	 */
	public function myOwnNameShouldNotBeListedInTheAutocompleteList()
	{
		PHPUnit_Framework_Assert::assertNotContains(
			$this->regularUserName,
			$this->sharingDialog->getAutocompleteUsersList()
		);
	}

	/**
	 * @Then a tooltip with the text :text should be shown near the share-with-field
	 */
	public function aTooltipWithTheTextShouldBeShownNearTheShareWithField($text)
	{
		PHPUnit_Framework_Assert::assertEquals(
			$text, 
			$this->sharingDialog->getShareWithTooltip()
		);
	}

	/**
	 * @Then the autocomplete list should not be displayed
	 */
	public function theAutocompleteListShouldNotBeDisplayed()
	{
		PHPUnit_Framework_Assert::assertEmpty(
			$this->sharingDialog->getAutocompleteUsersList()
		);
	}

	/**
	 * @BeforeScenario
	 * This will run before EVERY scenario.
	 * It will set the properties for this object.
	 */
	public function before (BeforeScenarioScope $scope)
	{
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$featureContext = $environment->getContext('FeatureContext');
		$this->regularUserNames = $featureContext->getRegularUserNames();
		$this->regularUserName = $featureContext->getRegularUserName();
	}
}

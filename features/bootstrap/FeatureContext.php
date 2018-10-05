<?php
use Behat\Mink\Exception\ResponseTextException;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    /** @var Behat\Gherkin\Node\StepNode */
    private $currentStep = null;
    private $scenarioTokens = null;

    /** @BeforeStep */
    public function beforeStep($event)
    {
        $this->currentStep  = $event->getStep();

        $this->scenarioTokens = null;
        try {
            //trying to save examples to use in @smartStep
            $this->scenarioTokens =
                $event->getFeature()->getScenarios()[0]->getExamples()[0]->getTokens();
        }catch(Throwable $e) {}
    }
    /**
     * Show an animation when waiting for a step
     * @param int $remaning Amount in seconds remaing on wait.
     * @param float $interval in seconds to update animation frame.
     */
    private function spinAnimation($remaining = null, $interval = 0.1) {
        static $frameId = null;
        $currentTime = microtime(true);
        static $lastUpdate = null;

        if($frameId === null) {
            $frameId = 0;
        }

        if($lastUpdate === null) {
            $lastUpdate = $currentTime;
        }

        if($currentTime - $lastUpdate < $interval) {
            return;
        }
        $lastUpdate = $currentTime;

        switch($frameId) {
            default: $frameId = 0;
            case 0: $frame = '|'; break;
            case 1: $frame = '\\'; break;
            case 2: $frame = '--'; break;
            case 3: $frame = '/'; break;

        }
        $frameId++;

        if($this->currentStep !== null) {

            print "'" . $this->currentStep->getText() . "' - ";
        }
        if($remaining !== null) {
            print "$remaining seconds remaining...  ";
        }
        print "$frame             \r";
        flush();
    }


    /**
     * Based on example from http://docs.behat.org/en/v2.5/cookbook/using_spin_functions.html
     *
     * @param callable $lambda The callback that will be called in spin
     * @param int $wait Amount in seconds to spin timeout
     * @return bool
     * @throws Exception
     */
    private function spin (callable $lambda, $wait = 60)
    {
        $startTime = time();
        do{
            try {
                if($lambda($this)) {
                    return true;
                }
            }catch(Exception $e) {
                //do nothing;
            }
            usleep(100000);
            $this->spinAnimation($wait - (time() - $startTime) );
        }while(time() < $startTime + $wait);

        throw new Exception(
            "Timeout: $wait seconds."
        );
    }


    /**
     * @When /^(?:|I )click in element "(?P<element>(?:[^"]|\\")*)"$/
     */
    public function clickInElement($element)
    {
        $element = $this->replacePlaceholdersByTokens($element);
        $session = $this->getSession();
        $locator = $this->fixStepArgument($element);
        $xpath = $session->getSelectorsHandler()->selectorToXpath('css', $locator);
        $element = $this->getSession()->getPage()->find('xpath',$xpath);
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not find element'));
        }

        $element->click();
    }

    private function replacePlaceholdersByTokens($element)
    {
        if (is_array($this->scenarioTokens)) {
            foreach ($this->scenarioTokens as $key => $value) {
                $element = str_replace("<$key>",$value,$element);
            }
        }
        return $element;
    }

    /**
     * @When /^(?:|I )wait for element "(?P<element>(?:[^"]|\\")*)" to appear$/
     * @Then /^(?:|I )should see element "(?P<element>(?:[^"]|\\")*)" appear$/
     * @param $element
     * @throws \Exception
     */
    public function iWaitForElementToAppear($element)
    {
        $this->spin(function(FeatureContext $context) use ($element) {
            try {
                $context->assertElementOnPage($element);
                return true;
            }
            catch(ResponseTextException $e) {
                // NOOP
            }
            return false;
        });
    }

    /**
     * @When /^(?:|I )wait for element "(?P<element>(?:[^"]|\\")*)" to appear, for (?P<wait>(?:\d+)*) seconds$/
     * @param $element
     * @param $wait
     * @throws \Exception
     */
    public function iWaitForElementToAppearForNSeconds($element,$wait)
    {
        $this->spin(function(FeatureContext $context) use ($element) {
            try {
                $context->assertElementOnPage($element);
                return true;
            }
            catch(ResponseTextException $e) {
                // NOOP
            }
            return false;
        },$wait);
    }

    /**
     * @When /^(?:|I )wait for (?P<wait>(?:\d+)*) seconds$/
     * @param $element
     * @param $wait
     * @throws \Exception
     */
    public function iWaitForNSeconds($wait)
    {
        return sleep($wait);
    }

    /**
     * @When /^(?:|I )wait for element "(?P<element>(?:[^"]|\\")*)" to become visible$/
     * @param $element
     * @throws \Exception
     */
    public function iWaitForElementToBecomeVisible($element)
    {
        $session = $this->getSession();

        $locator = $this->fixStepArgument($element);
        $xpath = $session->getSelectorsHandler()->selectorToXpath('css', $locator);
        $element = $this->getSession()->getPage()->find('xpath',$xpath);
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not find element'));
        }

        $this->spin(function() use ($element) {
            try {
                return $element->isVisible();
            }
            catch(ResponseTextException $e) {
                // NOOP
            }
            return false;
        });
    }

    /**
     * @when /^(?:|I )follow the element "(?P<element>(?:[^"]|\\")*)" href$/
     */
    public function iFollowTheElementHref($element)
    {
        $session = $this->getSession();

        $locator = $this->fixStepArgument($element);
        $xpath = $session->getSelectorsHandler()->selectorToXpath('css', $locator);
        $element = $this->getSession()->getPage()->find('xpath',$xpath);
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not find element'));
        }

        $href = $element->getAttribute('href');
        $this->visit($href);
    }

    /**
     * @When /^(?:|I )wait for text "(?P<text>(?:[^"]|\\")*)" to appear$/
     * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" appear$/
     * @param $text
     * @throws \Exception
     */
    public function iWaitForTextToAppear($text)
    {
        $this->spin(function(FeatureContext $context) use ($text) {
            try {
                $context->assertPageContainsText($text);
                return true;
            }
            catch(ResponseTextException $e) {
                // NOOP
            }
            return false;
        });
    }


    /**
     * @When /^(?:|I )wait for text "(?P<text>(?:[^"]|\\")*)" to appear, for (?P<wait>(?:\d+)*) seconds$/
     * @param $text
     * @param $wait
     * @throws \Exception
     */
    public function iWaitForTextToAppearForNSeconds($text,$wait)
    {
        $this->spin(function(FeatureContext $context) use ($text) {
            try {
                $context->assertPageContainsText($text);
                return true;
            }
            catch(ResponseTextException $e) {
                // NOOP
            }
            return false;
        },$wait);
    }

    /**
     * @Given /^I fill in "([^"]*)" with a random email$/
     * @param $element
     * @throws \Exception
     */

    public function iFillInWithARandomEmail($field)
    {
        $field = $this->fixStepArgument($field);
        $value = rand(100, 9999) . "@test.com";
        $this->getSession()->getPage()->fillField($field, $value);
    }


    /**
     * @Given /^document should open in new tab$/
     */
    public function documentShouldOpenInNewTab()
    {
        $session     = $this->getSession();
        $windowNames = $session->getWindowNames();
        if(sizeof($windowNames) < 2){
            throw new \ErrorException("Expected to see at least 2 windows opened");
        }

        //You can even switch to that window
        $session->switchToWindow($windowNames[1]);
    }

    /**
     * Some forms do not have a Submit button just pass the ID
     *
     * @Given /^I submit the form with id "([^"]*)"$/
     */
    public function iSubmitTheFormWithId($arg)
    {
        $node = $this->getSession()->getPage()->find('css', $arg);
        if($node) {
            $this->getSession()->executeScript("jQuery('$arg').submit();");
        } else {
            throw new Exception('Element not found');
        }
    }

    /**
     * @Given /^I use jquery to click on element "([^"]*)"$/
     */
    public function iUseJqueryToClickOnElement($arg)
    {
        $node = $this->getSession()->getPage()->find('css', $arg);
        if($node) {
            $this->getSession()->executeScript("jQuery('$arg').click();");
        } else {
            throw new Exception('Element not found');
        }
    }
}

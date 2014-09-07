<?php
namespace Pharborist;

/**
 * Tests various methods of ParameterTrait.
 */
class ParameterTraitTest extends \PHPUnit_Framework_TestCase {
  public function testHasParameter() {
    /** @var \Pharborist\FunctionDeclarationNode $function */
    $function = Parser::parseSnippet('function foo(stdClass &$a = NULL) { $a = new stdClass(); }');

    $this->assertTrue($function->hasParameter('a'));
    $this->assertTrue($function->hasParameter('$a'));
    $this->assertTrue($function->hasParameter($function->getParameterAtIndex(0)));
    $this->assertFalse($function->hasParameter('b'));
    $blorf = ParameterNode::create('$blorf');
    $this->assertFalse($function->hasParameter($blorf));

    $this->assertTrue($function->hasParameter('a', 'stdClass'));
    $this->assertFalse($function->hasParameter('a', 'Node'));

    $this->assertFalse($function->hasRequiredParameter('a', 'stdClass'));
    $this->assertTrue($function->hasOptionalParameter('a', 'stdClass'));

    $function->getParameterAtIndex(0)->setValue(NULL);
    $this->assertTrue($function->hasRequiredParameter('a', 'stdClass'));
    $this->assertFalse($function->hasOptionalParameter('a', 'stdClass'));
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testHasParamterInvalidArgumentException() {
    Parser::parseSnippet('function foo($bar) {}')->hasParameter(1);
  }
}
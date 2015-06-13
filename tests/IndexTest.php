<?php
namespace Pharborist;

use Pharborist\Index\FileSet;
use Pharborist\Index\Indexer;
use Pharborist\Index\ProjectIndex;

/**
 * Test the indexer.
 */
class IndexTest extends \PHPUnit_Framework_TestCase {

  public function testExample() {
    $baseDir = dirname(__FILE__) . '/index_tests/example';

    // Create index.
    $indexer = new Indexer();
    $index = $indexer->index($baseDir, new FileSet(['src']), TRUE);

    $this->assertTrue($index->classExists('\Example\Person'));
    $this->assertTrue($index->classExists('\Example\Communicator'));
    $this->assertTrue($index->interfaceExists('\Example\Speaker'));
    $this->assertTrue($index->traitExists('\Example\Ni'));
    $this->assertTrue($index->constantExists('\Example\ULTIMATE_ANSWER'));
    $this->assertTrue($index->functionExists('\Example\ask'));

    $class = $index->getClass('\Example\Person');
    $this->assertEquals(['\Example\Communicator'], $class->getExtendedBy());
    $methods = $class->getMethods();
    $this->assertArrayHasKey('__construct', $methods);
    $method = $methods['__construct'];
    $parameters = $method->getParameters();
    $this->assertCount(1, $parameters);
    $parameter = $parameters[0];
    $this->assertEquals('name', $parameter->getName());
    $this->assertEquals(['string'], $parameter->getTypes());
    $this->assertEquals(['void'], $method->getReturnTypes());
    $this->assertArrayHasKey('getName', $methods);
    $method = $methods['getName'];
    $this->assertEquals(['string'], $method->getReturnTypes());
    $properties = $class->getProperties();
    $this->assertArrayHasKey('name', $properties);
    $this->assertEquals(['string'], $properties['name']->getTypes());

    $interface = $index->getInterface('\Example\Speaker');
    $constants = $interface->getConstants();
    $this->assertArrayHasKey('HELLO', $constants);
    $methods = $interface->getMethods();
    $this->assertArrayHasKey('speak', $methods);
    $this->assertEquals(['void'], $methods['speak']->getReturnTypes());

    $function = $index->getFunction('\Example\ask');
    $this->assertEquals(['string'], $function->getReturnTypes());
    $parameters = $function->getParameters();
    $this->assertCount(1, $parameters);
    $parameter = $parameters[0];
    $this->assertEquals('question', $parameter->getName());
    $this->assertEquals(['string'], $parameter->getTypes());

    $class = $index->getClass('\Example\Communicator');
    $this->assertEquals('\Example\Person', $class->getExtends());
    $this->assertEquals(['\Example\PublicSpeaker'], $class->getImplements());
    $this->assertEquals(['\Example\Ni'], $class->getTraits());
    $this->assertTrue($class->hasMethod('getName'));
    $this->assertEquals('\Example\Person', $class->getMethod('getName')->getOwner());
    $this->assertTrue($class->hasMethod('speak'));
    $this->assertEquals('\Example\Communicator', $class->getMethod('speak')->getOwner());
    $this->assertTrue($class->hasMethod('ni'));
    $this->assertEquals('\Example\Ni', $class->getMethod('ni')->getOwner());
    $constants = $class->getConstants();
    $this->assertArrayHasKey('HELLO', $constants);
    $method = $class->getMethod('testTypeHint');
    $parameters = $method->getParameters();
    $this->assertEquals('callable', $parameters[0]->getTypeHint());

    // Load index from filesystem and check against the saved index.
    $index->save($baseDir);
    $loadedIndex = ProjectIndex::load($baseDir);
    $this->assertEquals($index, $loadedIndex);
    ProjectIndex::delete($baseDir);
  }

  public function testTraits() {
    $baseDir = dirname(__FILE__) . '/index_tests/traits';

    // Create index.
    $indexer = new Indexer();
    $index = $indexer->index($baseDir, new FileSet(['src']), TRUE);

    $this->assertTrue($index->classExists('\Example\Base'));
    $this->assertTrue($index->traitExists('\Example\SayWorld'));
    $this->assertTrue($index->classExists('\Example\MyHelloWorld'));
    $class = $index->getClass('\Example\MyHelloWorld');
    $this->assertTrue($class->hasMethod('sayHello'));
    $method = $class->getMethod('sayHello');
    $this->assertEquals('\Example\SayWorld', $method->getOwner());

    $this->assertTrue($index->traitExists('\Example\HelloWorld'));
    $this->assertTrue($index->classExists('\Example\TheWorldIsNotEnough'));
    $class = $index->getClass('\Example\TheWorldIsNotEnough');
    $this->assertTrue($class->hasMethod('sayHello'));
    $method = $class->getMethod('sayHello');
    $this->assertEquals('\Example\TheWorldIsNotEnough', $method->getOwner());

    $this->assertTrue($index->traitExists('\Example\A'));
    $this->assertTrue($index->traitExists('\Example\B'));
    $this->assertTrue($index->classExists('\Example\Talker'));

    $class = $index->getClass('\Example\Talker');
    $methods = $class->getMethods();
    $this->assertArrayHasKey('smallTalk', $methods);
    $this->assertArrayHasKey('bigTalk', $methods);
    $this->assertArrayHasKey('talk', $methods);
    $this->assertEquals('private', $methods['talk']->getVisibility());

    $this->assertTrue($index->classExists('\Example\Person'));
    $this->assertTrue($index->traitExists('\Example\Ni'));
    $this->assertTrue($index->classExists('\Example\KingArthur'));
    $class = $index->getClass('\Example\KingArthur');
    $methods = $class->getMethods();
    $this->assertArrayHasKey('getName', $methods);
    $this->assertEquals('\Example\Person', $methods['getName']->getOwner());
    $this->assertArrayHasKey('ni', $methods);
    $this->assertEquals('\Example\Ni', $methods['ni']->getOwner());
    $this->assertArrayHasKey('sayHello', $methods);
    $this->assertEquals('\Example\KingArthur', $methods['sayHello']->getOwner());
    $this->assertArrayHasKey('quote', $methods);
    $this->assertEquals('\Example\KingArthur', $methods['quote']->getOwner());

    $this->assertTrue($index->classExists('\Example\ClassA'));
    $class = $index->getClass('\Example\ClassA');
    $methods = $class->getMethods();
    $this->assertArrayHasKey('smallTalk', $methods);
    $this->assertEquals('\Example\A', $methods['smallTalk']->getOwner());
    $this->assertArrayHasKey('small', $methods);
    $this->assertEquals('\Example\A', $methods['small']->getOwner());
    $this->assertArrayHasKey('bigTalk', $methods);
    $this->assertEquals('\Example\A', $methods['bigTalk']->getOwner());
    $this->assertArrayHasKey('big', $methods);
    $this->assertEquals('\Example\A', $methods['big']->getOwner());

    $this->assertTrue($index->classExists('\Example\PrivateHelloWorld'));
    $class = $index->getClass('\Example\PrivateHelloWorld');
    $this->assertEquals('private', $class->getMethod('sayHello')->getVisibility());

    // Load index from filesystem and check against the saved index.
    $index->save($baseDir);
    $loadedIndex = ProjectIndex::load($baseDir);
    $this->assertEquals($index, $loadedIndex);
    ProjectIndex::delete($baseDir);
  }

  public function testRevisions() {
    $indexer = new Indexer();

    // Load revision 1.
    $rev1 = dirname(__FILE__) . '/index_tests/rev1';
    $index = $indexer->index($rev1, new FileSet(['src']), TRUE);

    $this->assertTrue($index->interfaceExists('\Example\StringObject'));
    $this->assertTrue($index->traitExists('\Example\ObjectUtil'));
    $this->assertTrue($index->classExists('\Example\Base'));
    $this->assertTrue($index->classExists('\Example\ParentClass'));
    $this->assertTrue($index->classExists('\Example\ChildClass'));
    $class = $index->getClass('\Example\ChildClass');
    $this->assertEquals(['\Example\Base', '\Example\ParentClass'], $class->getParents());

    $files = $index->getFiles();
    $parentClassHash = $files['src/ParentClass.php']->getHash();
    $childClassHash = $files['src/ChildClass.php']->getHash();

    // Copy index to revision 2.
    $rev2 = dirname(__FILE__) . '/index_tests/rev2';
    $index->save($rev2);

    // Load revision 2.
    $index = $indexer->index($rev2);

    $this->assertTrue($index->interfaceExists('\Example\StringObject'));
    $this->assertTrue($index->traitExists('\Example\ObjectUtil'));
    $this->assertFalse($index->classExists('\Example\Base'));
    $this->assertTrue($index->classExists('\Example\BaseObject'));
    $this->assertTrue($index->classExists('\Example\ParentClass'));
    $this->assertTrue($index->classExists('\Example\ChildClass'));
    $class = $index->getClass('\Example\ChildClass');
    $this->assertEquals(['\Example\BaseObject', '\Example\ParentClass'], $class->getParents());
    $this->assertTrue($class->hasMethod('sayHello'));

    $files = $index->getFiles();
    $this->assertNotEquals($parentClassHash, $files['src/ParentClass.php']->getHash());
    $this->assertEquals($childClassHash, $files['src/ChildClass.php']->getHash());

    ProjectIndex::delete($rev2);
  }

  public function testErrors() {
    $baseDir = dirname(__FILE__) . '/index_tests/errors';

    // Create index.
    $indexer = new Indexer();
    $index = $indexer->index($baseDir, new FileSet(['src']), TRUE);
    $index->save($baseDir);

    $this->assertEquals([
      'Declaration of \Example\SayGreet::say() must be compatible with \Example\SayHello::say() at src/Class.php:26',
      'Declaration of \Example\SayGreet::say() must be compatible with \Example\Say::say() at src/Class.php:26',
      'Trait property \Example\TraitProperty::$p defines the same property as \Example\PropertyB::$p at src/Class.php:42',
      'Trait property \Example\TraitA::$p conflicts with inherited property \Example\ClassTraitA::$p at src/Class.php:59',
      'Class \Example\TestY does not implement method \Example\Say::say() at src/Class.php:69',
      'Declaration of \Example\ExporterParameter::export() must be compatible with \Example\Exporter::export() at src/Class.php:76',
      'Cannot inherit previously-inherited or override constant MSG from interface \Example\InterfaceA at src/Interface.php:8',
      'Cannot inherit previously-inherited or override constant MSG from interface \Example\InterfaceA at src/Interface.php:12',
      'Cannot inherit previously-inherited or override constant MSG from interface \Example\InterfaceC at src/Interface.php:20',
      'Cannot inherit previously-inherited or override constant MSG from interface \Example\InterfaceC at src/Interface.php:24',
      'Declaration of \Example\InterfaceY::say() must be compatible with \Example\InterfaceX::say() at src/Interface.php:34',
      'Declaration of \Example\ConflictInterfaceMethods::say() must be compatible with \Example\InterfaceX::say() at src/Interface.php:37',
      'Class \Example\Missing extends missing class \Example\MissingClass at src/Missing.php:4',
      'Class \Example\Missing implements missing interface \Example\MissingInterface at src/Missing.php:4',
      'Class \Example\Missing uses missing trait \Example\MissingTrait at src/Missing.php:4',
      'Trait \Example\T uses missing trait \Example\MissingTrait at src/Missing.php:8',
      'Interface \Example\I extends missing interface \Example\MissingInterface at src/Missing.php:12',
      'Class \Example\Communicator does not implement method \Example\Speaker::speak() at src/Missing.php:20',
      'Class \Example\MissingAbstract does not implement method \Example\AbstractClass::test() at src/Missing.php:26',
      'Error at line 4:17 in file src/Parse.php: expected {',
      'Trait property \Example\B::$letter defines the same property \Example\A::$letter at src/Trait.php:32',
      'Trait method \Example\D::say has not been applied because it collides with \Example\C::say at src/Trait.php:32',
      'Trait alias conflictMethod at src/Trait.php:35 conflicts with existing alias at src/Trait.php:34',
      'Trait precedence at src/Trait.php:43 conflicts with existing rule at src/Trait.php:42',
      'Required trait \Example\C wasn\'t added to trait \Example\MissingRequiredTrait at src/Trait.php:49',
      'Required trait \Example\A wasn\'t added to trait \Example\AnotherMissingRequiredTrait at src/Trait.php:55',
      'A precedence rule was defined for \Example\A::say but this method does not exist at src/Trait.php:55',
      'Trait method \Example\D::say has not been applied because it collides with \Example\C::say at src/Trait.php:59',
      'An alias was defined for \Example\C::missingMethod but this method does not exist at src/Trait.php:65',
      'Trait property \Example\E::$letter conflicts with existing property \Example\A::$letter at src/Trait.php:73',
      'Trait property \Example\E::$letter conflicts with existing property \Example\PropertyVisibilityConflict::$letter at src/Trait.php:77',
      'Declaration of \Example\SayBase::say() must be compatible with \Example\Base::say() at src/Trait.php:95',
    ], $index->getErrors());

    $class = $index->getClass('\Example\SayHello');
    $this->assertEmpty($class->getTraitMethods());

    $method = $class->getMethod('say');
    $parameters = $method->getParameters();
    $parameter = $parameters[0];
    $this->assertEquals(['string'], $parameter->getTypes());

    $method = $index->getClass('\Example\DefaultValue')->getMethod('say');
    $this->assertEquals("'hello'", $method->getParameters()[0]->getDefaultValue());


    // Load index from filesystem and check against the saved index.
    $index->save($baseDir);
    $loadedIndex = ProjectIndex::load($baseDir);
    $this->assertEquals($index, $loadedIndex);
    ProjectIndex::delete($baseDir);
  }

  public function testDocTypes() {
    $baseDir = dirname(__FILE__) . '/index_tests/doc';

    // Create index.
    $indexer = new Indexer();
    $index = $indexer->index($baseDir, new FileSet(['src']), TRUE);

    $class = $index->getClass('\Example\TestSay');
    $method = $class->getMethod('say');
    $this->assertEquals(['string'], $method->getReturnTypes());
  }

}

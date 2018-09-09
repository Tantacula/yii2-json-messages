<?php
declare(strict_types=1);
namespace yii\i18n;

use PHPUnit\Framework\{TestCase};

/**
 * Tests the features of the `yii\i18n\JsonMessageSource` class.
 */
class JsonMessageSourceTest extends TestCase {

  /**
   * Tests the `JsonMessageSource::flatten
   * @test
   */
  function testFlatten(): void {
    $flatten = function($array) {
      return $this->flatten($array);
    };

    // It should merge the keys of a multidimensional array.
    $model = new JsonMessageSource;
    assertThat($flatten->call($model, []), equalTo([]);
    assertThat($flatten->call($model, ['foo' => 'bar', 'baz' => 'qux']), equalTo(['foo' => 'bar', 'baz' => 'qux']);
    assertThat($flatten->call($model, ['foo' => ['bar' => 'baz']]), equalTo(['foo.bar' => 'baz']);

    $source = [
      'foo' => 'bar',
      'bar' => ['baz' => 'qux'],
      'baz' => ['qux' => [
        'foo' => 'bar',
        'bar' => 'baz'
      ]]
    ];

    assertThat($flatten->call($model, $source), equalTo([
      'foo' => 'bar',
      'bar.baz' => 'qux',
      'baz.qux.foo' => 'bar',
      'baz.qux.bar' => 'baz'
    ]);

    // It should allow different nesting separators.
    $source = [
      'foo' => 'bar',
      'bar' => ['baz' => 'qux'],
      'baz' => ['qux' => [
        'foo' => 'bar',
        'bar' => 'baz'
      ]]
    ];

    $model = new JsonMessageSource(['nestingSeparator' => '/']);
    assertThat($flatten->call($model, $source), equalTo([
      'foo' => 'bar',
      'bar/baz' => 'qux',
      'baz/qux/foo' => 'bar',
      'baz/qux/bar' => 'baz'
    ]);

    $model = new JsonMessageSource(['nestingSeparator' => '->']);
    assertThat($flatten->call($model, $source), equalTo([
      'foo' => 'bar',
      'bar->baz' => 'qux',
      'baz->qux->foo' => 'bar',
      'baz->qux->bar' => 'baz'
    ]);
  }

  /**
   * Tests the `JsonMessageSource::getMessageFilePath
   * @test
   */
  function testGetMessageFilePath(): void {
    $getMessageFilePath = function($category, $language) {
      return $this->getMessageFilePath($category, $language);
    };

    // It should return the proper path to the message file.
    $model = new JsonMessageSource(['basePath' => '@root/test/fixtures']);
    $messageFile = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/fixtures/fr/messages.json');
    assertThat($getMessageFilePath->call($model, 'messages', 'fr'), equalTo($messageFile);

    // It should should support different file extensions.
    $model = new JsonMessageSource(['basePath' => '@root/test/fixtures', 'fileExtension' => 'json5']);
    $messageFile = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/fixtures/fr/messages');
    assertThat($getMessageFilePath->call($model, 'messages', 'fr'), equalTo("$messageFile.json5");
  }

  /**
   * Tests the `JsonMessageSource::loadMessagesFromFile
   * @test
   */
  function testLoadMessagesFromFile(): void {
    $loadMessagesFromFile = function($messageFile) {
      return $this->loadMessagesFromFile($messageFile);
    };

    // It should properly load the JSON source and parse it as array.
    $model = new JsonMessageSource(['basePath' => '@root/test/fixtures', 'enableNesting' => true]);
    $messageFile = \Yii::getAlias("{$model->basePath}/fr/messages.json");
    assertThat($loadMessagesFromFile->call($model, $messageFile), equalTo([
      'Hello World!' => 'Bonjour le monde !',
      'foo.bar.baz' => 'FooBarBaz'
    ]);

    // It should enable proper translation of source strings.
    $model = new JsonMessageSource(['basePath' => '@root/test/fixtures', 'enableNesting' => true]);
    assertThat($model->translate('messages', 'Hello World!', 'fr'), 'Bonjour le monde !');
    assertThat($model->translate('messages', 'foo.bar.baz', 'fr'), 'FooBarBaz');

    $model->nestingSeparator = '/';
    assertThat($model->translate('messages', 'foo/bar/baz', 'fr'), 'FooBarBaz');
  }

  /**
   * Tests the `JsonMessageSource::parseMessages
   * @test
   */
  function testParseMessages(): void {
    $parseMessages = function($messageData) {
      return $this->parseMessages($messageData);
    };

    // It should parse a JSON file as a hierarchical array.
    $model = new JsonMessageSource(['basePath' => '@root/test/fixtures', 'enableNesting' => true]);
    $messages = $parseMessages->call($model, file_get_contents(\Yii::getAlias("{$model->basePath}/fr/messages.json")));
    assertThat($messages)->equal([
      'Hello World!' => 'Bonjour le monde !',
      'foo' => ['bar' => ['baz' => 'FooBarBaz']]
    ]);

    // It should parse an invalid JSON file as an empty array.
    $model = new JsonMessageSource(['basePath' => '@root/test/fixtures']);
    assertThat($parseMessages->call($model, ''), isEmpty());
  }
}

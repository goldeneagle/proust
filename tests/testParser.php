<?php
/*
 * Proust - Mustache PHP Compiler - Test the Parser class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Proust.php");

class TestParser extends UnitTestCase {
  function assertStartsWith($str, $prefix) {
    $this->assertEqual(substr($str, 0, strlen($prefix)), $prefix);
  }

  public function setUp() {
    $this->p = new Proust\Parser();
  }
  
  public function testEmpty() {
    $res = $this->p->parse("");
    $this->assertEqual($res, array(":multi"));
  }

  public function testComment() {
    $res = $this->p->parse("{{!foobar comment}}");
    $this->assertEqual($res, array(":multi"));
    $res = $this->p->parse("{{!foobar comment}}{{!foobar comment}}");
    $this->assertEqual($res, array(":multi"));
  }

  public function testRawText() {
    $res = $this->p->parse("foo");
    $this->assertEqual($res, array(":multi", array(":static", "foo")));

    $res = $this->p->parse("{{!comment}}foo");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo")));
    $res = $this->p->parse("{{!comment}}foo{{!comment}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo")));
  }

  public function testSimpleProust() {
    $res = $this->p->parse("{{foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo")));
  }

  public function testTwoTags() {
    $res = $this->p->parse("{{foo}}{{bla}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla")));
  }

  public function testTwoTagsComment() {
    $res = $this->p->parse("{{foo}}{{!comment}}{{bla}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla")));
  }

  public function testTagText() {
    $res = $this->p->parse("text{{foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo")));

    $res = $this->p->parse("text{{foo}}text");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text")
                                   ));

    $res = $this->p->parse("text{{foo}}{{bla}}text");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla"),
                                   array(":static", "text")
                                   ));

    $res = $this->p->parse("text{{foo}}text2{{bla}}text");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text2"),
                                   array(":mustache", ":etag", "bla"),
                                   array(":static", "text")
                                   ));
  }

  public function testUnclosedTag() {
    try {
      $res = $this->p->parse("{{foo");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed tag");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->parse("{{foo{{bla}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed tag");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->parse("{{fo{o}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed tag");
      $this->assertTrue(true);
    }
    
  }

  public function testIllegalContent() {
    try {
      $res = $this->p->parse("{{ #fo#o}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Illegal content in tag");
      $this->assertTrue(true);
    }
  }

  public function testUnclosedSection() {
    try {
      $res = $this->p->parse("{{#foo}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }
    
    try {
      $res = $this->p->parse("{{#foo}}bla");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->parse("{{#foo}}{{#bla}}{{/bla}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }
    try {
      $res = $this->p->parse("{{#foo}}{{#foo}}{{/foo}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->parse("{{#foo}}{{#bla}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section bla");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->parse("{{#foo}}{{/bla}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->parse("{{bla}}{{#foo}}{{/bla}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }
  }

  public function testSimpleSection() {
    $res = $this->p->parse("{{#foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi"), 8, 8)));

    $res = $this->p->parse("{{#foo}}text{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi",
                                                                               array(":static", "text")),
                                         8, 12)));

    $res = $this->p->parse("{{#foo}}{{foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi",
                                                                               array(":mustache", ":etag", "foo")),
                                         8, 15)));

    $res = $this->p->parse("{{^foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":inverted_section", "foo", array(":multi"))));

    $res = $this->p->parse("{{^foo}}text{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":inverted_section", "foo", array(":multi",
                                                                                        array(":static", "text")))));

    $res = $this->p->parse("{{^foo}}{{foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":inverted_section", "foo", array(":multi",
                                                                                        array(":mustache", ":etag", "foo")))));
  }

  public function testNestedSection() {
    $res = $this->p->parse("{{#foo}}{{#bla}}{{/bla}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi",
                                                                               array(":mustache", ":section", "bla", array(":multi"), 16, 16)),
                                         8, 24)));
  }

  public function testUnopenedSection() {
    try {
      $res = $this->p->parse("{{/foo}}");
      $this->assertFalse(true);
    } catch (Proust\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Closing unopened section foo");
      $this->assertTrue(true);
    }
  }

  public function testCtag() {
    $res = $this->p->parse("{{=[[ ]]}}[[foo]]text{{text}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":tag_change", "[[", "]]"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text{{text}}")));

    /* create new proust to reset ctag and otag. */
    $this->p = new Proust\Parser();
    $res = $this->p->parse("{{=[[ ]]}}[[foo]]text[[={{ }}]]{{text}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":tag_change", "[[", "]]"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text"),
                                   array(":mustache", ":tag_change", "{{", "}}"),
                                   array(":mustache", ":etag", "text")));
  }

  public function testPartial() {
    $res = $this->p->parse("{{>partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial", "")));
    $res = $this->p->parse("{{<partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial", "")));
    $res = $this->p->parse("{{<partial/test}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial/test", "")));

    $res = $this->p->parse("  {{>partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial", "  ")));

    $res = $this->p->parse("foo  {{>partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo  "),
                                   array(":mustache", ":partial", "partial", "")));

    $res = $this->p->parse("foo  \n  {{>partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo  "),
                                   array(":newline"),
                                   array(":mustache", ":partial", "partial", "  ")));

    $res = $this->p->parse("foo  \n\t\t  {{>partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo  "),
                                   array(":newline"),
                                   array(":mustache", ":partial", "partial", "\t\t  ")));
  }

  public function testUtag() {
    $res = $this->p->parse("{{{fresh}}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":utag", "fresh")));
    $res = $this->p->parse("{{&unescaped_stuff}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":utag", "unescaped_stuff")));
  }

  public function testLong() {
    $res = $this->p->parse('Hello {{name}}
You have just won ${{value}}!
{{#in_ca}}
Well, ${{taxed_value}}, after taxes.
{{/in_ca}}');
    $this->assertEqual($res,
                       array(":multi",
                             array(":static", "Hello "),
                             array(":mustache", ":etag", "name"),
                             array(":newline"),
                             array(":static", "You have just won $"),
                             array(":mustache", ":etag", "value"),
                             array(":static", "!"),
                             array(":newline"),
                             array(":mustache", ":section", "in_ca",
                                   array(":multi",
                                         array(":static", "Well, $"),
                                         array(":mustache", ":etag", "taxed_value"),
                                         array(":static", ", after taxes.")),
                                   55, 93)));
  }

  public function testWhitespace() {
    $res = $this->p->parse(" ");
    $this->assertEqual($res, array(":multi", array(":static", " ")));

    $res = $this->p->parse("\r\n");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "\r"),
                                   array(":newline")));

    $res = $this->p->parse("\r\n\r\n");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "\r"),
                                   array(":newline"),
                                   array(":static", "\r"),
                                   array(":newline")));
  }

  public function testWhitespaceTag() {
    $res = $this->p->parse("\r\n{{tag}}\r\n"); 
    $this->assertEqual($res, array(":multi",
                                   array(":static", "\r"),
                                   array(":newline"),
                                   array(":mustache", ":etag", "tag"),
                                   array(":static", "\r"),
                                   array(":newline")));
    $res = $this->p->parse("\r\n  {{tag}}\r\n"); 
    $this->assertEqual($res, array(":multi",
                                   array(":static", "\r"),
                                   array(":newline"),
                                   array(":static", "  "),
                                   array(":mustache", ":etag", "tag"),
                                   array(":static", "\r"),
                                   array(":newline")));
  }

  public function testWhitespaceComment() {
    $res = $this->p->parse("{{! comment }}   ");
    $this->assertEqual($res, array(":multi"));

    $res = $this->p->parse("   {{! comment }}   ");
    $this->assertEqual($res, array(":multi"));
    $res = $this->p->parse("   {{! comment }}   \n");
    $this->assertEqual($res, array(":multi"));

    $res = $this->p->parse("   {{! comment }}   \n  ");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "  ")));

    $res = $this->p->parse("\r\n   {{! comment }}   \n");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "\r"),
                                   array(":newline")));

    $res = $this->p->parse("\r\nfoo {{! comment}} bla\r\n blorg\r\n");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "\r"),
                                   array(":newline"),
                                   array(":static", "foo  bla\r"),
                                   array(":newline"),
                                   array(":static", " blorg\r"),
                                   array(":newline")));
 }

  public function testWhitespaceSection() {
  }

  public function testWhitespacePartial() {
  }
  
};

?>

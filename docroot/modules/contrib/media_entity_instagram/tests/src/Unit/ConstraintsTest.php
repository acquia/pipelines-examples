<?php

namespace Drupal\Tests\media_entity_instagram\Unit;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\media_entity_instagram\Plugin\Validation\Constraint\InstagramEmbedCodeConstraint;
use Drupal\media_entity_instagram\Plugin\Validation\Constraint\InstagramEmbedCodeConstraintValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests media_entity_instagram constrains.
 *
 * @group media_entity
 */
class ConstraintsTest extends UnitTestCase {

  /**
   * Tests InstagramEmbedCode constraints.
   *
   * @covers \Drupal\media_entity_instagram\Plugin\Validation\Constraint\InstagramEmbedCodeConstraint
   * @covers \Drupal\media_entity_instagram\Plugin\Validation\Constraint\InstagramEmbedCodeConstraintValidator
   *
   * @dataProvider embedCodeProvider
   */
  public function testInstagramEmbedCodeConstraint($embed_code, $expected_violation_count) {
    // Check message in constraint.
    $constraint = new InstagramEmbedCodeConstraint();
    $this->assertEquals(addslashes('Not valid Instagram URL/Embed code.'), addslashes($constraint->message), 'Correct constraint message found.');

    $execution_context = $this->getMockBuilder('\Drupal\Core\TypedData\Validation\ExecutionContext')
      ->disableOriginalConstructor()
      ->getMock();

    if ($expected_violation_count) {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation')
        ->with($constraint->message);
    }
    else {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation');
    }

    $validator = new InstagramEmbedCodeConstraintValidator();
    $validator->initialize($execution_context);

    $definition = $this->getMock(ComplexDataDefinitionInterface::class);
    $definition->method('getPropertyDefinitions')->willReturn([]);

    $data = new StringLongItem($definition);
    $data->set('value', $embed_code);
    $validator->validate($data, $constraint);
  }

  /**
   * Provided test data for testInstagramEmbedCodeConstraint().
   */
  public function embedCodeProvider() {
    return [
      'valid URL' => ['https://instagram.com/p/2hi3DVDgsN/', 0],
      'valid embed code' => ['<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="5" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:50% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAAGFBMVEUiIiI9PT0eHh4gIB4hIBkcHBwcHBwcHBydr+JQAAAACHRSTlMABA4YHyQsM5jtaMwAAADfSURBVDjL7ZVBEgMhCAQBAf//42xcNbpAqakcM0ftUmFAAIBE81IqBJdS3lS6zs3bIpB9WED3YYXFPmHRfT8sgyrCP1x8uEUxLMzNWElFOYCV6mHWWwMzdPEKHlhLw7NWJqkHc4uIZphavDzA2JPzUDsBZziNae2S6owH8xPmX8G7zzgKEOPUoYHvGz1TBCxMkd3kwNVbU0gKHkx+iZILf77IofhrY1nYFnB/lQPb79drWOyJVa/DAvg9B/rLB4cC+Nqgdz/TvBbBnr6GBReqn/nRmDgaQEej7WhonozjF+Y2I/fZou/qAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <a href="https://instagram.com/p/2hi3DVDgsN/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Once upon a time there were 10 mysterious golden tickets.... #drupalcon</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A photo posted by DrupalCon (@drupalcon) on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="2015-05-11T02:01:51+00:00">May 10, 2015 at 7:01pm PDT</time></p></div></blockquote><script async defer src="//platform.instagram.com/en_US/embeds.js"></script>', 0],
      'invalid URL' => ['https://instagram.com/ppp/2hi3DVDgsN/', 1],
    ];
  }

}

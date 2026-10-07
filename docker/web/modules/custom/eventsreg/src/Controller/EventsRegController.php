<?php

namespace Drupal\eventsreg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\eventsreg\Utility\DescriptionTemplateTrait;

/**
 * Controller routines for page example routes.
 */
class EventsRegController extends ControllerBase {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'eventsreg';
  }

  /**
   * Constructs a simple page.
   *
   * The router _controller callback, maps the path
   * 'examples/page-example/simple' to this method.
   *
   * _controller callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */

  public function simple() {
    return [
      '#markup' => '<p>' . $this->t('Simple page: The quick brown fox jumps over the lazy dog.') . '</p>',
    ];
  }

  public function organization() {
    return [
      '#markup' => '<p>' . $this->t('An organization page') . '</p>',
    ];
  }

  public function site() {
    return [
      '#markup' => '<p>' . $this->t('You hit a site') . '</p>',
    ];
  }

  public function CurrentEvents() {
    return [
      '#markup' => '<p>' . $this->t('Current Events') . '</p>',
    ];
  }

	public function getCurrentEvents() {

		\Drupal::service('page_cache_kill_switch')->trigger();
		
	    $build = [];

	    // CSS and JavaScript libraries can be attached to elements in a renderable
	    // array. This way, if the element ends up being rendered and displayed you
	    // know for sure the CSS/JavaScript will also be included. But, if for
	    // some reason the element isn't ever rendered then Drupal can skip the
	    // unnecessary extra files.
	    //
	    // Learn more about attaching CSS and JavaScript libraries with the
	    // #attached property here:
	    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21theme.api.php/group/theme_render/#sec_attached
   
	    $build['#attached'] = [
	      'library' => [
	        'eventsreg/current-events.library',
	      ],
	    ];

		$build['#markup'] = getCurrentEventsHTML();
		$build['#cache'] =	['max-age' => 0,];	 //Set cache for 0 seconds.

		return $build;
	}

  public function getUserEvents() {

    \Drupal::service('page_cache_kill_switch')->trigger();
    
      $build = [];

      // CSS and JavaScript libraries can be attached to elements in a renderable
      // array. This way, if the element ends up being rendered and displayed you
      // know for sure the CSS/JavaScript will also be included. But, if for
      // some reason the element isn't ever rendered then Drupal can skip the
      // unnecessary extra files.
      //
      // Learn more about attaching CSS and JavaScript libraries with the
      // #attached property here:
      // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21theme.api.php/group/theme_render/#sec_attached
   
      $build['#attached'] = [
        'library' => [
          'eventsreg/events.library',
        ],
      ];

    $build['#markup'] = getUserEventsHTML();
    $build['#cache'] =  ['max-age' => 0,];   //Set cache for 0 seconds.

    return $build;
  }

  /**
   * A more complex _controller callback that takes arguments.
   *
   * This callback is mapped to the path
   * 'examples/page-example/arguments/{first}/{second}'.
   *
   * The arguments in brackets are passed to this callback from the page URL.
   * The placeholder names "first" and "second" can have any value but should
   * match the callback method variable names; i.e. $first and $second.
   *
   * This function also demonstrates a more complex render array in the returned
   * values. Instead of rendering the HTML with theme('item_list'), content is
   * left un-rendered, and the theme function name is set using #theme. This
   * content will now be rendered as late as possible, giving more parts of the
   * system a chance to change it if necessary.
   *
   * Consult @link http://drupal.org/node/930760 Render Arrays documentation
   * @endlink for details.
   *
   * @param string $first
   *   A string to use, should be a number.
   * @param string $second
   *   Another string to use, should be a number.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  public function arguments($first, $second) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if (!is_numeric($first) || !is_numeric($second)) {
      // We will just show a standard "access denied" page in this case.
      throw new AccessDeniedHttpException();
    }

    $list[] = $this->t("First number was @number.", ['@number' => $first]);
    $list[] = $this->t("Second number was @number.", ['@number' => $second]);
    $list[] = $this->t('The total was @number.', ['@number' => $first + $second]);

    $render_array['page_example_arguments'] = [
      // The theme function to apply to the #items.
      '#theme' => 'item_list',
      // The list itself.
      '#items' => $list,
      '#title' => $this->t('Argument Information'),
    ];
    return $render_array;
  }

}

<?php

namespace Drupal\form_reference_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\form_reference_field\FormReferenceFormDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FormReferenceAutocompleteController extends ControllerBase
{

	/**
	 * @var \Drupal\form_reference_field\FormReferenceFormDiscovery
	 */
	protected $formDiscovery;

	public function __construct(FormReferenceFormDiscovery $formDiscovery) {
		$this->formDiscovery = $formDiscovery;
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('form_reference_field.form_discovery')
		);
	}

	/**
	 * Handles autocomplete requests for form reference field settings.
	 */
	public function handleAutocomplete(Request $request)
	{
		$matches = [];
		$input = $request->query->get('q');
		$options = $this->formDiscovery->getFormOptions();
		foreach ($options as $id => $label) {
			if (stripos($label, $input) !== FALSE || stripos($id, $input) !== FALSE) {
				$matches[] = [
					'value' => $id,
					'label' => $label,
				];
			}
		}
		return new JsonResponse($matches);
	}

}

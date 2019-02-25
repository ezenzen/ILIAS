<?php
function tree() {

	global $DIC;
	$f = $DIC->ui()->factory();
	$renderer = $DIC->ui()->renderer();

	$data = [
		['label' => 'root', 'children' => [
			['label' => '1', 'children' => [
				['label' => '1.1', 'children' => [
					['label' => '1.1.1', 'children' => []],
					['label' => '1.1.2', 'children' => []]
				]],
				['label' => '1.2', 'children' => []],
				['label' => '1.3', 'children' => []]
			]],
			['label' => '2', 'children' => [
				['label' => '2.1', 'children' => []],
			]],
			['label' => '3', 'children' => [
				['label' => '3.1', 'children' => [
					['label' => '3.1.1', 'children' => [
						['label' => '3.1.1.1', 'children' => []],
					]],
				]],

			]],
		]]
	];



	$recursion = new class implements \ILIAS\UI\Component\Tree\TreeRecursion
	{
		public function getChildren($record, $environment = null): array
		{
			return $record['children'];
		}

		public function build(
			\ILIAS\UI\Component\Tree\Node\Factory $factory,
			$record,
			$environment = null
		): \ILIAS\UI\Component\Tree\Node\Node {
			$label = $record['label'];
			$node = $factory->simple($label);
			if(count($record['children']) === 0) {
				$node = $node->withOnClick($environment['modal']->getShowSignal());
			}
			return $node;
		}
	};

	$image = $f->image()->responsive("src/UI/examples/Image/mountains.jpg", "Image source: https://stocksnap.io, Creative Commons CC0 license");
	$page = $f->modal()->lightboxImagePage($image, 'Mountains');
	$modal = $f->modal()->lightbox($page);
	$environment = [
		'modal' => $modal
	];

	$tree = $f->tree()->tree($recursion)
		->withEnvironment($environment)
		->withData($data);

	return $renderer->render([
		$modal,
		$tree
	]);
}
<?php
declare(strict_types=1);

/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Tree\Node;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Component\Tree\Node;

class Renderer extends AbstractComponentRenderer {
	/**
	 * @inheritdoc
	 */
	public function render(Component\Component $component, RendererInterface $default_renderer) {
		$this->checkComponent($component);

		$tpl_name = "tpl.node.html";
		$tpl = $this->getTemplate($tpl_name, true, true);

		$tpl->setVariable("LABEL", $component->getLabel());

		$subnodes = $component->getSubnodes();


		$component = $component->withAdditionalOnLoadCode(function ($id) use ($signals){
			return "
				$('#$id > span').click(function(e){
					$('#$id').toggleClass('expanded');
					return false;
				});";
		});

		$triggered_signals = $component->getTriggeredSignals();
		if(count($triggered_signals) > 0) {

			foreach ($triggered_signals as $s) {
				$signals[] = [
					"signal_id" => $s->getSignal()->getId(),
					"event" => $s->getEvent(),
					"options" => $s->getSignal()->getOptions()
				];
			}
			$signals = json_encode($signals);

			$component = $component->withAdditionalOnLoadCode(function ($id) use ($signals){
				return "
				$('#$id > span').click(function(e){
					var node = $('#$id'),
						signals = $signals;

					for (var i = 0; i < signals.length; i++) {
						var s = signals[i];
						node.trigger(s.signal_id, s);
					}

					return false;
				});";
			});
		}

		$id = $this->bindJavaScript($component);
		$tpl->setVariable("ID", $id);

		if(count($subnodes) > 0) {
			$tpl->touchBlock("expandable");
			if($component->isExpanded()) {
				$tpl->touchBlock("expanded");
			}

			$subnodes_html = $default_renderer->render($subnodes);
			$tpl->setVariable("SUBNODES", $subnodes_html);
		}

		return $tpl->get();
	}

	/**
	 * @inheritdoc
	 */
	protected function getComponentInterfaceName() {
		return array(
			Node\Simple::class
		);
	}
}

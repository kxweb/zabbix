<?php declare(strict_types = 1);

namespace Modules\Example_A;

use Core\CModule,
	APP,
	CMenu;

class Module extends CModule {

	/**
	 * Initialize module.
	 */
	public function init(): void {

		/** @var CMenu $menu */
		$menu = APP::Component()->get('menu.main');

		$menu
			->find(_('Monitoring'))
			->getSubMenu()
			->add(
				(new \CMenuItem(_('4th Module')))->setAction('forth.module')
			);
	}
}

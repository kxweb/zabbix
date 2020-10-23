<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/../../include/config.inc.php';

class CControllerPopupImport extends CController {

	protected function checkInput() {
		$fields = [
			'import' => 'in 1',
			'rules_preset' => 'in '.implode(',',['host', 'template', 'mediatype', 'valuemap', 'screen', 'map']),
			'rules' => 'array'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$output = [];
			if (($messages = getMessages()) !== null) {
				$output['errors'] = $messages->toString();
			}

			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode($output)]))->disableView()
			);
		}

		return $ret;
	}

	protected function checkPermissions() {
		return true;
	}

	protected function doAction() {
		$rules = [
			'groups' => ['createMissing' => false],
			'hosts' => ['updateExisting' => false, 'createMissing' => false],
			'templates' => ['updateExisting' => false, 'createMissing' => false],
			'templateDashboards' => ['updateExisting' => false, 'createMissing' => false, 'deleteMissing' => false],
			'templateLinkage' => ['createMissing' => false, 'deleteMissing' => false],
			'applications' => ['createMissing' => false, 'deleteMissing' => false],
			'items' => ['updateExisting' => false, 'createMissing' => false, 'deleteMissing' => false],
			'discoveryRules' => ['updateExisting' => false, 'createMissing' => false, 'deleteMissing' => false],
			'triggers' => ['updateExisting' => false, 'createMissing' => false, 'deleteMissing' => false],
			'graphs' => ['updateExisting' => false, 'createMissing' => false, 'deleteMissing' => false],
			'httptests' => ['updateExisting' => false, 'createMissing' => false, 'deleteMissing' => false],
			'screens' => ['updateExisting' => false, 'createMissing' => false],
			'maps' => ['updateExisting' => false, 'createMissing' => false],
			'images' => ['updateExisting' => false, 'createMissing' => false],
			'mediaTypes' => ['updateExisting' => false, 'createMissing' => false],
			'valueMaps' => ['updateExisting' => false, 'createMissing' => false]
		];

		// Adjust defaults for given rule preset, if specified.
		switch (getRequest('rules_preset')) {
			case 'host':
				$rules['groups'] = ['createMissing' => true];
				$rules['hosts'] = ['updateExisting' => true, 'createMissing' => true];
				$rules['applications'] = ['createMissing' => true, 'deleteMissing' => false];
				$rules['items'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['discoveryRules'] = ['updateExisting' => true, 'createMissing' => true,
					'deleteMissing' => false
				];
				$rules['triggers'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['graphs'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['httptests'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['templateLinkage'] = ['createMissing' => true, 'deleteMissing' => false];
				$rules['valueMaps'] = ['updateExisting' => false, 'createMissing' => true];
				break;

			case 'template':
				$rules['groups'] = ['createMissing' => true];
				$rules['templates'] = ['updateExisting' => true, 'createMissing' => true];
				$rules['templateDashboards'] = ['updateExisting' => true, 'createMissing' => true,
					'deleteMissing' => false
				];
				$rules['applications'] = ['createMissing' => true, 'deleteMissing' => false];
				$rules['items'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['discoveryRules'] = ['updateExisting' => true, 'createMissing' => true,
					'deleteMissing' => false
				];
				$rules['triggers'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['graphs'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['httptests'] = ['updateExisting' => true, 'createMissing' => true, 'deleteMissing' => false];
				$rules['templateLinkage'] = ['createMissing' => true, 'deleteMissing' => false];
				$rules['valueMaps'] = ['updateExisting' => false, 'createMissing' => true];
				break;

			case 'mediatype':
				$rules['mediaTypes'] = ['updateExisting' => false, 'createMissing' => true];
				break;

			case 'valuemap':
				$rules['valueMaps'] = ['updateExisting' => false, 'createMissing' => true];
				break;

			case 'map':
				$rules['maps'] = ['updateExisting' => CWebUser::checkAccess(CRoleHelper::ACTIONS_EDIT_MAPS),
					'createMissing' => CWebUser::checkAccess(CRoleHelper::ACTIONS_EDIT_MAPS)
				];
				$rules['images'] = ['updateExisting' => false, 'createMissing' => true];
				break;

			case 'screen':
				$rules['screens'] = ['updateExisting' => CWebUser::checkAccess(CRoleHelper::ACTIONS_EDIT_DASHBOARDS),
					'createMissing' => CWebUser::checkAccess(CRoleHelper::ACTIONS_EDIT_DASHBOARDS)
				];
				break;
		}

		if ($this->hasInput('import')) {
			$request_rules = getRequest('rules', []);

			foreach ($rules as $rule_name => $rule) {
				if (!array_key_exists($rule_name, $request_rules)) {
					$request_rules[$rule_name] = [];
				}

				foreach (['updateExisting', 'createMissing', 'deleteMissing'] as $option) {
					if (array_key_exists($option, $request_rules[$rule_name])) {
						$request_rules[$rule_name][$option] = true;
					}
					elseif (array_key_exists($option, $rule)) {
						$request_rules[$rule_name][$option] = false;
					}
				}
			}

			if (!isset($_FILES['import_file'])) {
				error(_('No file was uploaded.'));
			} else {
				// CUploadFile throws exceptions, so we need to catch them
				try {
					$file = new CUploadFile($_FILES['import_file']);

					API::Configuration()->import([
						'format' => CImportReaderFactory::fileExt2ImportFormat($file->getExtension()),
						'source' => $file->getContent(),
						'rules' => $request_rules
					]);
				}
				catch (Exception $e) {
					error($e->getMessage());
				}
			}

			$output = [];

			if (($messages = getMessages()) !== null) {
				$output['errors'] = $messages->toString();
			}
			else {
				$output = ['message' => _('Imported successfully')];
			}

			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode($output)]))->disableView()
			);
		}
		else {
			$this->setResponse(new CControllerResponseData([
				'title' => _('Import'),
				'rules' => $rules,
				'allowed_edit_maps' => CWebUser::checkAccess(CRoleHelper::ACTIONS_EDIT_MAPS),
				'allowed_edit_screens' => CWebUser::checkAccess(CRoleHelper::ACTIONS_EDIT_DASHBOARDS),
				'user' => [
					'debug_mode' => $this->getDebugMode()
				]
			]));
		}
	}
}

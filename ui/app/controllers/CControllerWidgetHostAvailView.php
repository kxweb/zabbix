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


class CControllerWidgetHostAvailView extends CControllerWidget {

	public function __construct() {
		parent::__construct();

		$this->setType(WIDGET_HOST_AVAIL);
		$this->setValidationRules([
			'name' => 'string',
			'fields' => 'json'
		]);
	}

	protected function doAction() {
		$fields = $this->getForm()->getFieldsData();

		$interface_types = [INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP, INTERFACE_TYPE_JMX, INTERFACE_TYPE_IPMI];

		// Sanitize non-existing interface types.
		$fields['interface_type'] = array_values(array_intersect($interface_types, $fields['interface_type']));

		$groupids = $fields['groupids'] ? getSubGroups($fields['groupids']) : null;

		$hosts_types = $fields['interface_type'] ? $fields['interface_type'] : $interface_types;

		$hosts_total = array_fill_keys($interface_types, 0);
		$hosts_count = array_fill_keys($interface_types, [
			INTERFACE_AVAILABLE_UNKNOWN => 0,
			INTERFACE_AVAILABLE_TRUE => 0,
			INTERFACE_AVAILABLE_FALSE => 0
		]);

		$db_hosts = API::Host()->get([
			'output' => [],
			'selectInterfaces' => ['type', 'available'],
			'groupids' => $groupids,
			'filter' => ($fields['maintenance'] == HOST_MAINTENANCE_STATUS_OFF)
				? ['status' => HOST_STATUS_MONITORED, 'maintenance_status' => HOST_MAINTENANCE_STATUS_OFF]
				: ['status' => HOST_STATUS_MONITORED]
		]);

		foreach ($db_hosts as $host) {
			$ignore_types = [];

			foreach ($host['interfaces'] as $interface) {
				if (array_key_exists($interface['type'], $ignore_types)) {
					continue;
				}

				$ignore_types[$interface['type']] = 1;
				$hosts_count[$interface['type']][$interface['available']]++;
				$hosts_total[$interface['type']]++;
			}
		}

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->getDefaultHeader()),
			'layout' => $fields['layout'],
			'hosts_types' => $hosts_types,
			'hosts_count' => $hosts_count,
			'hosts_total' => $hosts_total,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}

<?php

namespace stevotvr\steamstatus\migrations;

class add_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['stevotvr_steamstatus_key']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('stevotvr_steamstatus_key', '')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_STEAMSTATUS_TITLE',
			)),
			array('module.add', array(
				'acp',
				'ACP_STEAMSTATUS_TITLE',
				array(
					'module_basename'	=> '\stevotvr\steamstatus\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}
}
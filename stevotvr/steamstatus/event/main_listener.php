<?php

namespace stevotvr\steamstatus\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.ucp_profile_modify_profile_info'		=> 'load_ucp_profile_language',
			'core.ucp_profile_validate_profile_info'	=> 'validate_id',
			'core.ucp_profile_info_modify_sql_ary'		=> 'modify_id',
		);
	}

	public function load_ucp_profile_language($event)
	{
		global $phpbb_container;
		$language = $phpbb_container->get('language');
		$language->add_lang('ucp_profile', 'stevotvr/steamstatus');
	}

	public function validate_id($event)
	{
		global $request, $config;

		$steam_id = trim($request->variable('pf_steam_id', ''));
		if($steam_id)
		{
			$steam_id64 = null;
			$steam_error = 'ERROR_INVALID_FORMAT';
			$matches = array();
			if(preg_match('/^STEAM_0:([0-1]):(\d+)$/', $steam_id, $matches) === 1)
			{
				// TODO: Add fallback for when bcmath is unavailable
				$steam_id64 = \bcadd($matches[2] * 2 + $matches[1], '76561197960265728');
			}
			elseif(preg_match('/^\[?U:1:(\d+)\]?$/', $steam_id, $matches) === 1)
			{
				// TODO: Add fallback for when bcmath is unavailable
				$steam_id64 = \bcadd($matches[1], '76561197960265728');
			}
			elseif(preg_match('/(?:steamcommunity.com\/profiles\/)?(\d{17})\/?$/', $steam_id, $matches) === 1)
			{
				$steam_id64 = $matches[1];
			}
			elseif(preg_match('/(?:steamcommunity.com\/id\/)?(\w+)\/?$/', $steam_id, $matches) === 1)
			{
				$query = http_build_query(array(
					'key'		=> $config['stevotvr_steamstatus_key'],
					'vanityurl'	=> $matches[1],
				));
				$url = 'https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/?' . $query;
				$result = @file_get_contents($url);
				if($result)
				{
					$result = json_decode($result);
					if($result && $result->response && $result->response->success === 1)
					{
						$steam_id64 = $result->response->steamid;
					}
					else
					{
						$steam_error = 'ERROR_NAME_NOT_FOUND';
					}
				}
				else
				{
					$steam_error = 'ERROR_LOOKUP_FAILED';
				}
			}
			if(!$steam_id64)
			{
				$error = $event['error'];
				$error[] = $steam_error;
				$event['error'] = $error;
			}
			else
			{
				$data = $event['data'];
				$data['pf_steam_id'] = $steam_id64;
				$event['data'] = $data;
			}
		}
	}

	public function modify_id($event)
	{
		if(sizeof($event['data']['pf_steam_id'])) {
			$cp_data = $event['cp_data'];
			$cp_data['pf_steam_id'] = $event['data']['pf_steam_id'];
			$event['cp_data'] = $cp_data;
		}
	}
}
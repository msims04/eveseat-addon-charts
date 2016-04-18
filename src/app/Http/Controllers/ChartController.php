<?php

namespace Seat\Addon\Charts\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Log\Writer as Log;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Character\CharacterSheetSkills;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Corporation\MemberTracking;
use Seat\Web\Models\User;

class ChartController extends Controller
{
	public function __construct(
		CharacterSheet       $character_sheet_model,
		CharacterSheetSkills $character_sheet_skills_model,
		CorporationSheet     $corporation_sheet_model,
		MemberTracking       $member_tracking_model,
		User                 $user_model,
		Carbon               $carbon,
		Cache                $cache,
		Log                  $log
	) {
		$this->character_sheet_model        = $character_sheet_model;
		$this->character_sheet_skills_model = $character_sheet_skills_model;
		$this->corporation_sheet_model      = $corporation_sheet_model;
		$this->member_tracking_model        = $member_tracking_model;
		$this->user_model                   = $user_model;
		$this->carbon                       = $carbon;
		$this->cache                        = $cache;
		$this->log                          = $log;
	}

	public function index($corporation_id = 0)
	{
		return view('charts::corporation')
			->withSkillPoints          ($this->getSkillPointsJson          ($corporation_id))
			->withUsers                ($this->getUsersJson                ($corporation_id))
			->withActiveUsers          ($this->getActiveUsersJson          ($corporation_id))
			->withActiveCharacters     ($this->getActiveCharactersJson     ($corporation_id))
			->withRegisteredCharacters ($this->getRegisteredCharactersJson ($corporation_id))
		;
	}

	private function getSkillPointsJson($corporation_id)
	{
		$character_ids = $this->character_sheet_model
			->select('characterID')
			->where('corporationID', $corporation_id)
			->lists('characterID');

		$result = [
			['y' =>   '<1m', 'a' => 0],
			['y' =>   '<5m', 'a' => 0],
			['y' =>  '<10m', 'a' => 0],
			['y' =>  '<20m', 'a' => 0],
			['y' =>  '<30m', 'a' => 0],
			['y' =>  '<40m', 'a' => 0],
			['y' =>  '<50m', 'a' => 0],
			['y' =>  '<60m', 'a' => 0],
			['y' =>  '<70m', 'a' => 0],
			['y' =>  '<80m', 'a' => 0],
			['y' =>  '<90m', 'a' => 0],
			['y' => '<100m', 'a' => 0],
			['y' => '<125m', 'a' => 0],
			['y' => '<150m', 'a' => 0],
			['y' => '>150m', 'a' => 0],
		];

		foreach ($character_ids as $character_id) {
			$skill_points = $this->character_sheet_skills_model
				->select('skillpoints')
				->where('characterID', $character_id)
				->sum('skillpoints');

			if      ($skill_points <   1000000) { $result[ 0]['a'] += 1; }
			else if ($skill_points <   5000000) { $result[ 1]['a'] += 1; }
			else if ($skill_points <  10000000) { $result[ 2]['a'] += 1; }
			else if ($skill_points <  20000000) { $result[ 3]['a'] += 1; }
			else if ($skill_points <  30000000) { $result[ 4]['a'] += 1; }
			else if ($skill_points <  40000000) { $result[ 5]['a'] += 1; }
			else if ($skill_points <  50000000) { $result[ 6]['a'] += 1; }
			else if ($skill_points <  60000000) { $result[ 7]['a'] += 1; }
			else if ($skill_points <  70000000) { $result[ 8]['a'] += 1; }
			else if ($skill_points <  80000000) { $result[ 9]['a'] += 1; }
			else if ($skill_points <  90000000) { $result[10]['a'] += 1; }
			else if ($skill_points < 100000000) { $result[11]['a'] += 1; }
			else if ($skill_points < 125000000) { $result[12]['a'] += 1; }
			else if ($skill_points < 150000000) { $result[13]['a'] += 1; }
			else if ($skill_points > 150000000) { $result[14]['a'] += 1; }
		}

		return json_encode($result);
	}

	private function getUsersJson($corporation_id)
	{
		$character_count = $this->character_sheet_model
			->select('characterID')
			->where('corporationID', $corporation_id)
			->count();

		$user_count = $this->getUsers($corporation_id)
			->count();

		$result = [
			['label' => trans('charts::charts.characters'), 'value' => $character_count],
			['label' => trans('charts::charts.users'     ), 'value' => $user_count     ],
		];

		return json_encode($result);
	}

	private function getActiveUsersJson($corporation_id)
	{
		$active_character_ids = $this->getActiveCharacters($corporation_id)
			->lists('characterID')
			->toArray();

		$user_count = $this->getUsers($corporation_id)
			->count();

		$active_user_count = $this->getActiveUsers($corporation_id, $active_character_ids)
			->count();

		$inactive_user_count = $user_count - $active_user_count;

		$result = [
			['label' => trans('charts::charts.active'  ), 'value' => $active_user_count  ],
			['label' => trans('charts::charts.inactive'), 'value' => $inactive_user_count],
		];

		return json_encode($result);
	}

	private function getActiveCharactersJson($corporation_id)
	{
		$character_count = $this->member_tracking_model
			->where('corporationID', $corporation_id)
			->count();

		$active_character_count = $this->getActiveCharacters($corporation_id)
			->count();

		$result = [
			['label' => trans('charts::charts.active'  ), 'value' => $active_character_count],
			['label' => trans('charts::charts.inactive'), 'value' => $character_count - $active_character_count],
		];

		return json_encode($result);
	}

	private function getRegisteredCharactersJson($corporation_id)
	{
		$corporation_member_count = $this->getCorporationMemberCount($corporation_id);

		$registered_character_count = $this->character_sheet_model
			->select('characterID')
			->where('corporationID', $corporation_id)
			->count();

		$result = [
			['label' => trans('charts::charts.registered'  ), 'value' => $registered_character_count],
			['label' => trans('charts::charts.unregistered'), 'value' => $corporation_member_count - $registered_character_count],
		];

		return json_encode($result);
	}

	private function getUsers($corporation_id)
	{
		return $this->user_model
			->with('keys')
			->with('keys.characters')
			->get()
			->filter(function ($user) use($corporation_id) {
				$in_corporation = false;

				$user->keys->each(function ($key) use($corporation_id, &$in_corporation) {
					$key->characters->each(function ($character) use($corporation_id, &$in_corporation) {
						if ($character->corporationID == $corporation_id) {
							$in_corporation = true;
						}
					});
				});

				return $in_corporation;
			});
	}

	private function getCorporationMemberCount($corporation_id)
	{
		return (integer)$this->corporation_sheet_model
			->select('memberCount')
			->where('corporationID', $corporation_id)
			->lists('memberCount')[0];
	}

	private function getActiveUsers($corporation_id, $active_character_ids = null)
	{
		$active_character_ids = $active_character_ids ?: $this->getActiveCharacters($corporation_id)
			->lists('characterID')
			->toArray();

		$active_users = $this->getUsers($corporation_id)
			->filter(function ($user) use ($active_character_ids, &$count) {
				$characters_ids = $user->keys
					->transform(function ($key) {
						return $key->characters;
					})
					->collapse()
					->lists('characterID')
					->toArray();

				return count(array_intersect($active_character_ids, $characters_ids)) > 0;
			});

		return $active_users;
	}

	private function getActiveCharacters($corporation_id)
	{
		$active_characters = $this->member_tracking_model
			->where('corporationID', $corporation_id)
			->where('logonDateTime', '>', $this->carbon->now()->subMonths(1))
			->get();

		return $active_characters;
	}
}

<?php

namespace Seat\Addon\Charts\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Log\Writer as Log;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Character\CharacterSheetSkills;
use Seat\Eveapi\Models\Corporation\MemberTracking;
use Seat\Web\Models\User;

class ChartController extends Controller
{
	public function __construct(
		CharacterSheet       $character_sheet_model,
		CharacterSheetSkills $character_sheet_skills_model,
		MemberTracking       $member_tracking_model,
		User                 $user_model,
		Carbon               $carbon,
		Cache                $cache,
		Log                  $log
	) {
		$this->character_sheet_model        = $character_sheet_model;
		$this->character_sheet_skills_model = $character_sheet_skills_model;
		$this->member_tracking_model        = $member_tracking_model;
		$this->user_model                   = $user_model;
		$this->carbon                       = $carbon;
		$this->cache                        = $cache;
		$this->log                          = $log;
	}

	public function index($corporationID = 0)
	{
		return view('charts::corporation')
			->withSkillPoints     ($this->getSkillPointsJson     ($corporationID))
			->withUsers           ($this->getUsersJson           ($corporationID))
			->withActiveUsers     ($this->getActiveUsersJson     ($corporationID))
			->withActiveCharacters($this->getActiveCharactersJson($corporationID))
		;
	}

	private function getSkillPointsJson($corporationID)
	{
		$character_ids = $this->character_sheet_model
			->select('characterID')
			->where('corporationID', $corporationID)
			->get()
			->transform(function ($character) {
				return (integer)$character->characterID;
			});

		$result = [
			['y' =>   '<1m', 'a' => 0], // <   1m
			['y' =>   '<5m', 'a' => 0], // <   5m
			['y' =>  '<10m', 'a' => 0], // <  10m
			['y' =>  '<20m', 'a' => 0], // <  20m
			['y' =>  '<30m', 'a' => 0], // <  30m
			['y' =>  '<40m', 'a' => 0], // <  40m
			['y' =>  '<50m', 'a' => 0], // <  50m
			['y' =>  '<60m', 'a' => 0], // <  60m
			['y' =>  '<70m', 'a' => 0], // <  70m
			['y' =>  '<80m', 'a' => 0], // <  80m
			['y' =>  '<90m', 'a' => 0], // <  90m
			['y' => '<100m', 'a' => 0], // < 100m
			['y' => '<125m', 'a' => 0], // < 125m
			['y' => '<150m', 'a' => 0], // < 150m
			['y' => '>150m', 'a' => 0], // < 150m
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

	private function getUsersJson($corporationID)
	{
		$character_count = $this->character_sheet_model
			->select('characterID')
			->where('corporationID', $corporationID)
			->count();

		$user_count = $this->getUsers($corporationID)
			->count();

		$result = [
			['label' => 'Characters', 'value' => $character_count],
			['label' => 'Users'     , 'value' => $user_count     ],
		];

		return json_encode($result);
	}

	private function getActiveUsersJson($corporationID)
	{
		$active_character_ids = $this->getActiveCharacters($corporationID)
			->transform(function ($character) {
				return (integer)$character->characterID;
			})
			->toArray();

		$user_count = $this->getUsers($corporationID)
			->count();

		$active_user_count = $this->getActiveUsers($corporationID, $active_character_ids)
			->count();

		$inactive_user_count = $user_count - $active_user_count;

		$result = [
			['label' => 'Active'  , 'value' => $active_user_count  ],
			['label' => 'Inactive', 'value' => $inactive_user_count],
		];

		return json_encode($result);
	}

	private function getActiveCharactersJson($corporationID)
	{
		$character_count = $this->member_tracking_model
			->where('corporationID', $corporationID)
			->count();

		$active_character_count = $this->getActiveCharacters($corporationID)
			->count();

		$result = [
			['label' => 'Active'  , 'value' => $active_character_count],
			['label' => 'Inactive', 'value' => $character_count - $active_character_count],
		];

		return json_encode($result);
	}

	private function getUsers($corporationID)
	{
		return $this->user_model
			->with('keys')
			->with('keys.characters')
			->get()
			->filter(function ($user) use($corporationID) {
				$in_corporation = false;

				$user->keys->each(function ($key) use($corporationID, &$in_corporation) {
					$key->characters->each(function ($character) use($corporationID, &$in_corporation) {
						if ($character->corporationID == $corporationID) {
							$in_corporation = true;
						}
					});
				});

				return $in_corporation;
			});
	}

	private function getActiveUsers($corporationID, $active_character_ids = null)
	{
		$active_character_ids = $active_character_ids ?: $this->getActiveCharacters($corporationID)
			->transform(function ($character) {
				return (integer)$character->characterID;
			})
			->toArray();

		$active_users = $this->getUsers($corporationID)
			->filter(function ($user) use ($active_character_ids, &$count) {
				$characters_ids = $user->keys
					->transform(function ($key) {
						return $key->characters;
					})
					->collapse()
					->transform(function ($character) {
						return (integer)$character->characterID;
					})
					->toArray();

				return count(array_intersect($active_character_ids, $characters_ids)) > 0;
			});

		return $active_users;
	}

	private function getActiveCharacters($corporationID)
	{
		$active_characters = $this->member_tracking_model
			->where('corporationID', $corporationID)
			->where('logonDateTime', '>', $this->carbon->now()->subMonths(1))
			->get();

		return $active_characters;
	}
}

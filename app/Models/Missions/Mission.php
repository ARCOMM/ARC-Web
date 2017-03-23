<?php

namespace App\Models\Missions;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use App\Models\Missions\MissionComment;
use App\Helpers\ArmaConfigParser;
use App\Models\Missions\Map;
use App\Models\Portal\User;
use Carbon\Carbon;
use \stdClass;
use Storage;
use File;
use Log;

class Mission extends Model implements HasMediaConversions
{
    use HasMediaTrait;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'last_played'
    ];

    /**
     * The loadout role map.
     *
     * @var array
     */
    protected $roles = [
        'all' => 'Everyone',
        'co' => 'Commander',
        'dc' => 'Squad Leader',
        'ftl' => 'Fireteam Leader',
        'm' => 'Medic',
        'fac' => 'Forward Air Controller',
        'r' => 'Rifleman',
        'ar' => 'Automatic Rifleman',
        'aar' => 'Assistant Automatic Rifleman',
        'rat' => 'Rifleman (AT)',
        'mmgtl' => 'Medium MG Team Leader',
        'mmgg' => 'Medium MG Gunner',
        'mmgab' => 'Medium MG Ammo Bearer',
        'mattl' => 'Medium AT Team Leader',
        'matg' => 'Medium AT Missile Specialist',
        'matab' => 'Medium AT Assistant Missile Specialist',
        'mtrl' => 'Mortar Team Leader',
        'mtrg' => 'Mortar Gunner',
        'mtra' => 'Mortar Assistant',
        'p' => 'Pilot',
        'cp' => 'Co-Pilot',
        'vc' => 'Vehicle Commander',
        'vd' => 'Vehicle Driver',
        'vg' => 'Vehicle Gunner'
    ];

    /**
     * Media library image conversions.
     *
     * @return void
     */
    public function registerMediaConversions()
    {
        $this->addMediaConversion('thumb')
            ->setManipulations(['w' => 384, 'h' => 384, 'fit' => 'crop'])
            ->performOnCollections('images');
    }

    /**
     * Gets all past missions (last played is in past and not null).
     *
     * @return Collection App\Models\Missions\Mission
     */
    public static function allPast()
    {
        return self::whereRaw(
            'last_played IS NOT NULL AND last_played < "'.
            Carbon::now()->toDateTimeString().'"'
        )->where('published', true)->orderBy('last_played', 'desc')->get();
    }

    /**
     * Gets all new missions (last played is null).
     *
     * @return Collection App\Models\Missions\Mission
     */
    public static function allNew()
    {
        return self::whereRaw('last_played IS NULL')->where('published', true)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Checks whether the mission is new.
     *
     * @return boolean
     */
    public function isNew()
    {
        return is_null($this->last_played);
    }

    /**
     * Gets the missions map.
     *
     * @return App\Models\Missions\Map
     */
    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    /**
     * Gets the mission's user (author).
     *
     * @return App\Models\Portal\User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Checks whether the mission belongs to the authenticated user.
     *
     * @return boolean
     */
    public function isMine()
    {
        return $this->user_id == auth()->user()->id;
    }

    /**
     * Gets all mission comments.
     *
     * @return Collection App\Models\Missions\MissionComment
     */
    public function comments()
    {
        return $this->hasMany(MissionComment::class);
    }

    /**
     * Gets the mission banner URL.
     *
     * @return string
     */
    public function banner()
    {
        $media = $this->getMedia();

        if (count($media) > 0) {
            return $media[rand(0, count($media) - 1)]->getUrl();
        } else {
            return 'https://source.unsplash.com/category/nature';
        }
    }

    /**
     * Gets the mission thumbnail URL.
     *
     * @return string
     */
    public function thumbnail()
    {
        $media = $this->getMedia();

        if (count($media) > 0) {
            return $media[rand(0, count($media) - 1)]->getUrl('thumb');
        }

        if (!is_null($this->map->image_2d)) {
            return url($this->map->image_2d);
        }

        return '';
    }

    /**
     * Gets the exported name of the file following the mission name format.
     *
     * @return string
     */
    public function exportedName()
    {
        $download = 'ARC_' .
            strtoupper($this->mode == 'adversarial' ? 'tvt' : $this->mode) . '_' .
            studly_case($this->display_name) . '_' .
            trim(substr($this->user->username, 0, 4)) . '_' .
            $this->id . '.' .
            $this->map->class_name . '.pbo';

        return $download;
    }

    /**
     * Creates the downloadable file and returns its full URL.
     *
     * @return string
     */
    public function download()
    {
        $download = $this->exportedName();

        if (file_exists(public_path('downloads/' . $download))) {
            Storage::disk('downloads')->delete($download);
        }

        File::copy(storage_path('app/' . $this->pbo_path), public_path('downloads/' . $download));

        return url('downloads/' . $download);
    }

    /**
     * Gets all videos for the mission.
     * Sorted latest first.
     *
     * @return Collection App\Models\Portal\Video
     */
    public function videos()
    {
        return $this->hasMany('App\Models\Portal\Video')->orderBy('created_at', 'desc');
    }

    /**
     * Gets all photos for the mission.
     *
     * @return Collection
     */
    public function photos()
    {
        return $this->getMedia();
    }

    /**
     * Sets the given briefing faction to locked or unlocked.
     *
     * @return void
     */
    public function lockBriefing($faction, $state)
    {
        $this->{'locked_' . strtolower($faction) . '_briefing'} = $state;
        $this->save();
    }

    /**
     * Gets the user's draft for the mission.
     *
     * @return App\Models\Missions\MissionComment
     */
    public function draft()
    {
        $comment = MissionComment::
            where('mission_id', $this->id)
            ->where('user_id', auth()->user()->id)
            ->where('published', false)
            ->first();

        return $comment;
    }

    /**
     * Gets the full path of the armake exe.
     *
     * @return string
     */
    public static function armake()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return resource_path('utils/armake.exe');
        } else {
            return 'armake';
        }
    }

    /**
     * Unpacks the mission PBO and returns the absolute path of the folder.
     *
     * @return string
     */
    public function unpack()
    {
        $unpacked = storage_path(
            'app/missions/' .
            $this->user_id .
            '/' .
            $this->id .
            '_unpacked'
        );

        // Delete the directory if it exists
        File::deleteDirectory($unpacked);

        // Unpack the PBO
        shell_exec(
            static::armake() .
            ' unpack -f ' .
            storage_path('app/' . $this->pbo_path) .
            ' ' .
            $unpacked
        );

        chdir($unpacked);

        // Debinarize mission.sqm
        // If it's not binned, armake exits gracefully
        shell_exec(
            static::armake() .
            ' derapify -f mission.sqm mission.sqm'
        );

        // Refresh the file encoding to handle weird Eden nuances
        // shell_exec(static::armake() . ' binarize -f mission.sqm mission.sqm');
        // shell_exec(static::armake() . ' derapify -f mission.sqm mission.sqm');

        return $unpacked;
    }

    /**
     * Deletes the unpacked mission directory.
     *
     * @return void
     */
    public function deleteUnpacked()
    {
        Storage::deleteDirectory('missions/' . $this->user_id . '/' . $this->id . '_unpacked');
    }

    /**
     * Gets the mission EXT object.
     *
     * @return object
     */
    public function ext()
    {
        return request()->session()->get('mission_ext');
    }

    /**
     * Gets the mission SQM object.
     *
     * @return object
     */
    public function sqm()
    {
        return request()->session()->get('mission_sqm');
    }

    /**
     * Gets the mission config object.
     *
     * @return object
     */
    public function config()
    {
        return request()->session()->get('mission_config')->cfgarcmf;
    }

    /**
     * Stores the decoded mission config objects in the session.
     * Used for optimisation.
     *
     * @return void
     */
    public function storeConfigs()
    {
        $unpacked = $this->unpack();

        // Removes entity data in sqm to avoid Eden string nuances
        $sqm_file = $unpacked . '/mission.sqm';
        $sqm_contents = file_get_contents($sqm_file);
        $sqm_contents = preg_replace('!/\*.*?\*/!s', '', $sqm_contents);
        $sqm_contents = preg_replace('/(class Entities[\s\S]+)/', '};', $sqm_contents);
        file_put_contents($sqm_file, $sqm_contents);

        request()->session()->put('mission_sqm', ArmaConfigParser::convert($sqm_file));
        request()->session()->put('mission_ext', ArmaConfigParser::convert($unpacked . '/description.ext'));
        request()->session()->put('mission_config', ArmaConfigParser::convert($unpacked . '/config.hpp'));
        request()->session()->put('mission_version', file_get_contents($unpacked . '/version.txt'));

        $this->deleteUnpacked();
    }

    /**
     * Gets the missions framework version.
     *
     * @return string
     */
    public function version()
    {
        return request()->session()->get('mission_version');
    }

    /**
     * Computes an array of options and returns the lowest true result.
     *
     * @return string
     */
    protected static function computeLessThan($value, $options)
    {
        foreach ($options as $text => $level) {
            if ($value <= $level) {
                return $text;
            }
        }
    }

    /**
     * Gets the fog text.
     *
     * @return string
     */
    public function fog()
    {
        return static::computeLessThan(
            (property_exists($this->sqm()->mission->intel, 'startfog')) ? $this->sqm()->mission->intel->startfog : 0,
            [
                '' => 0.0,
                'Light Fog' => 0.1,
                'Medium Fog' => 0.3,
                'Heavy Fog' => 0.5,
                'Extreme Fog' => 1.0
            ]
        );
    }

    /**
     * Gets the overcast text.
     *
     * @return string
     */
    public function overcast()
    {
        return static::computeLessThan(
            $this->sqm()->mission->intel->startweather,
            [
                'Clear Skies' => 0.1,
                'Partly Cloudy' => 0.3,
                'Heavy Clouds' => 0.6,
                'Stormy' => 1.0
            ]
        );
    }

    /**
     * Gets the rain text.
     *
     * @return string
     */
    public function rain()
    {
        $startRain = (property_exists($this->sqm()->mission->intel, 'startrain')) ? $this->sqm()->mission->intel->startrain : 0;
        $forecastRain = (property_exists($this->sqm()->mission->intel, 'forecastrain')) ? $this->sqm()->mission->intel->forecastrain : 0;
        $diff = $forecastRain - $startRain;

        return static::computeLessThan(
            $diff,
            [
                '' => 0,
                'Slight Drizzle' => 0.2,
                'Drizzle' => 0.4,
                'Rain' => 0.6,
                'Showers' => 1
            ]
        );
    }

    /**
     * Gets the weather text.
     *
     * @return string
     */
    public function weather()
    {
        return $this->overcast() . (($this->fog() == '') ? '' : ', ' . $this->fog()) . (($this->rain() == '') ? '' : ', ' . $this->rain());
    }

    /**
     * Gets the weather image name.
     *
     * @return string
     */
    public function weatherImage()
    {
        return url('/images/weather/' . ([
            'Clear Skies' => 'clear',
            'Partly Cloudy' => 'partly sunny',
            'Heavy Clouds' => 'partly cloudy',
            'Stormy' => 'cloudy',
            'Clear Skies, Slight Drizzle' => 'slight drizzle',
            'Clear Skies, Drizzle' => 'light rain',
            'Clear Skies, Rain' => 'rain',
            'Clear Skies, Showers' => 'showers',
            'Partly Cloudy, Slight Drizzle' => 'slight drizzle',
            'Partly Cloudy, Drizzle' => 'light rain',
            'Partly Cloudy, Rain' => 'rain',
            'Partly Cloudy, Showers' => 'showers',
            'Heavy Clouds, Slight Drizzle' => 'slight drizzle',
            'Heavy Clouds, Drizzle' => 'light rain',
            'Heavy Clouds, Rain' => 'rain',
            'Heavy Clouds, Showers' => 'showers',
            'Stormy, Slight Drizzle' => 'slight drizzle',
            'Stormy, Drizzle' => 'light rain',
            'Stormy, Rain' => 'rain',
            'Stormy, Showers' => 'showers'
        ])[$this->overcast() . (($this->rain() == '') ? '' : ', ' . $this->rain())] . '.png');
    }

    /**
     * Gets the mission SQM date.
     *
     * @return string
     */
    public function date()
    {
        $date = Carbon::createFromDate(
            (property_exists($this->sqm()->mission->intel, 'year')) ? abs($this->sqm()->mission->intel->year) : 2000,
            (property_exists($this->sqm()->mission->intel, 'month')) ? abs($this->sqm()->mission->intel->month) : 1,
            (property_exists($this->sqm()->mission->intel, 'day')) ? abs($this->sqm()->mission->intel->day) : 1
        );

        return $date->format('jS M Y');
    }

    /**
     * Gets the mission SQM time.
     *
     * @return string
     */
    public function time()
    {
        $time = Carbon::createFromTime(
            (property_exists($this->sqm()->mission->intel, 'hour')) ? abs($this->sqm()->mission->intel->hour) : 0,
            (property_exists($this->sqm()->mission->intel, 'minute')) ? abs($this->sqm()->mission->intel->minute) : 0,
            0
        );

        return $time->format('H:i');
    }

    /**
     * Gets all briefing factions that aren't locked (unless admin).
     *
     * @return array
     */
    public function briefingFactions()
    {
        $filledFactions = [];
        $factions = [
            'BLUFOR' => $this->locked_blufor_briefing,
            'OPFOR' => $this->locked_opfor_briefing,
            'INDFOR' => $this->locked_indfor_briefing,
            'CIVILIAN' => $this->locked_civilian_briefing,
            // 'GAME_MASTER' => $this->locked_gamemaster_briefing
        ];

        foreach ($factions as $faction => $locked) {
            if (!empty($this->briefing($faction)) && (!$locked || auth()->user()->isAdmin() || $this->isMine())) {
                $name = str_replace('_', ' ', $faction);

                $nav = new stdClass();
                $nav->name = $name;
                $nav->faction = $faction;
                $nav->locked = $locked;

                array_push($filledFactions, $nav);
            }
        }

        return $filledFactions;
    }

    /**
     * Gets the given faction's briefing subjects and content.
     *
     * @return array
     */
    public function briefing($faction)
    {
        $faction = strtolower($faction);
        $filledSubjects = [];
        $subjects = [
            'Situation' => 'situation',
            'Mission' => 'mission',
            'Enemy Forces' => 'enemyforces',
            'Friendly Forces' => 'friendlyforces',
            'Commanders Intent' => 'commandersintent',
            'Movement Plan' => 'movementplan',
            'Special Tasks' => 'specialtasks',
            'Fire Support Plan' => 'firesupportplan',
            'Logistics' => 'logistics'
        ];

        foreach ($subjects as $heading => $subject) {
            $subject = strtolower($subject);

            if (!property_exists($this->config()->briefing->$faction, $subject)) {
                continue;
            }

            $paragraphs = (array)$this->config()->briefing->$faction->$subject;

            if (!empty($paragraphs)) {
                $subjectObject = new stdClass();
                $subjectObject->title = $heading;
                $subjectObject->paragraphs = $paragraphs;
                $subjectObject->locked = $this->{'locked_' . $faction . '_briefing'};
                array_push($filledSubjects, $subjectObject);
            }
        }

        return $filledSubjects;
    }

    /**
     * Checks whether the given faction's briefing is locked.
     * Ignores user access level.
     *
     * @return boolean
     */
    public function briefingLocked($faction)
    {
        return $this->{'locked_' . strtolower($faction) . '_briefing'} > 0;
    }

    /**
     * Gets mission mode and map details from name.
     * Validates mission names and aborts if invalid name.
     *
     * @return object
     */
    public static function getDetailsFromName($name)
    {
        if (substr(strtolower($name), -3) != 'pbo') {
            abort(403, 'Mission file must be a PBO');
            return;
        }

        if (strpos($name, '_') === false) {
            abort(403, 'Mission name is invalid');
            return;
        }

        $name = rtrim($name, '.pbo');
        $parts = explode('_', $name);
        $mapName = last(explode('.', last($parts)));
        $map = Map::whereRaw('LOWER(class_name) = ?', [strtolower($mapName)])->first();

        if (is_null($map)) {
            $map = new Map();
            $map->display_name = $mapName;
            $map->class_name = $mapName;
            $map->save();
        }

        if (sizeof($parts) < 3) {
            abort(403, 'Mission name is invalid: ' . $name);
            return;
        }

        $group = $parts[0];
        $mode = strtolower($parts[1]);
        $validModes = ['coop', 'co', 'tvt', 'pvp', 'adv', 'preop'];

        if (in_array($mode, $validModes)) {
            if ($mode == 'co') {
                $mode = 'coop';
            }

            if (in_array($mode, ['tvt', 'pvp', 'adv'])) {
                $mode = 'adversarial';
            }
        } else {
            abort(403, 'Mission mode is invalid: ' . $mode);
            return;
        }

        $details = new stdClass();
        $details->mode = $mode;
        $details->map = $map;

        return $details;
    }

    /**
     * Gets the ACRE languages for the given faction as a string.
     *
     * @return string
     */
    public function acreLanguages($faction = 'blufor')
    {
        $lang = (array)$this->config()->acre->{strtolower($faction)}->languages;

        $mutated = array_map(function($item) {
            return title_case($item);
        }, $lang);

        return implode(', ', $mutated);
    }

    /**
     * Gets the ACRE role list for the given radio classname and faction.
     *
     * @return string
     */
    public function acreRoles($faction, $radio)
    {
        $roles = (array)$this->config()->acre->{strtolower($faction)}->{strtolower($radio)};

        $mutated = array_map(function($item) {
            if (array_key_exists(strtolower($item), $this->roles)) {
                return $this->roles[strtolower($item)];
            } else {
                return strtoupper($item);
            };
        }, $roles);

        return implode(', ', $mutated);
    }

    /**
     * Gets an overall description of the comm plan for the given faction.
     * Can be full, limited or none.
     *
     * @return string
     */
    public function acreOverview($faction)
    {
        $radio_343 = (array)$this->config()->acre->{strtolower($faction)}->an_prc_343;

        if (!empty($radio_343)) {
            if (in_array('all', array_map('strtolower', $radio_343))) {
                return 'Full';
            }
        }

        foreach (['AN_PRC_148', 'AN_PRC_152', 'AN_PRC_117F', 'AN_PRC_77'] as $radio) {
            if (!empty((array)$this->config()->acre->{strtolower($faction)}->{strtolower($radio)})) {
                return 'Limited';
            }
        }

        return 'None';
    }
}

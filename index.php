<?php
session_start();

// ═══════════════════════════════════════════════════════════════════════════════
// GAME CLASS
// ═══════════════════════════════════════════════════════════════════════════════
class NumberGuessingGame {
    private $games = [
        'Easy'   => ['min'=>1,'max'=>10,  'attempts'=>5, 'xp_base'=>100,'xp_mult'=>1.0,'coins_base'=>10],
        'Medium' => ['min'=>1,'max'=>100, 'attempts'=>7, 'xp_base'=>200,'xp_mult'=>1.5,'coins_base'=>20],
        'Hard'   => ['min'=>1,'max'=>1000,'attempts'=>10,'xp_base'=>300,'xp_mult'=>2.5,'coins_base'=>35],
    ];

    // ── APRIL 2026 DAILY CALENDAR ────────────────────────────────────────
    // Each day: [secret, min, max, attempts, theme_note]
    private $aprilCalendar = [
        '2026-04-01'=>['secret'=>42, 'min'=>1,'max'=>100,'attempts'=>7, 'note'=>"April Fools' Day — the answer is always 42"],
        '2026-04-02'=>['secret'=>7,  'min'=>1,'max'=>50, 'attempts'=>6, 'note'=>'Lucky 7 — short range warmup'],
        '2026-04-03'=>['secret'=>333,'min'=>1,'max'=>500,'attempts'=>9, 'note'=>'Triple threat — find 333'],
        '2026-04-04'=>['secret'=>404,'min'=>1,'max'=>500,'attempts'=>8, 'note'=>'Error not found — or is it?'],
        '2026-04-05'=>['secret'=>13, 'min'=>1,'max'=>50, 'attempts'=>6, 'note'=>'Unlucky 13 — if you dare'],
        '2026-04-06'=>['secret'=>256,'min'=>1,'max'=>512,'attempts'=>9, 'note'=>'Power of 2 — binary thinking'],
        '2026-04-07'=>['secret'=>77, 'min'=>1,'max'=>100,'attempts'=>7, 'note'=>'Double lucky — go for 77'],
        '2026-04-08'=>['secret'=>420,'min'=>1,'max'=>500,'attempts'=>8, 'note'=>'Blaze it — 420 in the wild'],
        '2026-04-09'=>['secret'=>99, 'min'=>1,'max'=>200,'attempts'=>8, 'note'=>'So close to 100 — almost perfect'],
        '2026-04-10'=>['secret'=>1,  'min'=>1,'max'=>100,'attempts'=>7, 'note'=>'Start from the very beginning'],
        '2026-04-11'=>['secret'=>111,'min'=>1,'max'=>200,'attempts'=>8, 'note'=>'Repeating digits — triple ones'],
        '2026-04-12'=>['secret'=>69, 'min'=>1,'max'=>100,'attempts'=>7, 'note'=>'Nice. Just... nice.'],
        '2026-04-13'=>['secret'=>500,'min'=>1,'max'=>500,'attempts'=>8, 'note'=>'Maximum pressure — hit the ceiling'],
        '2026-04-14'=>['secret'=>314,'min'=>1,'max'=>400,'attempts'=>9, 'note'=>'Pi day vibes — 3.14...'],
        '2026-04-15'=>['secret'=>88, 'min'=>1,'max'=>100,'attempts'=>7, 'note'=>'Double eights — prosperity incoming'],
        '2026-04-16'=>['secret'=>16, 'min'=>1,'max'=>32, 'attempts'=>5, 'note'=>'Sweet sixteen — tiny range'],
        '2026-04-17'=>['secret'=>199,'min'=>100,'max'=>300,'attempts'=>8,'note'=>'Mid-range madness — start in the middle'],
        '2026-04-18'=>['secret'=>777,'min'=>1,'max'=>1000,'attempts'=>10,'note'=>'Triple 7s — jackpot edition'],
        '2026-04-19'=>['secret'=>50, 'min'=>1,'max'=>100,'attempts'=>7, 'note'=>'Dead center — can you find the midpoint?'],
        '2026-04-20'=>['secret'=>420,'min'=>400,'max'=>500,'attempts'=>7,'note'=>'Narrow hunt — it is what it is'],
        '2026-04-21'=>['secret'=>21, 'min'=>1,'max'=>50, 'attempts'=>6, 'note'=>'21 — the magic age'],
        '2026-04-22'=>['secret'=>222,'min'=>1,'max'=>300,'attempts'=>8, 'note'=>'Earth Day — triple 2s for balance'],
        '2026-04-23'=>['secret'=>23, 'min'=>1,'max'=>50, 'attempts'=>6, 'note'=>'Jordan\'s number — goat territory'],
        '2026-04-24'=>['secret'=>100,'min'=>1,'max'=>200,'attempts'=>8, 'note'=>'Centurion — find the perfect 100'],
        '2026-04-25'=>['secret'=>365,'min'=>1,'max'=>500,'attempts'=>9, 'note'=>'Days in a year — time flies'],
        '2026-04-26'=>['secret'=>26, 'min'=>1,'max'=>50, 'attempts'=>6, 'note'=>'Letters in the alphabet — A to Z'],
        '2026-04-27'=>['secret'=>999,'min'=>900,'max'=>999,'attempts'=>7,'note'=>'So close to 1000 — find the max'],
        '2026-04-28'=>['secret'=>128,'min'=>1,'max'=>256,'attempts'=>8, 'note'=>'Another power of 2 — halfway there'],
        '2026-04-29'=>['secret'=>29, 'min'=>1,'max'=>50, 'attempts'=>6, 'note'=>'Prime number day — 29 is prime'],
        '2026-04-30'=>['secret'=>430,'min'=>1,'max'=>500,'attempts'=>9, 'note'=>'End of April — April 30 encodes itself'],
    ];

    // ── 200 ACHIEVEMENTS ────────────────────────────────────────────────
    private $achievements = [
        // ── FIRST STEPS (1-10)
        'first_game'      =>['name'=>'First Step',       'icon'=>'👣','desc'=>'Play your first game','cat'=>'Beginner'],
        'first_win'       =>['name'=>'First Blood',      'icon'=>'🎯','desc'=>'Win your first game','cat'=>'Beginner'],
        'first_easy'      =>['name'=>'Easy Does It',     'icon'=>'🌱','desc'=>'Win on Easy difficulty','cat'=>'Beginner'],
        'first_medium'    =>['name'=>'Getting There',    'icon'=>'🌿','desc'=>'Win on Medium difficulty','cat'=>'Beginner'],
        'first_hard'      =>['name'=>'Hardboiled',       'icon'=>'💪','desc'=>'Win on Hard difficulty','cat'=>'Beginner'],
        'first_daily'     =>['name'=>'Daily Grinder',    'icon'=>'📅','desc'=>'Complete a daily challenge','cat'=>'Beginner'],
        'first_multi'     =>['name'=>'Duelist',          'icon'=>'⚔️','desc'=>'Play your first multiplayer game','cat'=>'Beginner'],
        'first_multi_win' =>['name'=>'Multi Victor',     'icon'=>'🏆','desc'=>'Win your first multiplayer game','cat'=>'Beginner'],
        'first_hint'      =>['name'=>'Need a Hint?',     'icon'=>'💡','desc'=>'Use your first hint','cat'=>'Beginner'],
        'first_powerup'   =>['name'=>'Power Up!',        'icon'=>'⚡','desc'=>'Use your first power-up','cat'=>'Beginner'],

        // ── WIN MILESTONES (11-25)
        'win_5'           =>['name'=>'Hand Full',        'icon'=>'✋','desc'=>'Win 5 games','cat'=>'Wins'],
        'win_10'          =>['name'=>'Dime',             'icon'=>'💰','desc'=>'Win 10 games','cat'=>'Wins'],
        'win_25'          =>['name'=>'Quarter Century',  'icon'=>'🎖️','desc'=>'Win 25 games','cat'=>'Wins'],
        'win_50'          =>['name'=>'Sharpshooter',     'icon'=>'🎯','desc'=>'Win 50 games','cat'=>'Wins'],
        'win_100'         =>['name'=>'Centurion',        'icon'=>'🏅','desc'=>'Win 100 games','cat'=>'Wins'],
        'win_250'         =>['name'=>'Veteran',          'icon'=>'🌟','desc'=>'Win 250 games','cat'=>'Wins'],
        'win_500'         =>['name'=>'Legend',           'icon'=>'👑','desc'=>'Win 500 games','cat'=>'Wins'],
        'win_1000'        =>['name'=>'Immortal',         'icon'=>'♾️','desc'=>'Win 1000 games','cat'=>'Wins'],
        'easy_win_10'     =>['name'=>'Easy Street',      'icon'=>'🛤️','desc'=>'Win 10 Easy games','cat'=>'Wins'],
        'medium_win_10'   =>['name'=>'Middle Manager',   'icon'=>'📊','desc'=>'Win 10 Medium games','cat'=>'Wins'],
        'hard_win_10'     =>['name'=>'Hard Knock Life',  'icon'=>'🪨','desc'=>'Win 10 Hard games','cat'=>'Wins'],
        'hard_win_25'     =>['name'=>'Iron Will',        'icon'=>'⚙️','desc'=>'Win 25 Hard games','cat'=>'Wins'],
        'hard_win_50'     =>['name'=>'Masochist',        'icon'=>'😈','desc'=>'Win 50 Hard games','cat'=>'Wins'],
        'perfect_week'    =>['name'=>'Perfect Week',     'icon'=>'📆','desc'=>'Win 7 days in a row','cat'=>'Wins'],
        'winrate_80'      =>['name'=>'Consistent',       'icon'=>'📐','desc'=>'Achieve 80% win rate (min 20 games)','cat'=>'Wins'],

        // ── STREAKS (26-40)
        'streak_3'        =>['name'=>'On Fire',          'icon'=>'🔥','desc'=>'3 wins in a row','cat'=>'Streaks'],
        'streak_5'        =>['name'=>'Unstoppable',      'icon'=>'💥','desc'=>'5 wins in a row','cat'=>'Streaks'],
        'streak_10'       =>['name'=>'Legendary Streak', 'icon'=>'⚡','desc'=>'10 wins in a row','cat'=>'Streaks'],
        'streak_20'       =>['name'=>'Godmode',          'icon'=>'🌩️','desc'=>'20 wins in a row','cat'=>'Streaks'],
        'streak_50'       =>['name'=>'Ascended',         'icon'=>'🌌','desc'=>'50 wins in a row','cat'=>'Streaks'],
        'no_lose_day'     =>['name'=>'Flawless Day',     'icon'=>'✨','desc'=>'Win 10 games without losing once','cat'=>'Streaks'],
        'comeback'        =>['name'=>'Comeback Kid',     'icon'=>'🔄','desc'=>'Win after 5 consecutive losses','cat'=>'Streaks'],
        'multi_streak_3'  =>['name'=>'Multi Streak',     'icon'=>'🔗','desc'=>'3 multiplayer wins in a row','cat'=>'Streaks'],
        'multi_streak_5'  =>['name'=>'Multi Master',     'icon'=>'🎮','desc'=>'5 multiplayer wins in a row','cat'=>'Streaks'],
        'multi_streak_10' =>['name'=>'Multi Dominator',  'icon'=>'🎯','desc'=>'10 multiplayer wins in a row','cat'=>'Streaks'],
        'daily_streak_3'  =>['name'=>'Daily Habit',      'icon'=>'📅','desc'=>'3 daily challenges in a row','cat'=>'Streaks'],
        'daily_streak_7'  =>['name'=>'Weekly Warrior',   'icon'=>'🗓️','desc'=>'7 daily challenges in a row','cat'=>'Streaks'],
        'daily_streak_14' =>['name'=>'Fortnight',        'icon'=>'🌙','desc'=>'14 daily challenges in a row','cat'=>'Streaks'],
        'daily_streak_30' =>['name'=>'Monthly Legend',   'icon'=>'📆','desc'=>'30 daily challenges in a row','cat'=>'Streaks'],
        'daily_all_april' =>['name'=>'April Champion',   'icon'=>'🌸','desc'=>'Complete every April 2026 daily','cat'=>'Streaks'],

        // ── SPEED (41-55)
        'speed_10s'       =>['name'=>'Speed Demon',      'icon'=>'🚀','desc'=>'Win in under 10 seconds','cat'=>'Speed'],
        'speed_5s'        =>['name'=>'Lightning',        'icon'=>'⚡','desc'=>'Win in under 5 seconds','cat'=>'Speed'],
        'speed_3s'        =>['name'=>'Telekinesis',      'icon'=>'🧠','desc'=>'Win in under 3 seconds','cat'=>'Speed'],
        'speed_hard_30s'  =>['name'=>'Hard Sprinter',    'icon'=>'🏃','desc'=>'Win Hard in under 30 seconds','cat'=>'Speed'],
        'speed_hard_15s'  =>['name'=>'Hard Dasher',      'icon'=>'💨','desc'=>'Win Hard in under 15 seconds','cat'=>'Speed'],
        'speed_multi_1st' =>['name'=>'First to Finish',  'icon'=>'🥇','desc'=>'Win a multiplayer game first','cat'=>'Speed'],
        'speed_multi_10s' =>['name'=>'Multi Flash',      'icon'=>'⚡','desc'=>'Win multiplayer in under 10 seconds','cat'=>'Speed'],
        'slow_burn'       =>['name'=>'Slow Burn',        'icon'=>'🕯️','desc'=>'Win with more than 5 minutes elapsed','cat'=>'Speed'],
        'no_rush'         =>['name'=>'Philosopher',      'icon'=>'🧘','desc'=>'Win taking over 3 minutes','cat'=>'Speed'],
        'marathon'        =>['name'=>'Marathon',         'icon'=>'🏃','desc'=>'Spend 60+ total minutes in-game','cat'=>'Speed'],
        'speed_10_games'  =>['name'=>'Quick Ten',        'icon'=>'⏱️','desc'=>'Win 10 games in under 20s each','cat'=>'Speed'],
        'freeze_master'   =>['name'=>'Time Lord',        'icon'=>'⏸️','desc'=>'Use Time Freeze powerup 10 times','cat'=>'Speed'],
        'speed_easy_2s'   =>['name'=>'Instant',         'icon'=>'💫','desc'=>'Win Easy in 2 seconds flat','cat'=>'Speed'],
        'avg_speed_15'    =>['name'=>'Efficient Runner', 'icon'=>'🎽','desc'=>'Average win time under 15s (min 10 wins)','cat'=>'Speed'],
        'speed_5_in_row'  =>['name'=>'Rapid Fire',      'icon'=>'🔫','desc'=>'Win 5 games each under 15 seconds','cat'=>'Speed'],

        // ── PRECISION (56-75)
        'one_shot'        =>['name'=>'Lucky Shot',       'icon'=>'🍀','desc'=>'Guess correctly on the first try','cat'=>'Precision'],
        'one_shot_medium' =>['name'=>'Medium Sniper',    'icon'=>'🎯','desc'=>'First-try win on Medium','cat'=>'Precision'],
        'one_shot_hard'   =>['name'=>'Hard Clairvoyant', 'icon'=>'🔮','desc'=>'First-try win on Hard','cat'=>'Precision'],
        'one_shot_3'      =>['name'=>'Hat Trick',        'icon'=>'🎩','desc'=>'First-try win 3 times','cat'=>'Precision'],
        'one_shot_10'     =>['name'=>'Oracle',           'icon'=>'🌀','desc'=>'First-try win 10 times','cat'=>'Precision'],
        'two_shot_hard'   =>['name'=>'Sharp Shooter',    'icon'=>'🏹','desc'=>'Win Hard in exactly 2 attempts','cat'=>'Precision'],
        'efficiency'      =>['name'=>'Efficient',        'icon'=>'📐','desc'=>'Win using 50% or fewer attempts','cat'=>'Precision'],
        'efficiency_10'   =>['name'=>'Masterful',        'icon'=>'🎓','desc'=>'Win efficiently 10 times','cat'=>'Precision'],
        'no_hint_win'     =>['name'=>'Unaided',          'icon'=>'🙅','desc'=>'Win without using any hints','cat'=>'Precision'],
        'no_hint_10'      =>['name'=>'Thrifty',          'icon'=>'💵','desc'=>'Win 10 games without hints','cat'=>'Precision'],
        'no_hint_50'      =>['name'=>'Pure Mind',        'icon'=>'🧠','desc'=>'Win 50 games without hints','cat'=>'Precision'],
        'no_powerup_win'  =>['name'=>'Naked',            'icon'=>'🤷','desc'=>'Win without any powerups','cat'=>'Precision'],
        'max_attempt_win' =>['name'=>'Clutch',           'icon'=>'😰','desc'=>'Win on your very last attempt','cat'=>'Precision'],
        'max_attempt_3'   =>['name'=>'Clutch Artist',    'icon'=>'😅','desc'=>'Clutch-win 3 times','cat'=>'Precision'],
        'binary_search'   =>['name'=>'Computer Brain',   'icon'=>'💻','desc'=>'Win Hard using only 4 attempts','cat'=>'Precision'],
        'mid_guess'       =>['name'=>'Midpoint Master',  'icon'=>'↔️','desc'=>'Win by guessing the midpoint first','cat'=>'Precision'],
        'no_hint_hard'    =>['name'=>'Hard & Pure',      'icon'=>'💎','desc'=>'Win Hard with no hints or powerups','cat'=>'Precision'],
        'perfect_game'    =>['name'=>'Perfect Game',     'icon'=>'🌟','desc'=>'Win on first try with no powerups','cat'=>'Precision'],
        'half_range_win'  =>['name'=>'Bisector',         'icon'=>'✂️','desc'=>'Win Medium in exactly 3 attempts','cat'=>'Precision'],
        'precision_10'    =>['name'=>'Laser Focus',      'icon'=>'🔦','desc'=>'Win 10 games with exactly 1 attempt left','cat'=>'Precision'],

        // ── LEVELS & XP (76-90)
        'level_5'         =>['name'=>'Rookie',           'icon'=>'🥉','desc'=>'Reach level 5','cat'=>'Progression'],
        'level_10'        =>['name'=>'Initiate',         'icon'=>'🌟','desc'=>'Reach level 10','cat'=>'Progression'],
        'level_25'        =>['name'=>'Adept',            'icon'=>'⭐','desc'=>'Reach level 25','cat'=>'Progression'],
        'level_50'        =>['name'=>'Elite',            'icon'=>'💎','desc'=>'Reach level 50','cat'=>'Progression'],
        'level_75'        =>['name'=>'Master',           'icon'=>'🔶','desc'=>'Reach level 75','cat'=>'Progression'],
        'level_100'       =>['name'=>'Grandmaster',      'icon'=>'👑','desc'=>'Reach level 100','cat'=>'Progression'],
        'level_200'       =>['name'=>'Transcendent',     'icon'=>'🌌','desc'=>'Reach level 200','cat'=>'Progression'],
        'level_500'       =>['name'=>'Eternal',          'icon'=>'♾️','desc'=>'Reach level 500','cat'=>'Progression'],
        'level_1000'      =>['name'=>'Omniscient',       'icon'=>'🌠','desc'=>'Reach level 1000','cat'=>'Progression'],
        'xp_1000'         =>['name'=>'XP Rookie',        'icon'=>'📈','desc'=>'Earn 1,000 total XP','cat'=>'Progression'],
        'xp_10000'        =>['name'=>'XP Hunter',        'icon'=>'📊','desc'=>'Earn 10,000 total XP','cat'=>'Progression'],
        'xp_100000'       =>['name'=>'XP Lord',          'icon'=>'🏔️','desc'=>'Earn 100,000 total XP','cat'=>'Progression'],
        'xp_bonus_100'    =>['name'=>'Bonus Collector',  'icon'=>'🎁','desc'=>'Earn 100 achievement bonus XP in one game','cat'=>'Progression'],
        'level_up_fast'   =>['name'=>'Turbo Leveler',    'icon'=>'🚀','desc'=>'Level up 5 times in a single day','cat'=>'Progression'],
        'prestige'        =>['name'=>'Prestige',         'icon'=>'🎖️','desc'=>'Reach max level 1000','cat'=>'Progression'],

        // ── COINS & ECONOMY (91-107)
        'coins_100'       =>['name'=>'Pocket Change',    'icon'=>'🪙','desc'=>'Accumulate 100 coins','cat'=>'Economy'],
        'coins_500'       =>['name'=>'Moneybags',        'icon'=>'💰','desc'=>'Accumulate 500 coins','cat'=>'Economy'],
        'coins_1000'      =>['name'=>'Grand',            'icon'=>'💵','desc'=>'Accumulate 1,000 coins','cat'=>'Economy'],
        'coins_5000'      =>['name'=>'High Roller',      'icon'=>'🎰','desc'=>'Accumulate 5,000 coins','cat'=>'Economy'],
        'coins_10000'     =>['name'=>'Millionaire',      'icon'=>'🤑','desc'=>'Accumulate 10,000 coins','cat'=>'Economy'],
        'shop_1'          =>['name'=>'First Purchase',   'icon'=>'🛍️','desc'=>'Buy your first shop item','cat'=>'Economy'],
        'shopaholic'      =>['name'=>'Shopaholic',       'icon'=>'🛒','desc'=>'Buy 10 items from the shop','cat'=>'Economy'],
        'shop_50'         =>['name'=>'Big Spender',      'icon'=>'💸','desc'=>'Buy 50 items from the shop','cat'=>'Economy'],
        'shop_100'        =>['name'=>'Mogul',            'icon'=>'🏦','desc'=>'Buy 100 items from the shop','cat'=>'Economy'],
        'double_coins_5'  =>['name'=>'Doubler',          'icon'=>'×2','desc'=>'Use Coin Doubler 5 times','cat'=>'Economy'],
        'coins_spent_500' =>['name'=>'Investor',         'icon'=>'📉','desc'=>'Spend 500 coins total','cat'=>'Economy'],
        'coins_earned_day'=>['name'=>'Daily Earner',     'icon'=>'💴','desc'=>'Earn 100 coins in a single day','cat'=>'Economy'],
        'bargain'         =>['name'=>'Bargain Hunter',   'icon'=>'🏷️','desc'=>'Buy the cheapest item 20 times','cat'=>'Economy'],
        'max_stock'       =>['name'=>'Stocked Up',       'icon'=>'📦','desc'=>'Max stock on any one powerup','cat'=>'Economy'],
        'broke'           =>['name'=>'Reckless',         'icon'=>'💸','desc'=>'Spend your last coin','cat'=>'Economy'],
        'savings'         =>['name'=>'Saver',            'icon'=>'🏧','desc'=>'Hold 500+ coins for 7 days','cat'=>'Economy'],
        'tip_jar'         =>['name'=>'Generous',         'icon'=>'🫙','desc'=>'Spend 1000 total coins','cat'=>'Economy'],

        // ── POWERUP MASTERY (108-120)
        'power_player'    =>['name'=>'Power Player',     'icon'=>'🔌','desc'=>'Use a power-up to win','cat'=>'Powerups'],
        'powerup_10'      =>['name'=>'Power Junkie',     'icon'=>'💉','desc'=>'Use 10 powerups total','cat'=>'Powerups'],
        'powerup_50'      =>['name'=>'Power Addict',     'icon'=>'⚗️','desc'=>'Use 50 powerups total','cat'=>'Powerups'],
        'powerup_100'     =>['name'=>'Power Lord',       'icon'=>'🔋','desc'=>'Use 100 powerups total','cat'=>'Powerups'],
        'all_powerups'    =>['name'=>'Full Arsenal',     'icon'=>'🗄️','desc'=>'Use every type of powerup at least once','cat'=>'Powerups'],
        'range_master'    =>['name'=>'Range Master',     'icon'=>'🔭','desc'=>'Use Range Narrower 20 times','cat'=>'Powerups'],
        'reveal_master'   =>['name'=>'Reveal Master',    'icon'=>'🔮','desc'=>'Use Digit Revealer 10 times','cat'=>'Powerups'],
        'extra_life_10'   =>['name'=>'Nine Lives',       'icon'=>'🐱','desc'=>'Use Extra Life 10 times','cat'=>'Powerups'],
        'clone_master'    =>['name'=>'Doppelganger',     'icon'=>'👥','desc'=>'Use Shadow Guess 10 times','cat'=>'Powerups'],
        'bomb_master'     =>['name'=>'Bomb Specialist',  'icon'=>'💣','desc'=>'Use Range Bomb 5 times to win','cat'=>'Powerups'],
        'oracle_use'      =>['name'=>'Touched by Oracle','icon'=>'🌙','desc'=>'Use Hot/Cold Oracle 5 times','cat'=>'Powerups'],
        'shield_use'      =>['name'=>'Shielded',         'icon'=>'🛡️','desc'=>'Use Mistake Shield 5 times','cat'=>'Powerups'],
        'combo_powerup'   =>['name'=>'Combo Breaker',    'icon'=>'🔀','desc'=>'Use 3 different powerups in one game','cat'=>'Powerups'],

        // ── SOCIAL / MULTIPLAYER (121-140)
        'host_5'          =>['name'=>'Game Host',        'icon'=>'🎙️','desc'=>'Host 5 multiplayer rooms','cat'=>'Social'],
        'host_20'         =>['name'=>'Party Starter',    'icon'=>'🎉','desc'=>'Host 20 multiplayer rooms','cat'=>'Social'],
        'join_10'         =>['name'=>'Joiner',           'icon'=>'🚪','desc'=>'Join 10 multiplayer rooms','cat'=>'Social'],
        'play_multi_25'   =>['name'=>'Social Butterfly', 'icon'=>'🦋','desc'=>'Play 25 multiplayer games','cat'=>'Social'],
        'play_multi_100'  =>['name'=>'Arena Fighter',    'icon'=>'🥊','desc'=>'Play 100 multiplayer games','cat'=>'Social'],
        'multi_win_25'    =>['name'=>'Champion',         'icon'=>'🏅','desc'=>'Win 25 multiplayer games','cat'=>'Social'],
        'multi_win_100'   =>['name'=>'Gladiator',        'icon'=>'⚔️','desc'=>'Win 100 multiplayer games','cat'=>'Social'],
        'chat_10'         =>['name'=>'Chatterbox',       'icon'=>'💬','desc'=>'Send 10 chat messages','cat'=>'Social'],
        'chat_100'        =>['name'=>'Social Director',  'icon'=>'📢','desc'=>'Send 100 chat messages','cat'=>'Social'],
        'beat_3_players'  =>['name'=>'Triple Threat',    'icon'=>'🎯','desc'=>'Win a 4-player room','cat'=>'Social'],
        'last_stand'      =>['name'=>'Last Stand',       'icon'=>'🗡️','desc'=>'Win when all others have lost','cat'=>'Social'],
        'multi_no_hint'   =>['name'=>'Pure Duelist',     'icon'=>'🏹','desc'=>'Win multiplayer with no powerups or hints','cat'=>'Social'],
        'beat_streak'     =>['name'=>'Streak Breaker',   'icon'=>'💢','desc'=>'Beat a player on a win streak','cat'=>'Social'],
        'comeback_multi'  =>['name'=>'Clutch Multi',     'icon'=>'😤','desc'=>'Win multiplayer on your last attempt','cat'=>'Social'],
        'max_players'     =>['name'=>'Full House',       'icon'=>'🏠','desc'=>'Play in a full 4-player room','cat'=>'Social'],
        'win_all_diffs_mp'=>['name'=>'All Rounder',      'icon'=>'🌐','desc'=>'Win MP on Easy, Medium & Hard','cat'=>'Social'],
        'spectator'       =>['name'=>'Spectator',        'icon'=>'👁️','desc'=>'Lose a multiplayer game but watch to the end','cat'=>'Social'],
        'rematch'         =>['name'=>'Rematch!',         'icon'=>'🔁','desc'=>'Play the same opponent twice','cat'=>'Social'],
        'diplomatic'      =>['name'=>'Diplomatic',       'icon'=>'🤝','desc'=>'Tie in a multiplayer game','cat'=>'Social'],
        'solo_vs_team'    =>['name'=>'Lone Wolf',        'icon'=>'🐺','desc'=>'Win a multiplayer room as the only undefeated player','cat'=>'Social'],

        // ── DAILY CHALLENGES (141-155)
        'daily_10'        =>['name'=>'10 Days Strong',   'icon'=>'📅','desc'=>'Complete 10 daily challenges','cat'=>'Daily'],
        'daily_25'        =>['name'=>'Monthly Medal',    'icon'=>'🎖️','desc'=>'Complete 25 daily challenges','cat'=>'Daily'],
        'daily_50'        =>['name'=>'Daily Veteran',    'icon'=>'🌟','desc'=>'Complete 50 daily challenges','cat'=>'Daily'],
        'daily_100'       =>['name'=>'Daily Legend',     'icon'=>'👑','desc'=>'Complete 100 daily challenges','cat'=>'Daily'],
        'daily_first_try' =>['name'=>'Daily Ace',        'icon'=>'🎯','desc'=>'Win a daily challenge first try','cat'=>'Daily'],
        'daily_fast'      =>['name'=>'Daily Speedrun',   'icon'=>'⚡','desc'=>'Win a daily in under 15 seconds','cat'=>'Daily'],
        'daily_no_hint'   =>['name'=>'Daily Pure',       'icon'=>'💎','desc'=>'Win a daily with no hints','cat'=>'Daily'],
        'daily_no_power'  =>['name'=>'Daily Raw',        'icon'=>'🔩','desc'=>'Win a daily with no powerups','cat'=>'Daily'],
        'daily_monday'    =>['name'=>'Manic Monday',     'icon'=>'😤','desc'=>'Win a Monday daily challenge','cat'=>'Daily'],
        'daily_weekend'   =>['name'=>'Weekend Warrior',  'icon'=>'🎉','desc'=>'Win a Saturday or Sunday daily','cat'=>'Daily'],
        'april_fools'     =>['name'=>"It's Always 42",   'icon'=>'🤡','desc'=>"Win the April Fools' Day challenge",'cat'=>'Daily'],
        'april_pi'        =>['name'=>'Mathematician',    'icon'=>'π','desc'=>'Win the Pi Day challenge (Apr 14)','cat'=>'Daily'],
        'april_777'       =>['name'=>'Jackpot',          'icon'=>'🎰','desc'=>'Win the Triple 7s challenge (Apr 18)','cat'=>'Daily'],
        'april_last_day'  =>['name'=>'April Finale',     'icon'=>'🌸','desc'=>'Win the April 30 daily challenge','cat'=>'Daily'],
        'daily_clutch'    =>['name'=>'Daily Clutch',     'icon'=>'😰','desc'=>'Win a daily on your very last attempt','cat'=>'Daily'],

        // ── SPECIAL & HIDDEN (156-175)
        'lucky_number'    =>['name'=>'Lucky Number',     'icon'=>'🎲','desc'=>'Guess 7 correctly in any game','cat'=>'Special'],
        'guess_42'        =>['name'=>'Hitchhiker',       'icon'=>'🌌','desc'=>'Guess 42 as an answer that is correct','cat'=>'Special'],
        'guess_69'        =>['name'=>'Nice.',            'icon'=>'😏','desc'=>'Guess 69 as a correct answer','cat'=>'Special'],
        'guess_100'       =>['name'=>'Centurion Guess',  'icon'=>'💯','desc'=>'Guess exactly 100 as a correct answer','cat'=>'Special'],
        'palindrome_win'  =>['name'=>'Palindrome',       'icon'=>'🔂','desc'=>'Win with a palindrome number (11,22,33...)','cat'=>'Special'],
        'prime_win'       =>['name'=>'Prime Time',       'icon'=>'🔢','desc'=>'Win with a prime number','cat'=>'Special'],
        'even_win'        =>['name'=>'Even Stevens',     'icon'=>'⚖️','desc'=>'Win 5 times with an even number','cat'=>'Special'],
        'odd_win'         =>['name'=>'Odd One Out',      'icon'=>'🎭','desc'=>'Win 5 times with an odd number','cat'=>'Special'],
        'midnight_game'   =>['name'=>'Night Owl',        'icon'=>'🦉','desc'=>'Play between midnight and 3am','cat'=>'Special'],
        'morning_game'    =>['name'=>'Early Bird',       'icon'=>'🐦','desc'=>'Play before 7am','cat'=>'Special'],
        'play_1000'       =>['name'=>'One Thousand',     'icon'=>'🔱','desc'=>'Play 1000 total games','cat'=>'Special'],
        'zero_coins'      =>['name'=>'Broke & Winning',  'icon'=>'💔','desc'=>'Win a game with 0 coins','cat'=>'Special'],
        'max_1000'        =>['name'=>'Max Number',       'icon'=>'🏔️','desc'=>'Win when the secret is 1000','cat'=>'Special'],
        'min_1_win'       =>['name'=>'The One',          'icon'=>'☝️','desc'=>'Win when the secret is 1','cat'=>'Special'],
        'guess_all_wrong' =>['name'=>'So Close',         'icon'=>'😭','desc'=>'Miss by 1 on your final attempt','cat'=>'Special'],
        'guess_same_twice'=>['name'=>'Forgetful',        'icon'=>'🤔','desc'=>'Guess the same number twice in a row','cat'=>'Special'],
        'long_game'       =>['name'=>'Endurance',        'icon'=>'🏋️','desc'=>'Use all attempts and win','cat'=>'Special'],
        'weekend_binge'   =>['name'=>'Weekend Binge',    'icon'=>'📺','desc'=>'Play 20 games on a single day','cat'=>'Special'],
        'session_100'     =>['name'=>'Session King',     'icon'=>'🖥️','desc'=>'Log in 100 times','cat'=>'Special'],
        'seasonal_spring' =>['name'=>'Spring Bloom',     'icon'=>'🌸','desc'=>'Play in spring (March-May)','cat'=>'Special'],

        // ── PLAY COUNT (176-185)
        'play_10'         =>['name'=>'Warming Up',       'icon'=>'🔥','desc'=>'Play 10 games','cat'=>'Milestones'],
        'play_25'         =>['name'=>'Quarter Hundred',  'icon'=>'🎖️','desc'=>'Play 25 games','cat'=>'Milestones'],
        'play_50'         =>['name'=>'Half Century',     'icon'=>'🌗','desc'=>'Play 50 games','cat'=>'Milestones'],
        'play_100'        =>['name'=>'Centurion Player', 'icon'=>'💯','desc'=>'Play 100 games','cat'=>'Milestones'],
        'play_250'        =>['name'=>'Marathon Player',  'icon'=>'🏅','desc'=>'Play 250 games','cat'=>'Milestones'],
        'play_500'        =>['name'=>'Epic Journey',     'icon'=>'🗺️','desc'=>'Play 500 games','cat'=>'Milestones'],
        'play_1000'       =>['name'=>'Legendary Grind',  'icon'=>'👑','desc'=>'Play 1000 games','cat'=>'Milestones'],
        'lose_10'         =>['name'=>'Losing Streak',    'icon'=>'😔','desc'=>'Lose 10 games — keep going!','cat'=>'Milestones'],
        'lose_50'         =>['name'=>'Perseverance',     'icon'=>'💪','desc'=>'Lose 50 games — you never quit','cat'=>'Milestones'],
        'play_all_modes'  =>['name'=>'Diversified',      'icon'=>'🌈','desc'=>'Win on all 3 difficulties in one session','cat'=>'Milestones'],

        // ── THEMES & COSMETICS (186-195)
        'unlock_ocean'    =>['name'=>'Ocean Diver',      'icon'=>'🌊','desc'=>'Unlock the Deep Ocean theme','cat'=>'Cosmetics'],
        'unlock_forest'   =>['name'=>'Forest Walker',    'icon'=>'🌲','desc'=>'Unlock the Ancient Forest theme','cat'=>'Cosmetics'],
        'unlock_volcano'  =>['name'=>'Magma Surfer',     'icon'=>'🌋','desc'=>'Unlock the Volcano theme','cat'=>'Cosmetics'],
        'unlock_galaxy'   =>['name'=>'Star Traveler',    'icon'=>'🌌','desc'=>'Unlock the Galaxy theme','cat'=>'Cosmetics'],
        'unlock_gold'     =>['name'=>'Gilded',           'icon'=>'✨','desc'=>'Unlock the Gold Rush theme','cat'=>'Cosmetics'],
        'theme_changer'   =>['name'=>'Fashionista',      'icon'=>'👗','desc'=>'Change your theme 3 times','cat'=>'Cosmetics'],
        'dark_mode'       =>['name'=>'Night Mode',       'icon'=>'🌙','desc'=>'Play with Dark Mode enabled','cat'=>'Cosmetics'],
        'all_themes'      =>['name'=>'Collector',        'icon'=>'🗂️','desc'=>'Unlock all 6 themes','cat'=>'Cosmetics'],
        'play_light_mode' =>['name'=>'Sunshine',         'icon'=>'☀️','desc'=>'Win a game in Light Mode','cat'=>'Cosmetics'],
        'profile_complete'=>['name'=>'Complete Profile', 'icon'=>'✅','desc'=>'Set a theme and win on all difficulties','cat'=>'Cosmetics'],

        // ── MISC BONUS (196-200)
        'hint_100'        =>['name'=>'Hint Addict',      'icon'=>'💡','desc'=>'Use 100 total hints','cat'=>'Misc'],
        'hint_xp_1000'    =>['name'=>'Hint Rich',        'icon'=>'💎','desc'=>'Accumulate 1000 Hint XP','cat'=>'Misc'],
        'win_by_1'        =>['name'=>'Razor Edge',       'icon'=>'🪒','desc'=>'Win when the secret is min or max of range','cat'=>'Misc'],
        'symmetry'        =>['name'=>'Symmetry',         'icon'=>'🪞','desc'=>'Win 10 games where won+lost=even','cat'=>'Misc'],
        'comeback_200'    =>['name'=>'The Phoenix',      'icon'=>'🔥','desc'=>'Win after your longest losing streak (5+)','cat'=>'Misc'],
    ];

    // ── THEMES ──────────────────────────────────────────────────────────
    private $themes = [
        'default'=>['name'=>'Neon Noir',     'unlock_level'=>1,  'icon'=>'🌃'],
        'ocean'  =>['name'=>'Deep Ocean',    'unlock_level'=>5,  'icon'=>'🌊'],
        'forest' =>['name'=>'Ancient Forest','unlock_level'=>10, 'icon'=>'🌲'],
        'volcano'=>['name'=>'Volcano',       'unlock_level'=>20, 'icon'=>'🌋'],
        'galaxy' =>['name'=>'Galaxy',        'unlock_level'=>35, 'icon'=>'🌌'],
        'gold'   =>['name'=>'Gold Rush',     'unlock_level'=>50, 'icon'=>'✨'],
    ];

    // ── 11 POWERUP SHOP ITEMS ────────────────────────────────────────────
    private $shopItems = [
        // Original 6
        'range_narrow'  =>['name'=>'Range Narrower', 'icon'=>'🔭','desc'=>'Narrows the number range by 50%',              'cost'=>30, 'max'=>5],
        'reveal_digit'  =>['name'=>'Digit Revealer', 'icon'=>'🔮','desc'=>'Reveals the last digit of the secret number',  'cost'=>50, 'max'=>3],
        'extra_attempt' =>['name'=>'Extra Life',     'icon'=>'❤️', 'desc'=>'Grants +1 extra attempt this game',           'cost'=>40, 'max'=>3],
        'freeze_timer'  =>['name'=>'Time Freeze',    'icon'=>'⏸️', 'desc'=>'Pauses your timer for 30 seconds',            'cost'=>25, 'max'=>5],
        'double_coins'  =>['name'=>'Coin Doubler',   'icon'=>'🪙', 'desc'=>'Doubles coins earned on your next win',       'cost'=>60, 'max'=>2],
        'hint_boost'    =>['name'=>'Hint Refill',    'icon'=>'💡', 'desc'=>'Gives +100 Hint XP immediately',              'cost'=>35, 'max'=>10],
        // NEW 5
        'shadow_guess'  =>['name'=>'Shadow Guess',   'icon'=>'👥', 'desc'=>'Your next wrong guess does NOT cost an attempt','cost'=>55, 'max'=>3],
        'range_bomb'    =>['name'=>'Range Bomb',     'icon'=>'💣', 'desc'=>'Eliminates a random 25% chunk of wrong range', 'cost'=>45, 'max'=>4],
        'hot_cold_oracle'=>['name'=>'Hot/Cold Oracle','icon'=>'🌡️','desc'=>'Tells you if secret is in top or bottom 25%',  'cost'=>38, 'max'=>5],
        'mistake_shield'=>['name'=>'Mistake Shield', 'icon'=>'🛡️', 'desc'=>'If you lose, keep your streak (once)',         'cost'=>80, 'max'=>2],
        'xp_surge'      =>['name'=>'XP Surge',       'icon'=>'📈', 'desc'=>'Next win gives 50% bonus XP',                 'cost'=>70, 'max'=>3],
    ];

    private $usersFile = __DIR__.'/ng_users.json';
    private $dailyFile = __DIR__.'/ng_daily.json';
    private $roomsFile = __DIR__.'/ng_rooms.json';

    public function getDifficulties(){ return $this->games; }
    public function getGameConfig($d){ return $this->games[$d]??null; }
    public function getAchievements(){ return $this->achievements; }
    public function getAchievementsByCategory(){
        $cats=[];
        foreach($this->achievements as $k=>$a){
            $c=$a['cat']??'Misc';
            $cats[$c][$k]=$a;
        }
        return $cats;
    }
    public function getThemes(){ return $this->themes; }
    public function getShopItems(){ return $this->shopItems; }
    public function generateNumber($mn,$mx){ return rand($mn,$mx); }
    public function getAprilCalendar(){ return $this->aprilCalendar; }

    // ── USERS ─────────────────────────────────────────────────────────────
    public function loadUsers(){
        if(file_exists($this->usersFile))
            return json_decode(file_get_contents($this->usersFile),true)??[];
        return [];
    }
    public function saveUsers($u){ file_put_contents($this->usersFile,json_encode($u,JSON_PRETTY_PRINT)); }

    public function registerUser($username,$password){
        $users=$this->loadUsers();
        if(isset($users[$username]))return false;
        $users[$username]=[
            'password'=>password_hash($password,PASSWORD_DEFAULT),
            'level'=>1,'experience'=>0,'games_won'=>0,'games_played'=>0,'games_lost'=>0,
            'streak'=>0,'best_streak'=>0,'losing_streak'=>0,'max_losing_streak'=>0,
            'hints_used'=>0,'hint_xp'=>200,'achievements'=>[],'theme'=>'default','dark_mode'=>true,
            'powerups'=>[
                'range_narrow'=>2,'reveal_digit'=>1,'extra_attempt'=>0,'freeze_timer'=>0,
                'double_coins'=>0,'hint_boost'=>0,'shadow_guess'=>0,'range_bomb'=>0,
                'hot_cold_oracle'=>0,'mistake_shield'=>0,'xp_surge'=>0,
            ],
            'game_history'=>[],'no_hint_wins'=>0,'no_powerup_wins'=>0,'coins'=>50,
            'created_at'=>time(),'last_daily'=>null,'daily_streak'=>0,'daily_total'=>0,
            'diff_stats'=>['Easy'=>['w'=>0,'p'=>0],'Medium'=>['w'=>0,'p'=>0],'Hard'=>['w'=>0,'p'=>0]],
            'shop_purchases'=>0,'coins_spent'=>0,'multi_wins'=>0,'multi_streak'=>0,
            'multi_played'=>0,'multi_losses'=>0,'coin_doubler_active'=>false,
            'xp_surge_active'=>false,'shield_active'=>false,'theme_changes'=>0,
            'login_count'=>1,'last_login'=>date('Y-m-d'),'total_minutes'=>0,
            'one_shot_count'=>0,'efficiency_count'=>0,'clutch_count'=>0,
            'chat_messages'=>0,'rooms_hosted'=>0,'rooms_joined'=>0,'powerups_used'=>0,
            'powerup_type_used'=>[],'april_completed'=>[],'session_wins_today'=>0,
            'last_session_date'=>date('Y-m-d'),'consecutive_losses'=>0,'fast_wins_10s'=>0,
        ];
        $this->saveUsers($users); return true;
    }
    public function loginUser($u,$p){
        $users=$this->loadUsers();
        if(!isset($users[$u])||!password_verify($p,$users[$u]['password']))return false;
        // track login
        $users[$u]['login_count']=($users[$u]['login_count']??0)+1;
        $users[$u]['last_login']=date('Y-m-d');
        $this->saveUsers($users);
        return true;
    }
    public function getUser($u){ $users=$this->loadUsers(); return $users[$u]??null; }
    public function updateUser($username,$data){
        $users=$this->loadUsers();
        if(!isset($users[$username]))return;
        $users[$username]=array_merge($users[$username],$data);
        $this->saveUsers($users);
    }

    // ── DAILY ─────────────────────────────────────────────────────────────
    public function getDailyChallenge(){
        $today=date('Y-m-d');
        // Check April 2026 calendar first
        if(isset($this->aprilCalendar[$today])){
            $c=$this->aprilCalendar[$today];
            return array_merge($c,['date'=>$today]);
        }
        // Fallback: seeded random
        if(file_exists($this->dailyFile)){
            $d=json_decode(file_get_contents($this->dailyFile),true);
            if($d&&($d['date']??'')===$today)return $d;
        }
        $seed=crc32($today); srand($seed); $secret=rand(1,500); srand();
        $daily=['date'=>$today,'min'=>1,'max'=>500,'secret'=>$secret,'attempts'=>8,'note'=>''];
        file_put_contents($this->dailyFile,json_encode($daily));
        return $daily;
    }

    // ── SHOP ──────────────────────────────────────────────────────────────
    public function buyItem($username,$item){
        $users=$this->loadUsers();
        if(!isset($users[$username]))return['ok'=>false,'msg'=>'User not found'];
        $u=&$users[$username];
        $shop=$this->shopItems;
        if(!isset($shop[$item]))return['ok'=>false,'msg'=>'Item not found'];
        $it=$shop[$item];
        $cur=$u['powerups'][$item]??0;
        if($cur>=$it['max'])return['ok'=>false,'msg'=>'Max stock reached ('.$it['max'].')'];
        if(($u['coins']??0)<$it['cost'])return['ok'=>false,'msg'=>'Not enough coins (need '.$it['cost'].')'];
        $u['coins']-=$it['cost'];
        $u['coins_spent']=($u['coins_spent']??0)+$it['cost'];
        $u['powerups'][$item]=($u['powerups'][$item]??0)+1;
        $u['shop_purchases']=($u['shop_purchases']??0)+1;
        $newAch=[];
        $shopAchChecks=['shop_1'=>1,'shopaholic'=>10,'shop_50'=>50,'shop_100'=>100];
        foreach($shopAchChecks as $k=>$thresh)
            if($u['shop_purchases']>=$thresh&&!in_array($k,$u['achievements']??[])){
                $u['achievements'][]=$k; $newAch[]=$k;
            }
        // max_stock
        if($u['powerups'][$item]>=$it['max']&&!in_array('max_stock',$u['achievements']??[])){
            $u['achievements'][]='max_stock'; $newAch[]='max_stock';
        }
        // broke — spent last coin
        if($u['coins']<=0&&!in_array('broke',$u['achievements']??[])){
            $u['achievements'][]='broke'; $newAch[]='broke';
        }
        // tip_jar
        if(($u['coins_spent']??0)>=1000&&!in_array('tip_jar',$u['achievements']??[])){
            $u['achievements'][]='tip_jar'; $newAch[]='tip_jar';
        }
        $this->saveUsers($users);
        return['ok'=>true,'coins'=>$u['coins'],'new_ach'=>$newAch];
    }

    // ── ROOMS ─────────────────────────────────────────────────────────────
    public function loadRooms(){
        if(file_exists($this->roomsFile))
            return json_decode(file_get_contents($this->roomsFile),true)??[];
        return [];
    }
    public function saveRooms($r){ file_put_contents($this->roomsFile,json_encode($r,JSON_PRETTY_PRINT)); }

    public function createRoom($host,$difficulty,$maxPlayers=4){
        $rooms=$this->loadRooms();
        $now=time();
        foreach($rooms as $k=>$r){ if($now-($r['created_at']??0)>1800)unset($rooms[$k]); }
        $code=strtoupper(substr(md5(uniqid()),0,6));
        $cfg=$this->games[$difficulty];
        $secret=$this->generateNumber($cfg['min'],$cfg['max']);
        $rooms[$code]=[
            'code'=>$code,'host'=>$host,'difficulty'=>$difficulty,'status'=>'waiting',
            'secret'=>$secret,'min'=>$cfg['min'],'max'=>$cfg['max'],'max_attempts'=>$cfg['attempts'],
            'max_players'=>$maxPlayers,'created_at'=>$now,'started_at'=>null,
            'players'=>[$host=>['joined'=>$now,'guesses'=>[],'attempts'=>0,'won'=>false,'finish_time'=>null,'ready'=>true]],
            'chat'=>[],
        ];
        $this->saveRooms($rooms);
        // track host stat
        $users=$this->loadUsers();
        if(isset($users[$host])){$users[$host]['rooms_hosted']=($users[$host]['rooms_hosted']??0)+1;$this->saveUsers($users);}
        return $code;
    }
    public function joinRoom($code,$username){
        $rooms=$this->loadRooms(); $code=strtoupper(trim($code));
        if(!isset($rooms[$code]))return['ok'=>false,'msg'=>'Room not found'];
        $r=&$rooms[$code];
        if($r['status']==='finished')return['ok'=>false,'msg'=>'Game already finished'];
        if(count($r['players'])>=$r['max_players']&&!isset($r['players'][$username]))
            return['ok'=>false,'msg'=>'Room is full'];
        if(!isset($r['players'][$username])){
            $r['players'][$username]=['joined'=>time(),'guesses'=>[],'attempts'=>0,'won'=>false,'finish_time'=>null,'ready'=>false];
            $users=$this->loadUsers();
            if(isset($users[$username])){$users[$username]['rooms_joined']=($users[$username]['rooms_joined']??0)+1;$this->saveUsers($users);}
        }
        $this->saveRooms($rooms);
        return['ok'=>true,'code'=>$code];
    }
    public function getRoom($code){ $rooms=$this->loadRooms(); return $rooms[strtoupper($code)]??null; }
    public function startRoom($code,$host){
        $rooms=$this->loadRooms(); $code=strtoupper($code);
        if(!isset($rooms[$code]))return false;
        if($rooms[$code]['host']!==$host)return false;
        if(count($rooms[$code]['players'])<2)return false;
        $rooms[$code]['status']='active'; $rooms[$code]['started_at']=time();
        $this->saveRooms($rooms); return true;
    }
    public function roomGuess($code,$username,$guess){
        $rooms=$this->loadRooms(); $code=strtoupper($code);
        if(!isset($rooms[$code]))return['ok'=>false,'msg'=>'Room gone'];
        $r=&$rooms[$code];
        if($r['status']!=='active')return['ok'=>false,'msg'=>'Game not active'];
        if(!isset($r['players'][$username]))return['ok'=>false,'msg'=>'Not in room'];
        $p=&$r['players'][$username];
        if($p['won']||$p['attempts']>=$r['max_attempts'])return['ok'=>false,'msg'=>'Already done'];
        $p['guesses'][]=$guess; $p['attempts']++;
        $secret=$r['secret']; $won=($guess==$secret);
        if($won){ $p['won']=true; $p['finish_time']=time()-$r['started_at']; }
        elseif($p['attempts']>=$r['max_attempts']){ $p['finish_time']=time()-$r['started_at']; }
        $allDone=true;
        foreach($r['players'] as $pl){ if(!$pl['won']&&$pl['attempts']<$r['max_attempts']){$allDone=false;break;} }
        if($allDone) $r['status']='finished';
        $this->saveRooms($rooms);
        $dir=$guess<$secret?'higher':($guess>$secret?'lower':'exact');
        return['ok'=>true,'result'=>$won?'win':($p['attempts']>=$r['max_attempts']?'lose':'continue'),'direction'=>$dir,'secret'=>$won||$r['status']==='finished'?$secret:null];
    }
    public function addRoomChat($code,$username,$msg){
        $rooms=$this->loadRooms(); $code=strtoupper($code);
        if(!isset($rooms[$code]))return;
        $r=&$rooms[$code];
        $r['chat']=array_slice([...$r['chat'],['u'=>$username,'m'=>substr(strip_tags($msg),0,100),'t'=>time()]],-50);
        $this->saveRooms($rooms);
        // track chat count
        $users=$this->loadUsers();
        if(isset($users[$username])){
            $users[$username]['chat_messages']=($users[$username]['chat_messages']??0)+1;
            $this->saveUsers($users);
        }
    }

    // ── COMPREHENSIVE ACHIEVEMENT CHECK ───────────────────────────────────
    public function checkAchievements(&$u, $context=[]){
        $new=[];
        $ach=&$u['achievements'];
        $has=fn($k)=>in_array($k,$ach);
        $add=function($k) use (&$ach,&$new,&$has){
            if(!$has($k)){$ach[]=$k;$new[]=$k;}
        };

        $won=$context['won']??false;
        $diff=$context['difficulty']??'Easy';
        $att=$context['attempts_used']??1;
        $time=$context['time_secs']??0;
        $hints=$context['hints_used']??0;
        $puUsed=$context['powerup_used']??false;
        $isMulti=$context['is_multi']??false;
        $isDaily=$context['is_daily']??false;
        $secret=$context['secret']??0;
        $maxAtt=$context['max_attempts']??5;
        $cfg=$this->games[$diff]??['attempts'=>5];
        $date=$context['date']??date('Y-m-d');
        $hour=(int)date('G');
        $dayOfWeek=(int)date('N'); // 1=Mon 7=Sun

        // ── Beginner ──
        $add('first_game');
        if($won) $add('first_win');
        if($won&&$diff==='Easy') $add('first_easy');
        if($won&&$diff==='Medium') $add('first_medium');
        if($won&&$diff==='Hard') $add('first_hard');
        if($isDaily&&$won) $add('first_daily');
        if($isMulti) $add('first_multi');
        if($isMulti&&$won) $add('first_multi_win');
        if($hints>0) $add('first_hint');
        if($puUsed) $add('first_powerup');

        // ── Win milestones ──
        if($won){
            foreach([5=>0,10=>0,25=>0,50=>0,100=>0,250=>0,500=>0,1000=>0] as $n=>$_){
                if($u['games_won']>=$n) $add('win_'.$n);
            }
            if($diff==='Easy'&&$u['diff_stats']['Easy']['w']>=10) $add('easy_win_10');
            if($diff==='Medium'&&$u['diff_stats']['Medium']['w']>=10) $add('medium_win_10');
            if($diff==='Hard'){
                foreach([10,25,50] as $n) if($u['diff_stats']['Hard']['w']>=$n) $add('hard_win_'.$n);
            }
            if($u['games_played']>=20&&$u['games_played']>0&&($u['games_won']/$u['games_played'])>=0.8) $add('winrate_80');
        }

        // ── Streaks ──
        if($won){
            foreach([3,5,10,20,50] as $n) if($u['streak']>=$n) $add('streak_'.$n);
            if($isMulti) foreach([3,5,10] as $n) if(($u['multi_streak']??0)>=$n) $add('multi_streak_'.$n);
        }
        if(!$won&&($u['consecutive_losses']??0)>=5){
            // comeback tracked elsewhere; set flag
            $u['had_long_lose_streak']=true;
        }
        if($won&&($u['had_long_lose_streak']??false)){
            $add('comeback'); $add('comeback_200'); $u['had_long_lose_streak']=false;
        }

        // ── Daily streaks ──
        if($isDaily&&$won){
            foreach([3,7,14,30] as $n) if(($u['daily_streak']??0)>=$n) $add('daily_streak_'.$n);
            foreach([10,25,50,100] as $n) if(($u['daily_total']??0)>=$n) $add('daily_'.$n);
        }

        // ── Speed ──
        if($won){
            if($time<10) { $add('speed_10s'); $u['fast_wins_10s']=($u['fast_wins_10s']??0)+1; }
            if($time<5) $add('speed_5s');
            if($time<3) $add('speed_3s');
            if($diff==='Hard'&&$time<30) $add('speed_hard_30s');
            if($diff==='Hard'&&$time<15) $add('speed_hard_15s');
            if($diff==='Easy'&&$time<2) $add('speed_easy_2s');
            if($time>180) $add('no_rush');
            if($time>300) $add('slow_burn');
            if(($u['fast_wins_10s']??0)>=10) $add('speed_10_games');
        }

        // ── Precision ──
        if($won){
            if($att===1){
                $u['one_shot_count']=($u['one_shot_count']??0)+1;
                $add('one_shot');
                if($diff==='Medium') $add('one_shot_medium');
                if($diff==='Hard') $add('one_shot_hard');
                foreach([3,10] as $n) if(($u['one_shot_count']??0)>=$n) $add('one_shot_'.$n);
                if($hints===0&&!$puUsed) $add('perfect_game');
            }
            if($att===2&&$diff==='Hard') $add('two_shot_hard');
            if($diff==='Hard'&&$att<=4) $add('binary_search');
            if($diff==='Medium'&&$att===3) $add('half_range_win');
            if($att<=(int)ceil($cfg['attempts']/2)){
                $u['efficiency_count']=($u['efficiency_count']??0)+1;
                $add('efficiency');
                if(($u['efficiency_count']??0)>=10) $add('efficiency_10');
            }
            if($att===$maxAtt){
                $u['clutch_count']=($u['clutch_count']??0)+1;
                $add('max_attempt_win');
                if(($u['clutch_count']??0)>=3) $add('max_attempt_3');
                if(($u['clutch_count']??0)>=10) $add('precision_10');
                if($isDaily) $add('daily_clutch');
                if($isMulti) $add('comeback_multi');
            }
            if($hints===0) {
                $u['no_hint_wins']=($u['no_hint_wins']??0)+1;
                $add('no_hint_win');
                foreach([10,50] as $n) if(($u['no_hint_wins']??0)>=$n) $add('no_hint_'.$n);
                if($diff==='Hard'&&!$puUsed) $add('no_hint_hard');
                if($isDaily) $add('daily_no_hint');
            }
            if(!$puUsed){
                $u['no_powerup_wins']=($u['no_powerup_wins']??0)+1;
                $add('no_powerup_win');
                if($isDaily) $add('daily_no_power');
            }
            // check secret value
            if($secret===7) $add('lucky_number');
            if($secret===42) $add('guess_42');
            if($secret===69) $add('guess_69');
            if($secret===100) $add('guess_100');
            // palindrome
            $ss=(string)$secret;
            if(strlen($ss)>=2&&$ss===strrev($ss)) $add('palindrome_win');
            // prime
            if($secret>1){
                $ip=true;
                for($i=2;$i<=sqrt($secret);$i++)if($secret%$i===0){$ip=false;break;}
                if($ip) $add('prime_win');
            }
            // edge of range
            if($secret===$context['min_range']||$secret===$context['max_range']) $add('win_by_1');
            if($secret===1) $add('min_1_win');
            if($secret===1000) $add('max_1000');
        }

        // ── Levels ──
        foreach([5,10,25,50,75,100,200,500,1000] as $lv) if($u['level']>=$lv) $add('level_'.$lv);
        foreach([1000,10000,100000] as $xpv) if($u['experience']>=$xpv) $add('xp_'.$xpv);

        // ── Coins ──
        foreach([100,500,1000,5000,10000] as $cv) if(($u['coins']??0)>=$cv) $add('coins_'.$cv);

        // ── Powerup mastery ──
        if($puUsed) $add('power_player');
        foreach([10,50,100] as $n) if(($u['powerups_used']??0)>=$n) $add('powerup_'.$n);
        if(count($u['powerup_type_used']??[])>=count($this->shopItems)) $add('all_powerups');

        // ── Play count ──
        foreach([10,25,50,100,250,500,1000] as $n) if($u['games_played']>=$n) $add('play_'.$n);
        if($u['games_played']>=1000) $add('legendary_grind');

        // ── Multiplayer ──
        if($isMulti){
            foreach([25,100] as $n) if(($u['multi_played']??0)>=$n) $add('play_multi_'.$n);
            foreach([25,100] as $n) if(($u['multi_wins']??0)>=$n) $add('multi_win_'.$n);
        }
        foreach([10,100] as $n) if(($u['chat_messages']??0)>=$n) $add('chat_'.$n);
        foreach([5,20] as $n) if(($u['rooms_hosted']??0)>=$n) $add('host_'.$n);
        if(($u['rooms_joined']??0)>=10) $add('join_10');

        // ── Daily special ──
        if($isDaily&&$won){
            $today=date('Y-m-d');
            $aprilDone=$u['april_completed']??[];
            $isApril=str_starts_with($today,'2026-04-');
            if($isApril&&!in_array($today,$aprilDone)){
                $aprilDone[]=$today;
                $u['april_completed']=$aprilDone;
            }
            if(count(array_unique($aprilDone))>=30) $add('daily_all_april');
            if($today==='2026-04-01') $add('april_fools');
            if($today==='2026-04-14') $add('april_pi');
            if($today==='2026-04-18') $add('april_777');
            if($today==='2026-04-30') $add('april_last_day');
            if($won&&$att===1) $add('daily_first_try');
            if($won&&$time<15) $add('daily_fast');
            if($dayOfWeek===1) $add('daily_monday');
            if($dayOfWeek>=6) $add('daily_weekend');
        }

        // ── Time of day ──
        if($hour>=0&&$hour<3) $add('midnight_game');
        if($hour<7) $add('morning_game');

        // ── Theme ──
        foreach(['ocean'=>5,'forest'=>10,'volcano'=>20,'galaxy'=>35,'gold'=>50] as $th=>$lv)
            if($u['level']>=$lv) $add('unlock_'.$th);
        if(($u['theme_changes']??0)>=3) $add('theme_changer');
        if($u['level']>=50) $add('all_themes');
        if(!($u['dark_mode']??true)) $add('play_light_mode');

        // ── Misc ──
        if(($u['hints_used']??0)>=100) $add('hint_100');
        if(($u['hint_xp']??0)>=1000) $add('hint_xp_1000');
        if($u['login_count']>=100) $add('session_100');
        $month=(int)date('n');
        if($month>=3&&$month<=5) $add('seasonal_spring');
        if($u['games_played']>=20&&($u['games_lost']??0)>=10) $add('lose_10');
        if(($u['games_lost']??0)>=50) $add('lose_50');

        return $new;
    }

    // ── UPDATE USER STATS ─────────────────────────────────────────────────
    public function updateUserStats($username,$won,$difficulty,$attUsed,$timeSecs,$hintsUsed,$usedPowerup,$isMulti=false,$isDaily=false,$extra=[]){
        $users=$this->loadUsers();
        if(!isset($users[$username]))return[];
        $u=&$users[$username];

        $u['games_played']++;
        $u['diff_stats'][$difficulty]['p']++;
        if(date('Y-m-d')!==$u['last_session_date']??''){
            $u['last_session_date']=date('Y-m-d');
            $u['session_wins_today']=0;
        }

        $xp=10; $coins=5;
        if($won){
            $u['games_won']++; $u['streak']++; $u['consecutive_losses']=0;
            $u['diff_stats'][$difficulty]['w']++;
            if($isMulti){ $u['multi_wins']=($u['multi_wins']??0)+1; $u['multi_streak']=($u['multi_streak']??0)+1; }
            if($u['streak']>$u['best_streak'])$u['best_streak']=$u['streak'];
            $u['session_wins_today']=($u['session_wins_today']??0)+1;

            $cfg=$this->games[$difficulty];
            $streakBonus=min(3.0,1.0+($u['streak']-1)*0.2);
            $attemptBonus=max(0.5,1.0-(($attUsed-1)/$cfg['attempts'])*0.5);
            $xpSurge=($u['xp_surge_active']??false)?1.5:1.0;
            $xp=(int)round(max(50,$cfg['xp_base']*$cfg['xp_mult']*$streakBonus*$attemptBonus*$xpSurge));
            $coins=(int)round($cfg['coins_base']*$streakBonus*(($u['coin_doubler_active']??false)?2:1));
            $u['coin_doubler_active']=false; $u['xp_surge_active']=false;

            $u['hints_used']+=($hintsUsed>0?$hintsUsed:0);
        } else {
            $u['games_lost']=($u['games_lost']??0)+1;
            $prevStreak=$u['streak'];
            // check shield
            if(!($u['shield_active']??false)){
                $u['streak']=0;
                if($isMulti) $u['multi_streak']=0;
            } else {
                $u['shield_active']=false;
            }
            $u['consecutive_losses']=($u['consecutive_losses']??0)+1;
            if(($u['consecutive_losses']??0)>($u['max_losing_streak']??0))
                $u['max_losing_streak']=$u['consecutive_losses'];
        }
        $u['experience']+=$xp; $u['coins']=($u['coins']??0)+$coins;
        $u['level']=min(1000,(int)floor($u['experience']/50)+1);
        if($usedPowerup){
            $u['powerups_used']=($u['powerups_used']??0)+1;
            $pt=$extra['powerup_type']??'';
            if($pt&&!in_array($pt,$u['powerup_type_used']??[]))
                $u['powerup_type_used'][]=$pt;
        }
        if($isDaily&&$won){
            $u['daily_total']=($u['daily_total']??0)+1;
            $u['daily_streak']=($u['daily_streak']??0)+1;
            $u['last_daily']=date('Y-m-d');
        }

        // Run achievement engine
        $cfg2=$this->games[$difficulty]??['attempts'=>5,'min'=>1,'max'=>10];
        $context=[
            'won'=>$won,'difficulty'=>$difficulty,'attempts_used'=>$attUsed,
            'time_secs'=>$timeSecs,'hints_used'=>$hintsUsed,'powerup_used'=>$usedPowerup,
            'is_multi'=>$isMulti,'is_daily'=>$isDaily,'secret'=>$extra['secret']??0,
            'max_attempts'=>$_SESSION['attempts']??$cfg2['attempts'],
            'min_range'=>$cfg2['min']??1,'max_range'=>$cfg2['max']??10,
            'date'=>date('Y-m-d'),
        ];
        $newAch=$this->checkAchievements($u,$context);

        // XP for achievements
        $bonusXp=count($newAch)*100;
        if($bonusXp>0){ $u['experience']+=$bonusXp; $xp+=$bonusXp; $u['level']=min(1000,(int)floor($u['experience']/50)+1); }

        // Power reward on hard win
        if($won&&$difficulty==='Hard'){
            $u['powerups']['range_narrow']=min(5,($u['powerups']['range_narrow']??0)+1);
            $u['powerups']['reveal_digit']=min(3,($u['powerups']['reveal_digit']??0)+1);
        }
        if($won) $u['powerups']['range_narrow']=min(5,($u['powerups']['range_narrow']??0)+($u['streak']>=5?1:0));

        $u['game_history'][]=['diff'=>$difficulty,'won'=>$won,'attempts'=>$attUsed,'time'=>$timeSecs,'date'=>time(),'multi'=>$isMulti,'daily'=>$isDaily];
        if(count($u['game_history'])>100)array_shift($u['game_history']);

        $this->saveUsers($users);
        return['xp'=>$xp,'coins'=>$coins,'new_achievements'=>$newAch];
    }

    public function getRankings(){
        $users=$this->loadUsers(); $list=[];
        foreach($users as $uname=>$data) $list[]=array_merge($data,['username'=>$uname]);
        usort($list,fn($a,$b)=>$b['level']<=>$a['level']?:$b['experience']<=>$a['experience']);
        return $list;
    }
    public function useHint($username){
        $users=$this->loadUsers();
        if(!isset($users[$username])||($users[$username]['hint_xp']??0)<50)return false;
        $users[$username]['hint_xp']-=50;$this->saveUsers($users);return true;
    }
    public function usePowerup($username,$type){
        $users=$this->loadUsers();
        if(!isset($users[$username])||($users[$username]['powerups'][$type]??0)<=0)return false;
        $users[$username]['powerups'][$type]--;$this->saveUsers($users);return true;
    }
    public function getWarmthHint($guess,$secret,$range){
        $diff=abs($guess-$secret);$pct=$range>0?$diff/$range:0;
        if($pct===0)   return['msg'=>'🎯 Exact!',         'cls'=>'exact'];
        if($pct<0.02)  return['msg'=>'🔥🔥🔥 ON FIRE!',   'cls'=>'blazing'];
        if($pct<0.05)  return['msg'=>'🔥🔥 Scorching!',   'cls'=>'hot'];
        if($pct<0.10)  return['msg'=>'🌡️ Very Warm',      'cls'=>'warm'];
        if($pct<0.20)  return['msg'=>'☀️ Getting Warmer', 'cls'=>'lukewarm'];
        if($pct<0.35)  return['msg'=>'🌤️ Tepid',          'cls'=>'tepid'];
        if($pct<0.55)  return['msg'=>'❄️ Cold',           'cls'=>'cold'];
        return              ['msg'=>'🧊 Freezing!',        'cls'=>'freezing'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// THEME VARS
// ═══════════════════════════════════════════════════════════════════════════════
function themeVars($theme,$dark){
    $themes=[
        'default'=>['dark'=>'--bg:#0a0a14;--panel:#13131f;--acc1:#6d5cf6;--acc2:#a78bfa;--txt:#e8e8f0;--sub:#7b7b9a;--brd:#1e1e35;--card:#1a1a2e'],
        'ocean'  =>['light'=>'--bg:#e8f4f8;--panel:#fff;--acc1:#0077b6;--acc2:#00b4d8;--txt:#03045e;--sub:#023e8a;--brd:#caf0f8;--card:#f0f8ff',
                    'dark' =>'--bg:#030a1a;--panel:#071428;--acc1:#00b4d8;--acc2:#90e0ef;--txt:#caf0f8;--sub:#90e0ef;--brd:#0d2545;--card:#0a1e3d'],
        'forest' =>['light'=>'--bg:#f0fdf4;--panel:#fff;--acc1:#16a34a;--acc2:#4ade80;--txt:#14532d;--sub:#166534;--brd:#bbf7d0;--card:#f0fdf4',
                    'dark' =>'--bg:#030f07;--panel:#091a0e;--acc1:#4ade80;--acc2:#86efac;--txt:#dcfce7;--sub:#bbf7d0;--brd:#0f2d16;--card:#112418'],
        'volcano'=>['light'=>'--bg:#fff7ed;--panel:#fff;--acc1:#ea580c;--acc2:#fb923c;--txt:#431407;--sub:#7c2d12;--brd:#fed7aa;--card:#fff7ed',
                    'dark' =>'--bg:#0f0500;--panel:#1a0a00;--acc1:#fb923c;--acc2:#fdba74;--txt:#ffedd5;--sub:#fed7aa;--brd:#2d1000;--card:#231000'],
        'galaxy' =>['light'=>'--bg:#f5f3ff;--panel:#fff;--acc1:#7c3aed;--acc2:#a78bfa;--txt:#2e1065;--sub:#4c1d95;--brd:#ddd6fe;--card:#f5f3ff',
                    'dark' =>'--bg:#050312;--panel:#0c0824;--acc1:#a78bfa;--acc2:#c4b5fd;--txt:#ede9fe;--sub:#c4b5fd;--brd:#170f40;--card:#130b38'],
        'gold'   =>['light'=>'--bg:#fefce8;--panel:#fff;--acc1:#ca8a04;--acc2:#eab308;--txt:#422006;--sub:#713f12;--brd:#fef08a;--card:#fefce8',
                    'dark' =>'--bg:#0d0900;--panel:#1a1200;--acc1:#eab308;--acc2:#facc15;--txt:#fefce8;--sub:#fef08a;--brd:#2a1e00;--card:#231900'],
    ];
    $t=$themes[$theme]??$themes['default'];
    $mode=$dark?'dark':'light';
    return $t[$mode]??$t['dark'];
}

// ═══════════════════════════════════════════════════════════════════════════════
// PAGE SHELL
// ═══════════════════════════════════════════════════════════════════════════════
function page_start($title='NumGenius',$theme='default',$dark=true){
    $vars=themeVars($theme,$dark);
    $esc=htmlspecialchars($title);
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1,viewport-fit=cover'>
<meta name='theme-color' content='#0a0a14'>
<title>{$esc} — NumGenius</title>
<link rel='preconnect' href='https://fonts.googleapis.com'>
<link href='https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700;800&display=swap' rel='stylesheet'>
<style>
:root{{$vars}}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%;-webkit-text-size-adjust:100%}
body{font-family:'Syne',system-ui,sans-serif;font-size:1rem;line-height:1.6;background:var(--bg);color:var(--txt);
  min-height:100vh;min-height:100dvh;display:grid;place-items:start center;
  padding:clamp(14px,4vw,40px);padding-top:max(clamp(20px,4vw,40px),env(safe-area-inset-top));
  padding-bottom:max(clamp(14px,4vw,40px),env(safe-area-inset-bottom));
  position:relative;overflow-x:hidden;
  background-image:radial-gradient(ellipse 80% 50% at 50% -20%,color-mix(in srgb,var(--acc1) 12%,transparent),transparent),
    radial-gradient(ellipse 60% 40% at 80% 110%,color-mix(in srgb,var(--acc2) 6%,transparent),transparent);}
body::after{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;opacity:.6;
  background-image:url(\"data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E\");}
.card{position:relative;z-index:1;background:var(--panel);border:1px solid var(--brd);border-radius:clamp(16px,2vw,24px);
  padding:clamp(22px,5vw,44px);width:100%;max-width:520px;
  box-shadow:0 0 0 1px color-mix(in srgb,var(--acc1) 8%,transparent),0 16px 48px rgba(0,0,0,.5);
  animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both;margin:0 auto 24px;}
.card-wide{max-width:660px}.card-xl{max-width:820px}
@keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:none}}
h1{font-family:'Space Mono',monospace;font-weight:700;font-size:clamp(1.4rem,5vw,2rem);
  letter-spacing:-.03em;line-height:1.2;text-align:center;margin-bottom:clamp(18px,4vw,28px);
  background:linear-gradient(135deg,var(--acc1),var(--acc2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
h2{font-weight:700;color:var(--acc2);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em;font-size:.75rem}
p{color:var(--sub)}
.form-group{margin-bottom:clamp(12px,2.5vw,18px)}
label{display:block;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--sub);margin-bottom:5px}
input,select,textarea{width:100%;min-height:48px;padding:11px 15px;background:var(--card);border:1.5px solid var(--brd);
  border-radius:10px;color:var(--txt);font-size:1rem;font-family:inherit;outline:none;
  transition:border-color .2s,box-shadow .2s;-webkit-appearance:none;appearance:none;}
textarea{min-height:72px;resize:vertical}
@media(max-width:480px){input,select,textarea{font-size:16px}}
input:focus,select:focus,textarea:focus{border-color:var(--acc1);box-shadow:0 0 0 3px color-mix(in srgb,var(--acc1) 18%,transparent)}
input[type=number]{font-family:'Space Mono',monospace;font-size:1.2rem;text-align:center;font-weight:700}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;min-height:48px;
  padding:12px 20px;border:none;border-radius:10px;cursor:pointer;font-size:.9rem;
  font-family:'Syne',inherit;font-weight:700;letter-spacing:.04em;text-decoration:none;text-align:center;
  transition:transform .15s,box-shadow .15s,background .15s;-webkit-tap-highlight-color:transparent;
  touch-action:manipulation;user-select:none;white-space:nowrap;}
.btn:active{transform:scale(.96)}
.btn-primary{background:linear-gradient(135deg,var(--acc1),var(--acc2));color:#fff;box-shadow:0 4px 20px color-mix(in srgb,var(--acc1) 30%,transparent)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px color-mix(in srgb,var(--acc1) 45%,transparent)}
.btn-ghost{background:var(--card);color:var(--txt);border:1.5px solid var(--brd)}
.btn-ghost:hover{border-color:var(--acc1);color:var(--acc2)}
.btn-sm{min-height:36px;padding:6px 14px;font-size:.78rem;width:auto;border-radius:8px}
.btn-gold{background:linear-gradient(135deg,#ca8a04,#eab308);color:#000;font-weight:800}
.btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
.grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:clamp(8px,2vw,14px)}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:clamp(7px,1.5vw,12px)}
@media(max-width:380px){.grid-2,.grid-3{grid-template-columns:1fr}}
.stat-box{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:clamp(12px,2.5vw,20px) 8px;
  text-align:center;transition:border-color .2s,transform .15s}
.stat-box:hover{border-color:color-mix(in srgb,var(--acc1) 40%,transparent);transform:translateY(-2px)}
.stat-val{font-family:'Space Mono',monospace;font-size:clamp(1.2rem,4vw,1.7rem);font-weight:700;
  background:linear-gradient(135deg,var(--acc1),var(--acc2));-webkit-background-clip:text;
  -webkit-text-fill-color:transparent;background-clip:text;line-height:1.2}
.stat-lbl{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--sub);margin-top:4px}
.flex{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.flex-between{display:flex;align-items:center;justify-content:space-between;gap:8px}
.stacks{display:flex;flex-direction:column;gap:clamp(9px,2vw,12px)}
.divider{height:1px;background:var(--brd);margin:clamp(14px,3vw,20px) 0}
.link{color:var(--acc2);text-decoration:none;font-weight:700}.link:hover{text-decoration:underline}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:.67rem;font-weight:700;border:1px solid;white-space:nowrap}
.badge-acc{background:color-mix(in srgb,var(--acc1) 15%,transparent);border-color:color-mix(in srgb,var(--acc1) 40%,transparent);color:var(--acc2)}
.badge-gold{background:color-mix(in srgb,#eab308 15%,transparent);border-color:color-mix(in srgb,#eab308 40%,transparent);color:#fbbf24}
.badge-green{background:color-mix(in srgb,#22c55e 15%,transparent);border-color:color-mix(in srgb,#22c55e 40%,transparent);color:#86efac}
.badge-cat{padding:2px 8px;font-size:.6rem;border-radius:4px}
.alert{padding:12px 15px;border-radius:10px;font-size:.88rem;margin-top:12px;border-left:3px solid}
.alert-err{background:color-mix(in srgb,#ef4444 12%,transparent);border-color:#ef4444;color:#fca5a5}
.alert-ok{background:color-mix(in srgb,#22c55e 12%,transparent);border-color:#22c55e;color:#86efac}
.progress-wrap{background:var(--brd);border-radius:999px;height:7px;overflow:hidden;margin:6px 0}
.progress-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--acc1),var(--acc2));transition:width .8s cubic-bezier(.22,1,.36,1)}
.warn-box{background:color-mix(in srgb,#f59e0b 10%,transparent);border:1px solid color-mix(in srgb,#f59e0b 40%,transparent);border-radius:10px;padding:13px;color:#fbbf24;font-size:.9rem}
.success-box{background:color-mix(in srgb,#22c55e 10%,transparent);border:1px solid color-mix(in srgb,#22c55e 40%,transparent);border-radius:10px;padding:13px;color:#86efac;font-size:.9rem}
.info-box{background:color-mix(in srgb,var(--acc1) 8%,transparent);border:1px solid color-mix(in srgb,var(--acc1) 30%,transparent);border-radius:10px;padding:13px;color:var(--acc2);font-size:.9rem}
.text-sm{font-size:.875rem}.text-xs{font-size:.73rem}.text-muted{color:var(--sub)}
.text-acc{color:var(--acc2)}.text-center{text-align:center}
.text-green{color:#4ade80}.text-red{color:#f87171}.text-gold{color:#fbbf24}
.mono{font-family:'Space Mono',monospace}
.mt-1{margin-top:8px}.mt-2{margin-top:14px}.mt-3{margin-top:22px}
.mb-1{margin-bottom:8px}.mb-2{margin-bottom:14px}.mb-3{margin-bottom:22px}
.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Multiplayer */
.player-row{display:flex;align-items:center;gap:12px;background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:12px 15px}
.player-row.winner{border-color:#fbbf24;background:color-mix(in srgb,#fbbf24 8%,transparent)}
.player-row.you{border-color:var(--acc1)}
.chat-wrap{background:var(--card);border:1px solid var(--brd);border-radius:12px;overflow:hidden}
.chat-msgs{height:130px;overflow-y:auto;padding:10px 13px;display:flex;flex-direction:column;gap:5px}
.chat-msg{font-size:.8rem;line-height:1.4}
.chat-msg .chat-name{font-weight:700;color:var(--acc2);margin-right:5px}
.chat-input-row{display:flex;gap:8px;padding:8px;border-top:1px solid var(--brd)}
.chat-input-row input{min-height:36px;border-radius:8px;font-size:.85rem;flex:1}
.chat-input-row button{min-height:36px;width:auto;padding:6px 14px;font-size:.82rem}
/* Shop */
.shop-item{background:var(--card);border:1.5px solid var(--brd);border-radius:13px;padding:14px 16px;transition:border-color .2s,transform .15s}
.shop-item:hover{border-color:color-mix(in srgb,var(--acc1) 40%,transparent);transform:translateY(-2px)}
.shop-item .s-icon{font-size:1.8rem;margin-bottom:4px}
.shop-item .s-name{font-weight:700;font-size:.88rem;color:var(--txt)}
.shop-item .s-desc{font-size:.7rem;color:var(--sub);margin:3px 0 8px}
.shop-item .s-cost{font-family:'Space Mono',monospace;font-weight:700;color:#fbbf24;font-size:.82rem}
/* Room code */
.room-code{font-family:'Space Mono',monospace;font-size:2.2rem;font-weight:700;letter-spacing:.25em;text-align:center;
  padding:16px;background:var(--card);border:2px dashed var(--brd);border-radius:14px;color:var(--acc2);margin:12px 0}
/* Achievement grid */
.ach-item{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:10px 13px;transition:border-color .2s}
.ach-item.earned{border-color:color-mix(in srgb,var(--acc1) 35%,transparent);background:color-mix(in srgb,var(--acc1) 5%,transparent)}
.ach-item.locked{opacity:.35}
.ach-icon{font-size:1.5rem;flex-shrink:0;width:32px;text-align:center}
.ach-info{flex:1;min-width:0}
.ach-name{font-weight:700;font-size:.85rem;color:var(--txt);line-height:1.2}
.ach-desc{font-size:.68rem;color:var(--sub);line-height:1.3}
/* April calendar */
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px}
.cal-day{background:var(--card);border:1px solid var(--brd);border-radius:8px;padding:6px 4px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;min-height:56px;display:flex;flex-direction:column;justify-content:space-between}
.cal-day.today{border-color:var(--acc1);background:color-mix(in srgb,var(--acc1) 8%,transparent)}
.cal-day.done{border-color:#22c55e;background:color-mix(in srgb,#22c55e 8%,transparent)}
.cal-day.past{opacity:.5}
.cal-day .cd-num{font-family:'Space Mono',monospace;font-weight:700;font-size:.8rem;color:var(--acc2)}
.cal-day .cd-icon{font-size:.9rem}
/* Responsive */
@media(max-height:480px) and (orientation:landscape){body{padding-top:10px;padding-bottom:10px}.card{padding:14px 22px}h1{margin-bottom:10px}.stacks{gap:7px}}
@media(min-width:900px){body{padding:48px}.card{padding:48px}}
/* Animations */
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.pulse{animation:pulse 1.5s ease-in-out infinite}
@keyframes popIn{from{transform:scale(0) rotate(-10deg)}to{transform:scale(1) rotate(0)}}
.pop-in{animation:popIn .5s cubic-bezier(.22,1,.36,1) both}
@keyframes shimmer{0%{background-position:200% center}100%{background-position:-200% center}}
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:var(--card)}::-webkit-scrollbar-thumb{background:var(--brd);border-radius:2px}
</style></head><body>
";
}
function page_end(){ echo '</body></html>'; }

// ═══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════════
$game     = new NumberGuessingGame();
$action   = $_GET['action'] ?? 'login';
$username = $_SESSION['username'] ?? null;

function requireAuth(){ global $username; if(!$username){ header('Location: ?action=login'); exit; } }
function userTheme(){
    global $game,$username;
    if(!$username)return['default',true];
    $u=$game->getUser($username);
    return[$u['theme']??'default',(bool)($u['dark_mode']??true)];
}
function levelProgress($xp){
    $level=(int)floor($xp/50)+1;
    $xpCur=($level-1)*50; $xpNext=$level*50;
    $pct=min(100,round(($xp-$xpCur)/max(1,$xpNext-$xpCur)*100));
    return[$level,$pct,$xpNext-$xp];
}

// ═══════════════════════════════════════════════════════════════════════════════
// LOGIN
// ═══════════════════════════════════════════════════════════════════════════════
if($action==='login'){
    $err='';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $u=trim($_POST['username']??''); $p=$_POST['password']??'';
        if($game->loginUser($u,$p)){
            $_SESSION['username']=$u;
            $usr=$game->getUser($u);
            if(!isset($usr['coins']))$game->updateUser($u,['coins'=>50]);
            header('Location: ?action=menu'); exit;
        } else $err='Invalid credentials.';
    }
    page_start('Login');
    ?>
    <div class="card">
      <h1>🎯 NumGenius</h1>
      <p class="text-center mb-2">The competitive number guessing game</p>
      <div class="divider"></div>
      <form method="POST">
        <div class="form-group"><label>Username</label><input type="text" name="username" autofocus required autocomplete="username"></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn-primary" type="submit">Sign In →</button>
        <?php if($err):?><div class="alert alert-err"><?=htmlspecialchars($err)?></div><?php endif;?>
      </form>
      <div class="divider"></div>
      <p class="text-center text-sm">No account? <a class="link" href="?action=register">Register here</a></p>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// REGISTER
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='register'){
    $err='';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $u=trim($_POST['username']??''); $p=$_POST['password']??'';
        if(strlen($u)<3)$err='Username must be at least 3 characters.';
        elseif(strlen($p)<4)$err='Password must be at least 4 characters.';
        elseif($game->registerUser($u,$p)){ $_SESSION['username']=$u; header('Location: ?action=menu'); exit; }
        else $err='Username already taken.';
    }
    page_start('Register');
    ?>
    <div class="card">
      <h1>✨ Join NumGenius</h1>
      <form method="POST">
        <div class="form-group"><label>Username</label><input type="text" name="username" required autocomplete="username"></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required autocomplete="new-password"></div>
        <button class="btn btn-primary" type="submit">Create Account →</button>
        <?php if($err):?><div class="alert alert-err"><?=htmlspecialchars($err)?></div><?php endif;?>
      </form>
      <div class="divider"></div>
      <p class="text-center text-sm">Have an account? <a class="link" href="?action=login">Sign in</a></p>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// TOGGLE DARK
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='toggle_dark'){
    requireAuth();
    $u=$game->getUser($username);
    $game->updateUser($username,['dark_mode'=>!($u['dark_mode']??true)]);
    header('Location: ?action=menu'); exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// MENU
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='menu'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username);
    [$lvl,$pct,$xpLeft]=levelProgress($u['experience']);
    $dailyDone=$u['last_daily']===date('Y-m-d');
    $achs=$game->getAchievements(); $earned=$u['achievements']??[];
    $todayIsApril=str_starts_with(date('Y-m-d'),'2026-04-');
    $cal=$game->getAprilCalendar();
    $todayNote=$cal[date('Y-m-d')]['note']??'';
    page_start('Menu',$th,$dm);
    ?>
    <div class="card card-wide">
      <div class="flex-between mb-2">
        <div class="flex">
          <span class="badge badge-acc mono">⭐ Lv <?=$lvl?></span>
          <span style="font-weight:700"><?=htmlspecialchars($username)?></span>
        </div>
        <div class="flex">
          <a href="?action=toggle_dark" class="btn btn-ghost btn-sm"><?=$dm?'☀️':'🌙'?></a>
          <a href="?action=logout" class="btn btn-ghost btn-sm">Out</a>
        </div>
      </div>
      <div style="margin-bottom:16px">
        <div class="flex-between text-xs text-muted mb-1"><span><?=$u['experience']?> XP</span><span><?=$xpLeft?> to Lv <?=$lvl+1?></span></div>
        <div class="progress-wrap"><div class="progress-bar" style="width:<?=$pct?>%"></div></div>
      </div>
      <div class="info-box flex-between mb-2" style="padding:10px 15px">
        <span class="text-sm"><span style="font-size:1.1rem">🪙</span> <strong><?=$u['coins']??0?></strong> coins</span>
        <div class="flex" style="gap:6px">
          <span class="text-xs text-muted"><?=count($earned)?>/<?=count($achs)?> 🏅</span>
          <a href="?action=shop" class="btn btn-gold btn-sm" style="min-height:30px;font-size:.73rem">🛒 Shop</a>
        </div>
      </div>
      <div class="grid-2 mb-2">
        <div class="stat-box"><div class="stat-val"><?=$u['games_won']?></div><div class="stat-lbl">Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['streak']?><?=$u['streak']>=3?' 🔥':''?></div><div class="stat-lbl">Streak</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']?></div><div class="stat-lbl">Played</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']>0?round($u['games_won']/$u['games_played']*100):0?>%</div><div class="stat-lbl">Win Rate</div></div>
      </div>
      <?php if(!$dailyDone):?>
      <a href="?action=daily" style="display:block;text-decoration:none;margin-bottom:12px">
        <div class="warn-box" style="cursor:pointer">
          📅 <strong>Daily Challenge!</strong><?=$todayIsApril?' 🌸 April Special':''?>
          <?php if($todayNote):?><br><span class="text-xs" style="opacity:.8"><?=htmlspecialchars($todayNote)?></span><?php endif;?>
        </div>
      </a>
      <?php else:?><div class="success-box mb-2">✅ Daily done! Come back tomorrow.</div><?php endif;?>
      <div class="stacks">
        <a href="?action=select" class="btn btn-primary" style="font-size:1rem">▶&nbsp; Play Solo</a>
        <a href="?action=multiplayer" class="btn btn-ghost" style="border-color:color-mix(in srgb,var(--acc2) 40%,transparent);color:var(--acc2)">⚔️&nbsp; Multiplayer</a>
        <div class="grid-2">
          <a href="?action=daily_calendar" class="btn btn-ghost">🌸 April Calendar</a>
          <a href="?action=rankings" class="btn btn-ghost">🏆 Rankings</a>
        </div>
        <div class="grid-2">
          <a href="?action=achievements" class="btn btn-ghost">🎖 Achievements</a>
          <a href="?action=stats" class="btn btn-ghost">📊 Stats</a>
        </div>
        <a href="?action=themes" class="btn btn-ghost">🎨 Themes</a>
      </div>
      <div class="divider"></div>
      <div class="flex text-xs text-muted" style="justify-content:center;gap:10px;flex-wrap:wrap">
        <?php foreach(['range_narrow'=>'🔭','reveal_digit'=>'🔮','extra_attempt'=>'❤️','freeze_timer'=>'⏸️','double_coins'=>'🪙','shadow_guess'=>'👥','range_bomb'=>'💣','hot_cold_oracle'=>'🌡️','mistake_shield'=>'🛡️','xp_surge'=>'📈'] as $k=>$ic):
          $cnt=$u['powerups'][$k]??0; if($cnt>0): ?><span><?=$ic?> ×<?=$cnt?></span><?php endif; endforeach;?>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// APRIL CALENDAR
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='daily_calendar'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username);
    $cal=$game->getAprilCalendar();
    $done=$u['april_completed']??[];
    $today=date('Y-m-d');
    page_start('April Calendar',$th,$dm);
    ?>
    <div class="card card-wide">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🌸 April 2026</h1>
      <p class="text-center mb-3">30 hand-crafted daily challenges — complete them all for the April Champion badge!</p>
      <div class="flex-between mb-3">
        <span class="badge badge-acc">✅ <?=count(array_intersect(array_keys($cal),$done))?>/30 Done</span>
        <?php if(count(array_intersect(array_keys($cal),$done))>=30):?>
        <span class="badge badge-green">🌸 April Champion!</span>
        <?php endif;?>
      </div>
      <!-- Calendar grid header -->
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px">
        <?php foreach(['M','T','W','T','F','S','S'] as $d):?>
        <div style="text-align:center;font-size:.62rem;font-weight:700;color:var(--sub);padding:4px 0"><?=$d?></div>
        <?php endforeach;?>
      </div>
      <!-- April 1 is Wednesday (offset 2 Mon=0) -->
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px">
        <?php
        // April 1 2026 is a Wednesday (index 2 in Mon-Sun week)
        for($i=0;$i<2;$i++): ?>
        <div></div>
        <?php endfor;
        for($day=1;$day<=30;$day++):
          $dateKey=sprintf('2026-04-%02d',$day);
          $isDone=in_array($dateKey,$done);
          $isToday=$dateKey===$today;
          $isPast=$dateKey<$today;
          $isFuture=$dateKey>$today;
          $entry=$cal[$dateKey]??null;
          $icons=['2026-04-01'=>'🤡','2026-04-07'=>'7️⃣','2026-04-10'=>'1️⃣','2026-04-12'=>'😏',
                  '2026-04-14'=>'π','2026-04-18'=>'🎰','2026-04-19'=>'⚖️','2026-04-22'=>'🌍',
                  '2026-04-30'=>'🌸'];
          $icon=$icons[$dateKey]??($isDone?'✅':($isFuture?'🔒':'📅'));
        ?>
        <div class="cal-day <?=$isToday?'today':($isDone?'done':($isPast?'past':''))?>">
          <div class="cd-num"><?=$day?></div>
          <div class="cd-icon"><?=$icon?></div>
          <?php if($isToday&&!$isDone):?>
          <a href="?action=daily" style="font-size:.55rem;font-weight:700;color:var(--acc2);text-decoration:none;line-height:1">PLAY</a>
          <?php endif;?>
        </div>
        <?php endfor;?>
      </div>
      <!-- Challenge list -->
      <div class="divider"></div>
      <h2>Challenge Details</h2>
      <div class="stacks" style="max-height:400px;overflow-y:auto">
        <?php foreach($cal as $dk=>$c):
          $day=(int)substr($dk,8);
          $isDone=in_array($dk,$done);
          $isToday=$dk===date('Y-m-d');
          $isFuture=$dk>date('Y-m-d');
        ?>
        <div style="display:flex;align-items:center;gap:12px;background:var(--card);border:1px solid <?=$isDone?'#22c55e':($isToday?'var(--acc1)':'var(--brd)')?>;border-radius:10px;padding:10px 14px;opacity:<?=$isFuture?'.55':'1'?>">
          <div style="font-family:'Space Mono',monospace;font-weight:700;color:var(--acc2);font-size:1.1rem;width:28px;text-align:center;flex-shrink:0"><?=$day?></div>
          <div style="flex:1;min-width:0">
            <div class="text-xs text-muted">Range <?=$c['min']?>–<?=$c['max']?> · <?=$c['attempts']?> attempts</div>
            <div style="font-size:.8rem;color:var(--txt);font-style:italic"><?=htmlspecialchars($c['note'])?></div>
          </div>
          <div>
            <?php if($isDone):?><span style="color:#4ade80;font-size:1.2rem">✅</span>
            <?php elseif($isToday):?><a href="?action=daily" class="btn btn-primary btn-sm" style="min-height:30px;font-size:.72rem">Play!</a>
            <?php elseif($isFuture):?><span style="color:var(--sub);font-size:.8rem">🔒</span>
            <?php else:?><span style="color:#f87171;font-size:.8rem">Missed</span>
            <?php endif;?>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// SHOP
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='shop'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username);
    $shopItems=$game->getShopItems();
    $msg=''; $msgType='';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $item=$_POST['item']??'';
        $res=$game->buyItem($username,$item);
        if($res['ok']){ $msg='Purchased! ('.$res['coins'].' coins left)'; $msgType='ok'; $u=$game->getUser($username); }
        else{ $msg=$res['msg']; $msgType='err'; }
    }
    page_start('Shop',$th,$dm);
    ?>
    <div class="card card-wide">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🛒 Power-Up Shop</h1>
      <div class="info-box flex-between mb-2" style="padding:10px 15px">
        <span>Balance: <strong class="text-gold mono"><?=$u['coins']??0?> 🪙</strong></span>
        <span class="text-xs text-muted">Bought: <?=$u['shop_purchases']??0?></span>
      </div>
      <?php if($msg):?><div class="alert alert-<?=$msgType==='ok'?'ok':'err'?> mb-2"><?=htmlspecialchars($msg)?></div><?php endif;?>
      <div class="grid-2" style="gap:10px">
        <?php foreach($shopItems as $key=>$it):
          $owned=$u['powerups'][$key]??0;
          $canBuy=($u['coins']??0)>=$it['cost']&&$owned<$it['max'];
          $isNew=in_array($key,['shadow_guess','range_bomb','hot_cold_oracle','mistake_shield','xp_surge']);
        ?>
        <div class="shop-item" style="<?=$isNew?'border-color:color-mix(in srgb,var(--acc2) 40%,transparent)':''?>">
          <?php if($isNew):?><span style="font-size:.6rem;font-weight:700;color:var(--acc2);text-transform:uppercase;letter-spacing:.1em">✨ New</span><?php endif;?>
          <div class="s-icon"><?=$it['icon']?></div>
          <div class="s-name"><?=$it['name']?></div>
          <div class="s-desc"><?=$it['desc']?></div>
          <div class="flex-between mt-1">
            <span class="s-cost"><?=$it['cost']?> 🪙</span>
            <span class="text-xs text-muted"><?=$owned?>/<?=$it['max']?></span>
          </div>
          <form method="POST" style="margin-top:7px">
            <input type="hidden" name="item" value="<?=$key?>">
            <button class="btn <?=$canBuy?'btn-gold':'btn-ghost'?> btn-sm" style="width:100%;<?=!$canBuy?'opacity:.4;cursor:not-allowed':''?>" <?=!$canBuy?'disabled':''?>>
              <?=$owned>=$it['max']?'Max':($canBuy?'Buy':'Need '.$it['cost'].'🪙')?>
            </button>
          </form>
        </div>
        <?php endforeach;?>
      </div>
      <div class="divider"></div>
      <p class="text-xs text-muted text-center">Earn coins by winning · Hard mode & streaks give more · Daily challenges give bonus</p>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// MULTIPLAYER HUB
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='multiplayer'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username);
    $err='';
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['create'])){
        $diff=$_POST['difficulty']??'Medium';
        $maxP=intval($_POST['max_players']??4);
        $code=$game->createRoom($username,$diff,$maxP);
        header("Location: ?action=room&code=$code"); exit;
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['join'])){
        $code=strtoupper(trim($_POST['code']??''));
        $res=$game->joinRoom($code,$username);
        if($res['ok'])  { header("Location: ?action=room&code=$code"); exit; }
        else $err=$res['msg'];
    }
    page_start('Multiplayer',$th,$dm);
    ?>
    <div class="card">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">⚔️ Multiplayer</h1>
      <p class="text-center mb-3">Race friends to guess the same secret number!</p>
      <h2>Create a Room</h2>
      <form method="POST" class="mb-3">
        <div class="form-group">
          <label>Difficulty</label>
          <select name="difficulty">
            <option value="Easy">Easy — 1–10, 5 attempts</option>
            <option value="Medium" selected>Medium — 1–100, 7 attempts</option>
            <option value="Hard">Hard — 1–1000, 10 attempts</option>
          </select>
        </div>
        <div class="form-group">
          <label>Max Players</label>
          <select name="max_players">
            <option value="2">2 Players</option>
            <option value="3">3 Players</option>
            <option value="4" selected>4 Players</option>
          </select>
        </div>
        <button class="btn btn-primary" type="submit" name="create">Create Room →</button>
      </form>
      <div class="divider"></div>
      <h2>Join a Room</h2>
      <?php if($err):?><div class="alert alert-err mb-2"><?=htmlspecialchars($err)?></div><?php endif;?>
      <form method="POST" class="flex" style="gap:10px">
        <input type="text" name="code" placeholder="6-char code" maxlength="6" style="text-transform:uppercase;font-family:'Space Mono',monospace;letter-spacing:.15em;font-size:1.1rem;text-align:center">
        <button class="btn btn-ghost" type="submit" name="join" style="width:auto;white-space:nowrap">Join →</button>
      </form>
      <div class="divider"></div>
      <div class="grid-2 text-xs text-muted" style="gap:8px">
        <div class="stat-box"><div class="stat-val"><?=$u['multi_wins']??0?></div><div class="stat-lbl">MP Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['multi_streak']??0?></div><div class="stat-lbl">MP Streak</div></div>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// ROOM
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='room'){
    requireAuth();
    [$th,$dm]=userTheme();
    $code=strtoupper($_GET['code']??'');
    $room=$game->getRoom($code);
    if(!$room){ header('Location: ?action=multiplayer'); exit; }
    if(!isset($room['players'][$username])){
        $res=$game->joinRoom($code,$username);
        if(!$res['ok']){ header('Location: ?action=multiplayer'); exit; }
        $room=$game->getRoom($code);
    }
    $isHost=$room['host']===$username;
    $status=$room['status'];
    $myData=$room['players'][$username];
    $u=$game->getUser($username);

    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['start_game'])){
        $game->startRoom($code,$username);
        header("Location: ?action=room&code=$code"); exit;
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['chat_msg'])){
        $msg=trim($_POST['chat_msg']??'');
        if($msg) $game->addRoomChat($code,$username,$msg);
        header("Location: ?action=room&code=$code"); exit;
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['guess'])){
        $guess=intval($_POST['guess']);
        $res=$game->roomGuess($code,$username,$guess);
        if($res['result']==='win') header("Location: ?action=room&code=$code&flash=win");
        elseif($res['result']==='lose') header("Location: ?action=room&code=$code&flash=lose");
        else header("Location: ?action=room&code=$code");
        exit;
    }
    if(isset($_GET['leave'])){ header('Location: ?action=multiplayer'); exit; }

    if($status==='finished'&&!isset($_SESSION['multi_scored_'.$code])){
        $game->updateUserStats($username,$myData['won'],$room['difficulty'],$myData['attempts'],$myData['finish_time']??0,0,false,true,false,['secret'=>$room['secret']]);
        $_SESSION['multi_scored_'.$code]=true;
    }

    $room=$game->getRoom($code);
    $myData=$room['players'][$username];
    $flash=$_GET['flash']??'';

    page_start('Room '.$code,$th,$dm);
    ?>
    <div class="card card-wide">
      <div class="flex-between mb-2">
        <div class="flex">
          <span class="badge badge-acc mono"><?=$room['difficulty']?></span>
          <span class="badge" style="background:color-mix(in srgb,<?=$status==='active'?'#22c55e':($status==='finished'?'#ef4444':'#f59e0b')?> 15%,transparent);border-color:color-mix(in srgb,<?=$status==='active'?'#22c55e':($status==='finished'?'#ef4444':'#f59e0b')?> 40%,transparent);color:<?=$status==='active'?'#4ade80':($status==='finished'?'#f87171':'#fbbf24')?>"><?=ucfirst($status)?></span>
        </div>
        <a href="?action=room&code=<?=$code?>&leave=1" class="btn btn-ghost btn-sm">Leave</a>
        <?php if($status!=='finished'):?><meta http-equiv="refresh" content="4"><?php endif;?>
      </div>
      <?php if($status==='waiting'):?><div class="room-code"><?=$code?></div><p class="text-center text-sm text-muted mb-3">Share this code with friends</p><?php endif;?>
      <?php if($flash==='win'):?><div class="success-box mb-2 pop-in">🎉 You got it! Waiting for others…</div><?php endif;?>
      <?php if($flash==='lose'):?><div class="alert alert-err mb-2">💔 Out of attempts! Watching…</div><?php endif;?>

      <h2>Players (<?=count($room['players'])?>/<?=$room['max_players']?>)</h2>
      <div class="stacks mb-3">
        <?php
        $sorted=$room['players'];
        uasort($sorted,function($a,$b){
            if($a['won']&&!$b['won'])return -1;
            if(!$a['won']&&$b['won'])return 1;
            if($a['won']&&$b['won'])return $a['finish_time']<=>$b['finish_time'];
            return $b['attempts']<=>$a['attempts'];
        });
        $medals=['🥇','🥈','🥉','4️⃣']; $rank=0;
        foreach($sorted as $pname=>$pd):
          $isMe=$pname===$username;
          $isDone=$pd['won']||$pd['attempts']>=$room['max_attempts'];
          $medal=$pd['won']?($medals[$rank]??''):($isDone?'❌':'');
          if($pd['won'])$rank++;
        ?>
        <div class="player-row <?=$pd['won']?'winner':''?> <?=$isMe?'you':''?>">
          <div style="font-size:1.3rem;width:28px;text-align:center"><?=$status==='waiting'?($pname===$room['host']?'👑':'👤'):$medal?></div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;color:<?=$isMe?'var(--acc2)':'var(--txt)'?>"><?=htmlspecialchars($pname)?><?=$isMe?' (you)':''?><?=$pname===$room['host']?' 👑':''?></div>
            <?php if($status!=='waiting'):?>
            <div class="text-xs text-muted"><?=$pd['attempts']?> attempt<?=$pd['attempts']!==1?'s':''?><?=$pd['won']?' · ⏱ '.$pd['finish_time'].'s':''?></div>
            <?php endif;?>
          </div>
          <?php if($status!=='waiting'&&!$isDone&&!$isMe):?><span class="badge badge-acc pulse">Playing</span><?php endif;?>
        </div>
        <?php endforeach;?>
      </div>

      <?php if($status==='waiting'&&$isHost):?>
      <form method="POST" class="mb-3">
        <button class="btn btn-primary" type="submit" name="start_game" <?=count($room['players'])<2?'disabled':''?>>
          <?=count($room['players'])<2?'Waiting for players…':'▶ Start Game!'?>
        </button>
      </form>
      <?php elseif($status==='waiting'&&!$isHost):?>
      <div class="warn-box mb-3 pulse">⏳ Waiting for host to start…</div>
      <?php endif;?>

      <?php if($status==='active'&&!$myData['won']&&$myData['attempts']<$room['max_attempts']):
        $attLeft=$room['max_attempts']-$myData['attempts'];
        $lastGuess=$myData['guesses']?end($myData['guesses']):null;
        $dir=''; if($lastGuess!==null) $dir=$lastGuess<$room['secret']?'↑ Higher':'↓ Lower';
      ?>
      <div class="info-box mb-3">
        <div class="flex-between">
          <span>Left: <strong class="mono"><?=$attLeft?>/<?=$room['max_attempts']?></strong></span>
          <span style="font-size:.8rem">Range: <strong class="mono"><?=$room['min']?>–<?=$room['max']?></strong></span>
        </div>
        <?php if($dir):?><div class="text-sm mt-1 text-acc" style="font-weight:700"><?=$dir?></div><?php endif;?>
        <?php if($myData['guesses']):?>
        <div class="flex mt-1" style="gap:6px;flex-wrap:wrap">
          <?php foreach($myData['guesses'] as $g):?><span class="mono badge badge-acc"><?=$g?></span><?php endforeach;?>
        </div>
        <?php endif;?>
      </div>
      <form method="POST" class="flex-between" style="gap:10px">
        <input type="number" name="guess" min="<?=$room['min']?>" max="<?=$room['max']?>" required autofocus placeholder="<?=$room['min']?>–<?=$room['max']?>" style="flex:1">
        <button class="btn btn-primary" style="width:auto;white-space:nowrap" type="submit">Guess!</button>
      </form>
      <?php elseif($status==='active'):?>
      <div class="<?=$myData['won']?'success-box':'warn-box'?> mb-3">
        <?=$myData['won']?'🎉 You got it! Waiting for others…':'⏳ Out of attempts. Watching…'?>
      </div>
      <?php endif;?>
      <?php if($status==='finished'):?>
      <div class="divider"></div>
      <p class="text-center text-sm text-muted mb-2">Secret was <strong class="text-acc mono" style="font-size:1.2rem"><?=$room['secret']?></strong></p>
      <div class="grid-2">
        <a href="?action=multiplayer" class="btn btn-primary">New Game</a>
        <a href="?action=menu" class="btn btn-ghost">Menu</a>
      </div>
      <?php endif;?>
      <div class="divider"></div>
      <h2>Chat</h2>
      <div class="chat-wrap">
        <div class="chat-msgs" id="chatBox">
          <?php $chat=array_slice($room['chat']??[],-20); foreach($chat as $cm):?>
          <div class="chat-msg"><span class="chat-name"><?=htmlspecialchars($cm['u'])?>:</span><?=htmlspecialchars($cm['m'])?></div>
          <?php endforeach;?>
          <?php if(empty($chat)):?><div class="text-xs text-muted">No messages yet…</div><?php endif;?>
        </div>
        <div class="chat-input-row">
          <form method="POST" class="flex" style="flex:1;gap:8px;margin:0">
            <input type="text" name="chat_msg" placeholder="Say something…" maxlength="100">
            <button class="btn btn-ghost btn-sm" type="submit">Send</button>
          </form>
        </div>
      </div>
    </div>
    <script>const cb=document.getElementById('chatBox');if(cb)cb.scrollTop=cb.scrollHeight;</script>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// SELECT DIFFICULTY
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='select'){
    requireAuth();
    [$th,$dm]=userTheme();
    $diffs=$game->getDifficulties(); $u=$game->getUser($username);
    page_start('Select Difficulty',$th,$dm);
    ?>
    <div class="card">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">Choose Difficulty</h1>
      <form method="POST" action="?action=start">
        <div class="stacks">
          <?php foreach($diffs as $name=>$cfg):
            $ds=$u['diff_stats'][$name]??['w'=>0,'p'=>0];
            $wr=$ds['p']>0?round($ds['w']/$ds['p']*100):0;
            $cols=['Easy'=>'#4ade80','Medium'=>'#fbbf24','Hard'=>'#f87171'];
            $col=$cols[$name]??'var(--acc1)';
          ?>
          <label style="cursor:pointer;display:block">
            <input type="radio" name="difficulty" value="<?=$name?>" required style="display:none" onchange="this.form.submit()">
            <div class="stat-box" style="text-align:left;padding:17px 20px;cursor:pointer"
              onmouseover="this.style.borderColor='<?=$col?>';this.style.transform='translateY(-2px)'"
              onmouseout="this.style.borderColor='var(--brd)';this.style.transform='none'">
              <div class="flex-between">
                <strong style="font-size:1.05rem"><?=$name?></strong>
                <div class="flex">
                  <span class="badge badge-acc">×<?=$cfg['xp_mult']?> XP</span>
                  <span class="badge badge-gold"><?=$cfg['coins_base']?>🪙</span>
                  <span style="color:<?=$col?>">●</span>
                </div>
              </div>
              <div class="text-sm text-muted mt-1">Range <?=$cfg['min']?>–<?=$cfg['max']?> · <?=$cfg['attempts']?> attempts</div>
              <div class="text-xs text-muted mt-1">Record: <strong class="text-acc"><?=$ds['w']?>/<?=$ds['p']?> (<?=$wr?>%)</strong></div>
            </div>
          </label>
          <?php endforeach;?>
        </div>
      </form>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// START
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='start'){
    requireAuth();
    $difficulty=$_POST['difficulty']??'';
    $config=$game->getGameConfig($difficulty);
    if(!$config){ header('Location: ?action=select'); exit; }
    $_SESSION['secret']=$game->generateNumber($config['min'],$config['max']);
    $_SESSION['difficulty']=$difficulty;
    $_SESSION['min']=$config['min']; $_SESSION['max_range']=$config['max'];
    $_SESSION['cur_min']=$config['min']; $_SESSION['cur_max']=$config['max'];
    $_SESSION['attempts']=$config['attempts']; $_SESSION['guesses']=[];
    $_SESSION['current_attempt']=0; $_SESSION['hints_used']=0;
    $_SESSION['powerup_used']=false; $_SESSION['powerup_types_used']=[];
    $_SESSION['start_time']=time(); $_SESSION['is_daily']=false;
    $_SESSION['freeze_end']=0; $_SESSION['shadow_active']=false;
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// DAILY
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='daily'){
    requireAuth();
    $u=$game->getUser($username);
    if($u['last_daily']===date('Y-m-d')){ header('Location: ?action=menu'); exit; }
    $daily=$game->getDailyChallenge();
    $_SESSION['secret']=$daily['secret']; $_SESSION['difficulty']='Medium';
    $_SESSION['min']=$daily['min']; $_SESSION['max_range']=$daily['max'];
    $_SESSION['cur_min']=$daily['min']; $_SESSION['cur_max']=$daily['max'];
    $_SESSION['attempts']=$daily['attempts']; $_SESSION['guesses']=[];
    $_SESSION['current_attempt']=0; $_SESSION['hints_used']=0;
    $_SESSION['powerup_used']=false; $_SESSION['powerup_types_used']=[];
    $_SESSION['start_time']=time(); $_SESSION['is_daily']=true;
    $_SESSION['freeze_end']=0; $_SESSION['shadow_active']=false;
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// USE HINT
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='use_hint'){
    requireAuth();
    if($game->useHint($username)){ $_SESSION['hints_used']=($_SESSION['hints_used']??0)+1; $_SESSION['show_hint']=true; }
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// USE POWERUP
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='use_powerup'){
    requireAuth();
    $type=$_GET['type']??''; $secret=$_SESSION['secret']??0;
    $curMin=$_SESSION['cur_min']??1; $curMax=$_SESSION['cur_max']??10;

    if($game->usePowerup($username,$type)){
        $_SESSION['powerup_used']=true;
        if(!in_array($type,$_SESSION['powerup_types_used']??[]))
            $_SESSION['powerup_types_used'][]=$type;
        $u=$game->getUser($username);

        switch($type){
            case 'range_narrow':
                $range=$curMax-$curMin; $q=(int)floor($range/4);
                if($secret-$q>$curMin)$_SESSION['cur_min']=$secret-$q;
                if($secret+$q<$curMax)$_SESSION['cur_max']=$secret+$q;
                $_SESSION['powerup_msg']="🔭 Range narrowed to {$_SESSION['cur_min']}–{$_SESSION['cur_max']}!";
                break;
            case 'reveal_digit':
                $_SESSION['powerup_msg']="🔮 The number ends in: <strong>".($secret%10)."</strong>";
                break;
            case 'extra_attempt':
                $_SESSION['attempts']++;
                $_SESSION['powerup_msg']="❤️ +1 Extra attempt granted!";
                break;
            case 'freeze_timer':
                $_SESSION['freeze_end']=time()+30;
                $_SESSION['powerup_msg']="⏸️ Timer frozen for 30 seconds!";
                break;
            case 'double_coins':
                $game->updateUser($username,['coin_doubler_active'=>true]);
                $_SESSION['powerup_msg']="🪙 Coin Doubler active — next win = 2× coins!";
                break;
            case 'hint_boost':
                $game->updateUser($username,['hint_xp'=>($u['hint_xp']??0)+100]);
                $_SESSION['powerup_msg']="💡 +100 Hint XP added!";
                break;
            case 'shadow_guess':
                $_SESSION['shadow_active']=true;
                $_SESSION['powerup_msg']="👥 Shadow Guess active — next wrong guess is free!";
                break;
            case 'range_bomb':
                // Eliminate a random 25% block that doesn't contain the secret
                $range=$curMax-$curMin; $chunk=(int)floor($range/4);
                // find a quarter that doesn't contain secret
                for($attempt=0;$attempt<10;$attempt++){
                    $bombMin=$curMin+rand(0,3)*$chunk;
                    $bombMax=min($curMax,$bombMin+$chunk);
                    if($secret<$bombMin||$secret>$bombMax){
                        // Collapse range around remaining area
                        if($bombMin<=$curMin)$_SESSION['cur_min']=$bombMax+1;
                        elseif($bombMax>=$curMax)$_SESSION['cur_max']=$bombMin-1;
                        $_SESSION['powerup_msg']="💣 Range Bomb! Eliminated {$bombMin}–{$bombMax}. New range: {$_SESSION['cur_min']}–{$_SESSION['cur_max']}";
                        break;
                    }
                }
                if(!isset($_SESSION['powerup_msg']))$_SESSION['powerup_msg']="💣 Bomb fizzled — secret too central!";
                break;
            case 'hot_cold_oracle':
                $midpoint=($curMin+$curMax)/2;
                $where=$secret<=$midpoint?"bottom half ({$curMin}–".(int)$midpoint.')':'top half ('.(((int)$midpoint)+1)."–{$curMax})";
                $_SESSION['powerup_msg']="🌡️ Oracle: The secret is in the <strong>$where</strong>!";
                break;
            case 'mistake_shield':
                $game->updateUser($username,['shield_active'=>true]);
                $_SESSION['powerup_msg']="🛡️ Mistake Shield active — if you lose, your streak is protected!";
                break;
            case 'xp_surge':
                $game->updateUser($username,['xp_surge_active'=>true]);
                $_SESSION['powerup_msg']="📈 XP Surge active — next win gives 50% bonus XP!";
                break;
        }
    }
    header('Location: ?action=game');
}

// ═══════════════════════════════════════════════════════════════════════════════
// GAME
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='game'){
    requireAuth();
    [$th,$dm]=userTheme();
    $secret=$_SESSION['secret']??0; $attempts=$_SESSION['attempts']??5;
    $current=$_SESSION['current_attempt']??0; $difficulty=$_SESSION['difficulty']??'Medium';
    $minR=$_SESSION['min']??1; $maxR=$_SESSION['max_range']??10;
    $curMin=$_SESSION['cur_min']??$minR; $curMax=$_SESSION['cur_max']??$maxR;
    $guesses=$_SESSION['guesses']??[]; $isDaily=$_SESSION['is_daily']??false;
    $freezeEnd=$_SESSION['freeze_end']??0; $shadowActive=$_SESSION['shadow_active']??false;

    if($_SERVER['REQUEST_METHOD']==='POST'){
        $guess=intval($_POST['guess']??0);
        $isShadowActive=$_SESSION['shadow_active']??false;
        $isCorrect=$guess==$secret;
        // Shadow guess: wrong guess doesn't cost an attempt
        if(!$isCorrect&&$isShadowActive){
            $_SESSION['shadow_active']=false;
            $_SESSION['guesses'][]=$guess;
            // Don't increment attempt
            $_SESSION['powerup_msg']="👥 Shadow Guess absorbed! Free miss on $guess.";
            header('Location: ?action=game'); exit;
        }
        $_SESSION['guesses'][]=$guess; $_SESSION['current_attempt']++;
        $_SESSION['show_hint']=false; unset($_SESSION['powerup_msg']);
        if($isCorrect){ header('Location: ?action=results&result=win'); exit; }
        if($_SESSION['current_attempt']>=$attempts){ header('Location: ?action=results&result=lose'); exit; }
        header('Location: ?action=game'); exit;
    }
    if($current>=$attempts){ header('Location: ?action=results&result=lose'); exit; }

    $u=$game->getUser($username);
    $lastGuess=$guesses?end($guesses):null; $dirHint=''; $warmth=null;
    if($lastGuess!==null){
        $dirHint=$lastGuess<$secret?'Too low — go higher ↑':'Too high — go lower ↓';
        $warmth=$game->getWarmthHint($lastGuess,$secret,$maxR-$minR);
    }
    $showHintResult=$_SESSION['show_hint']??false; $hintResult='';
    if($showHintResult&&$secret){
        $mid=(int)(($curMin+$curMax)/2);
        $hintResult=$secret<=$mid?"Lower half ({$curMin}–{$mid})":"Upper half (".($mid+1)."–{$curMax})";
        $_SESSION['show_hint']=false;
    }
    $powerupMsg=$_SESSION['powerup_msg']??''; unset($_SESSION['powerup_msg']);
    $frozenTime=max(0,$freezeEnd-time());
    $elapsed=max(0,time()-($_SESSION['start_time']??time())-($frozenTime>0?$frozenTime:0));
    $left=$attempts-$current;
    $wc=['exact'=>'#22c55e','blazing'=>'#ef4444','hot'=>'#f97316','warm'=>'#f59e0b','lukewarm'=>'#eab308','tepid'=>'#84cc16','cold'=>'#38bdf8','freezing'=>'#818cf8'];

    page_start('Game',$th,$dm);
    ?>
    <div class="card">
      <div class="flex-between mb-2">
        <div class="flex">
          <span class="badge badge-acc"><?=htmlspecialchars($difficulty)?></span>
          <?php if($isDaily):?><span class="badge badge-gold">📅 Daily</span><?php endif;?>
          <?php if($u['coin_doubler_active']??false):?><span class="badge badge-gold">🪙 2×</span><?php endif;?>
          <?php if($u['xp_surge_active']??false):?><span class="badge badge-acc">📈 XP+</span><?php endif;?>
          <?php if($shadowActive):?><span class="badge badge-green">👥 Shadow</span><?php endif;?>
          <?php if($u['shield_active']??false):?><span class="badge badge-green">🛡️ Shield</span><?php endif;?>
        </div>
        <span class="text-sm text-muted mono">⏱ <span id="ts"><?=$elapsed?></span>s<?=$frozenTime>0?' ⏸️':''?></span>
      </div>
      <div class="flex-between" style="margin-bottom:12px">
        <div><div class="text-xs text-muted">Attempts Left</div><div class="mono" style="font-size:1.8rem;font-weight:700;color:<?=$left<=2?'#f87171':'var(--acc2)'?>;line-height:1"><?=$left?>/<?=$attempts?></div></div>
        <div style="text-align:right"><div class="text-xs text-muted">Range</div><div class="mono" style="font-size:1.05rem;font-weight:700;color:var(--acc1)"><?=$curMin?> – <?=$curMax?></div></div>
      </div>
      <div class="progress-wrap mb-2">
        <div class="progress-bar" style="width:<?=round(($current/$attempts)*100)?>%;background:linear-gradient(90deg,<?=$left<=2?'#ef4444':'var(--acc1)'?>,<?=$left<=2?'#dc2626':'var(--acc2)'?>)"></div>
      </div>

      <?php if($warmth): $c=$wc[$warmth['cls']]; ?>
      <div style="background:color-mix(in srgb,<?=$c?> 12%,transparent);border:1px solid color-mix(in srgb,<?=$c?> 35%,transparent);border-radius:10px;padding:13px;margin-bottom:12px;text-align:center">
        <div style="font-size:1.05rem;font-weight:700;color:<?=$c?>"><?=$warmth['msg']?></div>
        <div class="text-sm text-muted mt-1"><?=$dirHint?></div>
      </div>
      <?php endif;?>
      <?php if($hintResult):?><div class="success-box mb-2">💡 Hint: <?=$hintResult?></div><?php endif;?>
      <?php if($powerupMsg):?><div class="success-box mb-2"><?=$powerupMsg?></div><?php endif;?>

      <?php if(!empty($guesses)):?>
      <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:12px;margin-bottom:12px">
        <div class="text-xs text-muted mb-2">Guesses</div>
        <div style="display:flex;flex-wrap:wrap;gap:7px">
          <?php foreach($guesses as $g): $w=$game->getWarmthHint($g,$secret,$maxR-$minR); $c=$wc[$w['cls']]; ?>
          <span class="mono" style="background:color-mix(in srgb,<?=$c?> 14%,transparent);border:1px solid color-mix(in srgb,<?=$c?> 35%,transparent);color:<?=$c?>;padding:5px 12px;border-radius:999px;font-size:.88rem"><?=$g?></span>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>

      <form method="POST">
        <div class="form-group">
          <label>Your Guess (<?=$curMin?> – <?=$curMax?>)</label>
          <input type="number" name="guess" min="<?=$curMin?>" max="<?=$curMax?>" required autofocus placeholder="Enter a number…">
        </div>
        <button class="btn btn-primary" type="submit">Submit Guess</button>
      </form>

      <div class="divider"></div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between">
        <div>
          <?php if(($u['hint_xp']??0)>=50):?>
          <a href="?action=use_hint" class="btn btn-ghost btn-sm">💡 Hint (50 XP)</a>
          <?php else:?><span class="text-xs text-muted">💡 Need 50 XP</span><?php endif;?>
        </div>
        <div class="flex" style="gap:5px;flex-wrap:wrap">
          <?php
          $puList=[
            'range_narrow'=>'🔭','reveal_digit'=>'🔮','extra_attempt'=>'❤️',
            'freeze_timer'=>'⏸️','double_coins'=>'🪙','shadow_guess'=>'👥',
            'range_bomb'=>'💣','hot_cold_oracle'=>'🌡️','mistake_shield'=>'🛡️','xp_surge'=>'📈'
          ];
          foreach($puList as $k=>$ic):
            $cnt=$u['powerups'][$k]??0;
            if($cnt>0):
          ?>
          <a href="?action=use_powerup&type=<?=$k?>" class="btn btn-ghost btn-sm" title="<?=$k?>"><?=$ic?> ×<?=$cnt?></a>
          <?php endif; endforeach;?>
          <a href="?action=shop" class="btn btn-ghost btn-sm" title="Shop">🛒</a>
        </div>
      </div>
    </div>
    <script>
    let frozen=<?=$frozenTime?>,s=<?=$elapsed?>;
    const el=document.getElementById('ts');
    setInterval(()=>{if(frozen>0){frozen--;return;}el.textContent=++s;},1000);
    </script>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// RESULTS
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='results'){
    requireAuth();
    [$th,$dm]=userTheme();
    $result=$_GET['result']??'lose'; $secret=$_SESSION['secret']??0;
    $guesses=$_SESSION['guesses']??[]; $attUsed=count($guesses);
    $difficulty=$_SESSION['difficulty']??'Medium';
    $timeSecs=max(1,time()-($_SESSION['start_time']??time()));
    $hintsUsed=$_SESSION['hints_used']??0; $puUsed=$_SESSION['powerup_used']??false;
    $isDaily=$_SESSION['is_daily']??false; $won=$result==='win';
    $puTypes=$_SESSION['powerup_types_used']??[];

    $uBefore=$game->getUser($username);
    $stats=$game->updateUserStats($username,$won,$difficulty,$attUsed,$timeSecs,$hintsUsed,$puUsed,false,$isDaily,[
        'secret'=>$secret,'powerup_type'=>$puTypes[0]??'',
        'min_range'=>$_SESSION['min']??1,'max_range'=>$_SESSION['max_range']??10,
    ]);
    if($won){ $u2=$game->getUser($username); $game->updateUser($username,['hint_xp'=>($u2['hint_xp']??0)+($stats['xp']??0)]); }

    $u=$game->getUser($username); $xpEarned=$stats['xp']??0; $coinsEarned=$stats['coins']??0;
    $newAch=$stats['new_achievements']??[]; $achs=$game->getAchievements();
    [$lvl,$pct]=levelProgress($u['experience']);
    $wc=$won?'#22c55e':'#ef4444';

    page_start('Results',$th,$dm);
    ?>
    <div class="card" style="text-align:center">
      <div style="font-size:3.5rem;margin-bottom:12px" class="pop-in"><?=$won?'🎉':'💔'?></div>
      <h1 style="margin-bottom:8px"><?=$won?'You Won!':'Game Over'?></h1>
      <p class="mb-2"><?=$won?"Cracked it in <strong>{$attUsed}</strong> attempt".($attUsed!==1?'s':'')." · {$timeSecs}s":"Secret was <strong style='color:var(--acc2);font-family:Space Mono,monospace'>{$secret}</strong>"?></p>

      <div style="background:color-mix(in srgb,<?=$wc?> 8%,transparent);border:1px solid color-mix(in srgb,<?=$wc?> 25%,transparent);border-radius:14px;padding:20px;margin-bottom:18px">
        <div class="flex" style="justify-content:center;gap:24px">
          <div><div class="mono" style="font-size:1.9rem;font-weight:700;color:<?=$wc?>">+<?=$xpEarned?></div><div class="text-xs text-muted">XP</div></div>
          <div><div class="mono" style="font-size:1.9rem;font-weight:700;color:#fbbf24">+<?=$coinsEarned?></div><div class="text-xs text-muted">Coins 🪙</div></div>
        </div>
        <div class="text-sm text-muted mt-2"><?=htmlspecialchars($difficulty)?><?=$isDaily?' · Daily':''?><?=$hintsUsed?" · {$hintsUsed} hint".($hintsUsed!==1?'s':''):''?></div>
        <?php if($u['streak']>=2):?><div class="text-sm mt-1" style="color:#f97316">🔥 <?=$u['streak']?>-win streak!</div><?php endif;?>
      </div>

      <?php if(!empty($newAch)):?>
      <div style="margin-bottom:16px">
        <div class="text-xs text-muted mb-2">🏅 Achievements Unlocked!</div>
        <?php foreach($newAch as $k):
          $a=$achs[$k]??['name'=>$k,'icon'=>'🏅','desc'=>''];
        ?>
        <div style="background:color-mix(in srgb,var(--acc1) 10%,transparent);border:1px solid color-mix(in srgb,var(--acc1) 25%,transparent);border-radius:10px;padding:11px;margin-top:8px;text-align:left;display:flex;align-items:center;gap:11px">
          <span style="font-size:1.6rem"><?=$a['icon']?></span>
          <div><strong style="font-size:.9rem"><?=$a['name']?></strong><div class="text-xs text-muted"><?=$a['desc']?></div></div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>

      <div class="text-xs text-muted mb-1">Level <?=$lvl?> Progress</div>
      <div class="progress-wrap mb-2"><div class="progress-bar" style="width:<?=$pct?>%"></div></div>

      <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:12px;margin-bottom:18px;text-align:left">
        <div class="text-xs text-muted mb-2">Guesses</div>
        <div style="display:flex;flex-wrap:wrap;gap:7px">
          <?php foreach($guesses as $i=>$g):
            $col=($i===count($guesses)-1&&$won)?'#22c55e':'var(--sub)';
          ?><span class="mono" style="background:var(--brd);color:<?=$col?>;padding:5px 12px;border-radius:999px;font-size:.88rem"><?=$g?></span>
          <?php endforeach;?>
        </div>
      </div>

      <div class="stacks">
        <a href="?action=select" class="btn btn-primary">▶ Play Again</a>
        <div class="grid-2">
          <a href="?action=multiplayer" class="btn btn-ghost">⚔️ Multi</a>
          <a href="?action=menu" class="btn btn-ghost">← Menu</a>
        </div>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACHIEVEMENTS — with category filtering
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='achievements'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username);
    $earned=$u['achievements']??[];
    $achByCat=$game->getAchievementsByCategory();
    $allAchs=$game->getAchievements();
    $filterCat=$_GET['cat']??'All';
    $cats=array_keys($achByCat);
    sort($cats);
    page_start('Achievements',$th,$dm);
    ?>
    <div class="card card-xl">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🏅 Achievements</h1>
      <div class="flex-between mb-2">
        <p class="text-muted text-sm"><?=count($earned)?> / <?=count($allAchs)?> unlocked</p>
        <div class="progress-wrap" style="width:140px;margin:0"><div class="progress-bar" style="width:<?=round(count($earned)/max(1,count($allAchs))*100)?>%"></div></div>
      </div>

      <!-- Category filter -->
      <div class="flex mb-3" style="gap:6px;flex-wrap:wrap">
        <a href="?action=achievements&cat=All" class="btn btn-sm <?=$filterCat==='All'?'btn-primary':'btn-ghost'?>" style="min-height:30px;font-size:.7rem">All (<?=count($allAchs)?>)</a>
        <?php foreach($cats as $cat):
          $catCount=count($achByCat[$cat]);
          $catEarned=count(array_filter(array_keys($achByCat[$cat]),fn($k)=>in_array($k,$earned)));
        ?>
        <a href="?action=achievements&cat=<?=urlencode($cat)?>" class="btn btn-sm <?=$filterCat===$cat?'btn-primary':'btn-ghost'?>" style="min-height:30px;font-size:.7rem"><?=$cat?> (<?=$catEarned?>/<?=$catCount?>)</a>
        <?php endforeach;?>
      </div>

      <?php
      $displayAchs=$filterCat==='All'?$allAchs:($achByCat[$filterCat]??[]);
      // Sort: earned first, then by name
      $earnedAchs=[]; $lockedAchs=[];
      foreach($displayAchs as $k=>$a){
          if(in_array($k,$earned))$earnedAchs[$k]=$a;
          else $lockedAchs[$k]=$a;
      }
      $sorted=array_merge($earnedAchs,$lockedAchs);
      ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <?php foreach($sorted as $key=>$a):
          $got=in_array($key,$earned);
        ?>
        <div class="ach-item <?=$got?'earned':'locked'?>">
          <div class="ach-icon"><?=$a['icon']?></div>
          <div class="ach-info">
            <div class="ach-name" style="color:<?=$got?'var(--acc2)':'var(--sub)'?>"><?=$a['name']?></div>
            <div class="ach-desc"><?=$a['desc']?></div>
          </div>
          <?php if($got):?><div style="color:#4ade80;font-size:1rem;flex-shrink:0">✅</div><?php endif;?>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// RANKINGS
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='rankings'){
    requireAuth();
    [$th,$dm]=userTheme();
    $rankings=$game->getRankings(); $medals=['🥇','🥈','🥉'];
    page_start('Rankings',$th,$dm);
    ?>
    <div class="card card-wide">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🏆 Rankings</h1>
      <div class="stacks">
        <?php foreach($rankings as $i=>$data):
          $isMe=$data['username']===$username; $medal=$medals[$i]??'#'.($i+1);
          [$lvl]=levelProgress($data['experience']);
          $wr=$data['games_played']>0?round($data['games_won']/$data['games_played']*100):0;
        ?>
        <div style="background:<?=$isMe?'color-mix(in srgb,var(--acc1) 10%,transparent)':'var(--card)'?>;border:1.5px solid <?=$isMe?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:14px 17px;display:flex;align-items:center;gap:12px">
          <div style="font-size:1.4rem;width:34px;text-align:center;flex-shrink:0"><?=$medal?></div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;color:<?=$isMe?'var(--acc2)':'var(--txt)'?>"><?=htmlspecialchars($data['username'])?><?=$isMe?' (you)':''?></div>
            <div class="text-xs text-muted">Lv <?=$lvl?> · <?=$data['experience']?> XP · <?=$data['games_won']?> wins · <?=$wr?>% WR<?=($data['best_streak']??0)>=3?' · 🔥'.($data['best_streak']).' streak':''?> · 🏅<?=count($data['achievements']??[])?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div class="text-xs text-muted">Lv</div>
            <div class="mono" style="font-size:1.4rem;color:var(--acc2);font-weight:700"><?=$lvl?></div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATS
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='stats'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username); [$lvl,$pct,$xpLeft]=levelProgress($u['experience']);
    $history=array_reverse($u['game_history']??[]);
    $wonTimes=array_column(array_filter($history,fn($h)=>$h['won']),'time');
    $avgTime=$wonTimes?round(array_sum($wonTimes)/count($wonTimes)):0;
    page_start('Stats',$th,$dm);
    ?>
    <div class="card card-wide">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">📊 Statistics</h1>
      <div class="grid-2 mb-2">
        <div class="stat-box"><div class="stat-val"><?=$lvl?></div><div class="stat-lbl">Level</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['experience']?></div><div class="stat-lbl">Total XP</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['coins']??0?> 🪙</div><div class="stat-lbl">Coins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_won']?></div><div class="stat-lbl">Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['best_streak']?><?=($u['best_streak']??0)>=3?' 🔥':''?></div><div class="stat-lbl">Best Streak</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['multi_wins']??0?> ⚔️</div><div class="stat-lbl">Multi Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['daily_total']??0?></div><div class="stat-lbl">Dailies Done</div></div>
        <div class="stat-box"><div class="stat-val"><?=$avgTime?>s</div><div class="stat-lbl">Avg Win Time</div></div>
        <div class="stat-box"><div class="stat-val"><?=count($u['achievements']??[])?></div><div class="stat-lbl">Achievements</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['shop_purchases']??0?></div><div class="stat-lbl">Purchases</div></div>
      </div>
      <h2>Per Difficulty</h2>
      <div class="stacks mb-2">
        <?php foreach(['Easy'=>'#4ade80','Medium'=>'#fbbf24','Hard'=>'#f87171'] as $d=>$col):
          $ds=$u['diff_stats'][$d]??['w'=>0,'p'=>0]; $wr=$ds['p']>0?round($ds['w']/$ds['p']*100):0;
        ?>
        <div class="stat-box flex-between" style="text-align:left;padding:12px 16px">
          <span style="font-weight:700;color:<?=$col?>"><?=$d?></span>
          <span class="text-sm text-muted"><?=$ds['w']?>/<?=$ds['p']?> wins · <strong class="text-acc"><?=$wr?>%</strong></span>
        </div>
        <?php endforeach;?>
      </div>
      <h2>Recent Games</h2>
      <?php if(empty($history)):?><p class="text-muted text-sm">No games yet — go play!</p>
      <?php else:?>
      <div class="stacks">
        <?php foreach(array_slice($history,0,15) as $h):?>
        <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:10px">
          <span><?=$h['won']?'✅':'❌'?> <strong><?=$h['diff']?></strong><?=($h['multi']??false)?' ⚔️':''?><?=($h['daily']??false)?' 📅':''?></span>
          <span class="text-sm text-muted mono"><?=$h['attempts']?> att · <?=$h['time']?>s</span>
          <span class="text-xs text-muted"><?=date('M j',$h['date'])?></span>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// THEMES
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='themes'){
    requireAuth();
    $themes=$game->getThemes();
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $u0=$game->getUser($username); [$lvl0]=levelProgress($u0['experience']);
        $t=$_POST['theme']??'default';
        if(isset($themes[$t])&&$lvl0>=$themes[$t]['unlock_level']){
            $game->updateUser($username,['theme'=>$t,'theme_changes'=>($u0['theme_changes']??0)+1]);
        }
        header('Location: ?action=themes'); exit;
    }
    [$th,$dm]=userTheme();
    $u=$game->getUser($username); [$lvl]=levelProgress($u['experience']);
    page_start('Themes',$th,$dm);
    ?>
    <div class="card">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🎨 Themes</h1>
      <p class="text-muted text-sm mb-2">Level <?=$lvl?> — unlock more by levelling up</p>
      <form method="POST">
        <div class="stacks">
          <?php foreach($themes as $key=>$t):
            $unlocked=$lvl>=$t['unlock_level']; $active=($u['theme']??'default')===$key;
          ?>
          <div style="background:<?=$active?'color-mix(in srgb,var(--acc1) 10%,transparent)':'var(--card)'?>;border:1.5px solid <?=$active?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:14px 17px;display:flex;align-items:center;justify-content:space-between;gap:12px;opacity:<?=$unlocked?'1':'.4'?>;transition:border-color .2s,opacity .2s">
            <div class="flex">
              <span style="font-size:1.7rem"><?=$t['icon']?></span>
              <div><div style="font-weight:700"><?=$t['name']?></div><div class="text-xs text-muted">Unlocks at Lv <?=$t['unlock_level']?></div></div>
            </div>
            <?php if($active):?><span class="badge badge-acc">Active</span>
            <?php elseif($unlocked):?><button type="submit" name="theme" value="<?=$key?>" class="btn btn-primary btn-sm">Select</button>
            <?php else:?><span class="text-xs text-muted">🔒 Lv <?=$t['unlock_level']?></span>
            <?php endif;?>
          </div>
          <?php endforeach;?>
        </div>
      </form>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='logout'){
    session_destroy(); header('Location: ?action=login'); exit;
} else {
    header('Location: ?action=login'); exit;
}
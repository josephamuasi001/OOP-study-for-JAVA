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
    private $achievements = [
        'first_win'     =>['name'=>'First Blood',   'icon'=>'🎯','desc'=>'Win your first game'],
        'win_streak_3'  =>['name'=>'On Fire',        'icon'=>'🔥','desc'=>'3 wins in a row'],
        'win_streak_5'  =>['name'=>'Unstoppable',    'icon'=>'⚡','desc'=>'5 wins in a row'],
        'win_streak_10' =>['name'=>'Legendary',      'icon'=>'👑','desc'=>'10 wins in a row'],
        'one_shot'      =>['name'=>'Lucky Shot',     'icon'=>'🍀','desc'=>'Guess correctly on first try'],
        'hard_win'      =>['name'=>'Hardboiled',     'icon'=>'💪','desc'=>'Win on Hard difficulty'],
        'speed_demon'   =>['name'=>'Speed Demon',    'icon'=>'⚡','desc'=>'Win in under 10 seconds'],
        'centurion'     =>['name'=>'Centurion',      'icon'=>'🏅','desc'=>'Play 100 games'],
        'level_10'      =>['name'=>'Veteran',        'icon'=>'🌟','desc'=>'Reach level 10'],
        'level_50'      =>['name'=>'Elite',          'icon'=>'💎','desc'=>'Reach level 50'],
        'daily_champion'=>['name'=>'Daily Grinder',  'icon'=>'📅','desc'=>'Complete a daily challenge'],
        'hint_hoarder'  =>['name'=>'Thrifty',        'icon'=>'💰','desc'=>'Use 0 hints in 10 games'],
        'power_player'  =>['name'=>'Power Player',   'icon'=>'🚀','desc'=>'Use a power-up to win'],
        'win_50'        =>['name'=>'Sharpshooter',   'icon'=>'🎖️','desc'=>'Win 50 games total'],
        'efficiency'    =>['name'=>'Efficient',      'icon'=>'🧠','desc'=>'Win using 50% or fewer attempts'],
        'multi_winner'  =>['name'=>'Duelist',        'icon'=>'⚔️','desc'=>'Win your first multiplayer game'],
        'multi_streak3' =>['name'=>'Champion',       'icon'=>'🏆','desc'=>'Win 3 multiplayer games in a row'],
        'rich'          =>['name'=>'Moneybags',      'icon'=>'💰','desc'=>'Accumulate 500 coins'],
        'shopaholic'    =>['name'=>'Shopaholic',     'icon'=>'🛒','desc'=>'Buy 10 items from the shop'],
    ];
    private $themes = [
        'default'=>['name'=>'Neon Noir',     'unlock_level'=>1,  'icon'=>'🌃'],
        'ocean'  =>['name'=>'Deep Ocean',    'unlock_level'=>5,  'icon'=>'🌊'],
        'forest' =>['name'=>'Ancient Forest','unlock_level'=>10, 'icon'=>'🌲'],
        'volcano'=>['name'=>'Volcano',       'unlock_level'=>20, 'icon'=>'🌋'],
        'galaxy' =>['name'=>'Galaxy',        'unlock_level'=>35, 'icon'=>'🌌'],
        'gold'   =>['name'=>'Gold Rush',     'unlock_level'=>50, 'icon'=>'✨'],
    ];
    // Powerup shop items
    private $shopItems = [
        'range_narrow' =>['name'=>'Range Narrower','icon'=>'🔭','desc'=>'Narrows the number range by 50%','cost'=>30,'max'=>5],
        'reveal_digit' =>['name'=>'Digit Revealer','icon'=>'🔮','desc'=>'Reveals the last digit of the number','cost'=>50,'max'=>3],
        'extra_attempt'=>['name'=>'Extra Life',    'icon'=>'❤️', 'desc'=>'Grants +1 extra attempt this game','cost'=>40,'max'=>3],
        'freeze_timer' =>['name'=>'Time Freeze',   'icon'=>'⏸️', 'desc'=>'Pauses the timer for 30 seconds','cost'=>25,'max'=>5],
        'double_coins' =>['name'=>'Coin Doubler',  'icon'=>'🪙', 'desc'=>'Doubles coins earned next win','cost'=>60,'max'=>2],
        'hint_boost'   =>['name'=>'Hint Refill',   'icon'=>'💡', 'desc'=>'Gives 100 bonus Hint XP','cost'=>35,'max'=>10],
    ];

    private $usersFile  = __DIR__.'/ng_users.json';
    private $dailyFile  = __DIR__.'/ng_daily.json';
    private $roomsFile  = __DIR__.'/ng_rooms.json';

    public function getDifficulties(){ return $this->games; }
    public function getGameConfig($d){ return $this->games[$d]??null; }
    public function getAchievements(){ return $this->achievements; }
    public function getThemes(){ return $this->themes; }
    public function getShopItems(){ return $this->shopItems; }
    public function generateNumber($mn,$mx){ return rand($mn,$mx); }

    // ── USERS ──────────────────────────────────────────────────────────────
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
            'password'=>password_hash($password,PASSWORD_DEFAULT),'level'=>1,'experience'=>0,
            'games_won'=>0,'games_played'=>0,'streak'=>0,'best_streak'=>0,
            'hints_used'=>0,'hint_xp'=>200,'achievements'=>[],'theme'=>'default','dark_mode'=>true,
            'powerups'=>['range_narrow'=>2,'reveal_digit'=>1,'extra_attempt'=>0,'freeze_timer'=>0,'double_coins'=>0,'hint_boost'=>0],
            'game_history'=>[],'no_hint_games'=>0,'coins'=>50,
            'created_at'=>time(),'last_daily'=>null,'daily_streak'=>0,
            'diff_stats'=>['Easy'=>['w'=>0,'p'=>0],'Medium'=>['w'=>0,'p'=>0],'Hard'=>['w'=>0,'p'=>0]],
            'shop_purchases'=>0,'multi_wins'=>0,'multi_streak'=>0,'coin_doubler_active'=>false,
        ];
        $this->saveUsers($users); return true;
    }
    public function loginUser($u,$p){
        $users=$this->loadUsers();
        if(!isset($users[$u])||!password_verify($p,$users[$u]['password']))return false;
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
        if(file_exists($this->dailyFile)){
            $d=json_decode(file_get_contents($this->dailyFile),true);
            if($d&&($d['date']??'')===$today)return $d;
        }
        $seed=crc32($today); srand($seed); $secret=rand(1,500); srand();
        $daily=['date'=>$today,'min'=>1,'max'=>500,'secret'=>$secret,'attempts'=>8];
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
        $u['powerups'][$item]=($u['powerups'][$item]??0)+1;
        $u['shop_purchases']=($u['shop_purchases']??0)+1;
        $newAch=[];
        if($u['shop_purchases']>=10&&!in_array('shopaholic',$u['achievements']??[])){
            $u['achievements'][]='shopaholic'; $newAch[]='shopaholic';
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
        // cleanup stale rooms (>30 min old)
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
        return $code;
    }
    public function joinRoom($code,$username){
        $rooms=$this->loadRooms();
        $code=strtoupper(trim($code));
        if(!isset($rooms[$code]))return['ok'=>false,'msg'=>'Room not found'];
        $r=&$rooms[$code];
        if($r['status']==='finished')return['ok'=>false,'msg'=>'Game already finished'];
        if(count($r['players'])>=$r['max_players']&&!isset($r['players'][$username]))
            return['ok'=>false,'msg'=>'Room is full'];
        if(!isset($r['players'][$username]))
            $r['players'][$username]=['joined'=>time(),'guesses'=>[],'attempts'=>0,'won'=>false,'finish_time'=>null,'ready'=>false];
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
        // check if all done
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
        $r['chat'][]=array_slice([...$r['chat'],['u'=>$username,'m'=>substr(strip_tags($msg),0,100),'t'=>time()]],-50);
        $this->saveRooms($rooms);
    }
    public function getRoomState($code){
        $rooms=$this->loadRooms(); $code=strtoupper($code);
        return $rooms[$code]??null;
    }

    // ── STATS ─────────────────────────────────────────────────────────────
    public function updateUserStats($username,$won,$difficulty,$attemptsUsed,$timeSecs,$hintsUsed,$usedPowerup,$isMulti=false){
        $users=$this->loadUsers();
        if(!isset($users[$username]))return[];
        $u=&$users[$username]; $u['games_played']++; $u['diff_stats'][$difficulty]['p']++;
        $newAchievements=[];
        if($won){
            $u['games_won']++; $u['streak']++; $u['diff_stats'][$difficulty]['w']++;
            if($u['streak']>$u['best_streak'])$u['best_streak']=$u['streak'];
            $cfg=$this->games[$difficulty];
            $streakBonus=min(3.0,1.0+($u['streak']-1)*0.2);
            $attemptBonus=max(0.5,1.0-(($attemptsUsed-1)/$cfg['attempts'])*0.5);
            $xp=(int)round(max(50,$cfg['xp_base']*$cfg['xp_mult']*$streakBonus*$attemptBonus));
            $coins=(int)round($cfg['coins_base']*$streakBonus*($u['coin_doubler_active']??false?2:1));
            $u['coin_doubler_active']=false;
            if($hintsUsed===0)$u['no_hint_games']++;
            else{$u['no_hint_games']=0;$u['hints_used']+=$hintsUsed;}
            if($usedPowerup&&!in_array('power_player',$u['achievements'])){$u['achievements'][]='power_player';$newAchievements[]='power_player';}
            $checks=[
                'first_win'=>$u['games_won']===1,'win_streak_3'=>$u['streak']>=3,'win_streak_5'=>$u['streak']>=5,
                'win_streak_10'=>$u['streak']>=10,'one_shot'=>$attemptsUsed===1,'hard_win'=>$difficulty==='Hard',
                'speed_demon'=>$timeSecs<10,'win_50'=>$u['games_won']>=50,'efficiency'=>$attemptsUsed<=(int)ceil($cfg['attempts']/2)
            ];
            foreach($checks as $key=>$cond)
                if($cond&&!in_array($key,$u['achievements'])){$u['achievements'][]=$key;$newAchievements[]=$key;$xp+=100;}
            if($isMulti){
                $u['multi_wins']=($u['multi_wins']??0)+1; $u['multi_streak']=($u['multi_streak']??0)+1;
                if(!in_array('multi_winner',$u['achievements'])){$u['achievements'][]='multi_winner';$newAchievements[]='multi_winner';}
                if(($u['multi_streak']??0)>=3&&!in_array('multi_streak3',$u['achievements'])){$u['achievements'][]='multi_streak3';$newAchievements[]='multi_streak3';}
            }
            $u['experience']+=$xp; $u['coins']=($u['coins']??0)+$coins;
            if(($u['coins']??0)>=500&&!in_array('rich',$u['achievements'])){$u['achievements'][]='rich';$newAchievements[]='rich';}
        } else {
            $u['streak']=0; if($isMulti)$u['multi_streak']=0;
            $u['no_hint_games']=0;$xp=10;$coins=5;
            $u['experience']+=$xp; $u['coins']=($u['coins']??0)+$coins;
        }
        $u['level']=min(1000,(int)floor($u['experience']/50)+1);
        foreach(['level_10'=>10,'level_50'=>50] as $key=>$lvl)
            if($u['level']>=$lvl&&!in_array($key,$u['achievements'])){$u['achievements'][]=$key;$newAchievements[]=$key;}
        if($u['games_played']>=100&&!in_array('centurion',$u['achievements'])){$u['achievements'][]='centurion';$newAchievements[]='centurion';}
        if($u['no_hint_games']>=10&&!in_array('hint_hoarder',$u['achievements'])){$u['achievements'][]='hint_hoarder';$newAchievements[]='hint_hoarder';}
        $u['game_history'][]=['diff'=>$difficulty,'won'=>$won,'attempts'=>$attemptsUsed,'time'=>$timeSecs,'date'=>time(),'multi'=>$isMulti];
        if(count($u['game_history'])>50)array_shift($u['game_history']);
        $u['powerups']['range_narrow']=min(5,($u['powerups']['range_narrow']??0)+($won?1:0));
        if($won&&$difficulty==='Hard')$u['powerups']['reveal_digit']=min(3,($u['powerups']['reveal_digit']??0)+1);
        $this->saveUsers($users);
        return['xp'=>$xp??10,'coins'=>$coins??5,'new_achievements'=>$newAchievements];
    }
    public function getRankings(){
        $users=$this->loadUsers();$list=[];
        foreach($users as $uname=>$data)$list[]=array_merge($data,['username'=>$uname]);
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
        if($pct===0)   return['msg'=>'🎯 Exact!',          'cls'=>'exact'];
        if($pct<0.02)  return['msg'=>'🔥🔥🔥 ON FIRE!',    'cls'=>'blazing'];
        if($pct<0.05)  return['msg'=>'🔥🔥 Scorching!',    'cls'=>'hot'];
        if($pct<0.10)  return['msg'=>'🌡️ Very Warm',       'cls'=>'warm'];
        if($pct<0.20)  return['msg'=>'☀️ Getting Warmer',  'cls'=>'lukewarm'];
        if($pct<0.35)  return['msg'=>'🌤️ Tepid',           'cls'=>'tepid'];
        if($pct<0.55)  return['msg'=>'❄️ Cold',            'cls'=>'cold'];
        return              ['msg'=>'🧊 Freezing!',         'cls'=>'freezing'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// THEME DEFINITIONS
// ═══════════════════════════════════════════════════════════════════════════════
function themeVars($theme,$dark){
    $themes=[
        'default'=>[
            'dark' =>'--bg:#0a0a14;--panel:#13131f;--acc1:#6d5cf6;--acc2:#a78bfa;--txt:#e8e8f0;--sub:#7b7b9a;--brd:#1e1e35;--card:#1a1a2e',
        ],
        'ocean'=>[
            'light'=>'--bg:#e8f4f8;--panel:#ffffff;--acc1:#0077b6;--acc2:#00b4d8;--txt:#03045e;--sub:#023e8a;--brd:#caf0f8;--card:#f0f8ff',
            'dark' =>'--bg:#030a1a;--panel:#071428;--acc1:#00b4d8;--acc2:#90e0ef;--txt:#caf0f8;--sub:#90e0ef;--brd:#0d2545;--card:#0a1e3d',
        ],
        'forest'=>[
            'light'=>'--bg:#f0fdf4;--panel:#ffffff;--acc1:#16a34a;--acc2:#4ade80;--txt:#14532d;--sub:#166534;--brd:#bbf7d0;--card:#f0fdf4',
            'dark' =>'--bg:#030f07;--panel:#091a0e;--acc1:#4ade80;--acc2:#86efac;--txt:#dcfce7;--sub:#bbf7d0;--brd:#0f2d16;--card:#112418',
        ],
        'volcano'=>[
            'light'=>'--bg:#fff7ed;--panel:#ffffff;--acc1:#ea580c;--acc2:#fb923c;--txt:#431407;--sub:#7c2d12;--brd:#fed7aa;--card:#fff7ed',
            'dark' =>'--bg:#0f0500;--panel:#1a0a00;--acc1:#fb923c;--acc2:#fdba74;--txt:#ffedd5;--sub:#fed7aa;--brd:#2d1000;--card:#231000',
        ],
        'galaxy'=>[
            'light'=>'--bg:#f5f3ff;--panel:#ffffff;--acc1:#7c3aed;--acc2:#a78bfa;--txt:#2e1065;--sub:#4c1d95;--brd:#ddd6fe;--card:#f5f3ff',
            'dark' =>'--bg:#050312;--panel:#0c0824;--acc1:#a78bfa;--acc2:#c4b5fd;--txt:#ede9fe;--sub:#c4b5fd;--brd:#170f40;--card:#130b38',
        ],
        'gold'=>[
            'light'=>'--bg:#fefce8;--panel:#ffffff;--acc1:#ca8a04;--acc2:#eab308;--txt:#422006;--sub:#713f12;--brd:#fef08a;--card:#fefce8',
            'dark' =>'--bg:#0d0900;--panel:#1a1200;--acc1:#eab308;--acc2:#facc15;--txt:#fefce8;--sub:#fef08a;--brd:#2a1e00;--card:#231900',
        ],
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
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,viewport-fit=cover\">
<meta name=\"theme-color\" content=\"#0a0a14\">
<title>{$esc} — NumGenius</title>
<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
<link href=\"https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">
<style>
:root { {$vars} }
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%;-webkit-text-size-adjust:100%}
body{
  font-family:'Syne',system-ui,sans-serif;
  font-size:1rem;line-height:1.6;
  background:var(--bg);color:var(--txt);
  min-height:100vh;min-height:100dvh;
  display:grid;place-items:start center;
  padding:clamp(14px,4vw,40px);
  padding-top:max(clamp(20px,4vw,40px),env(safe-area-inset-top));
  padding-bottom:max(clamp(14px,4vw,40px),env(safe-area-inset-bottom));
  position:relative;overflow-x:hidden;
  background-image:
    radial-gradient(ellipse 80% 50% at 50% -20%,color-mix(in srgb,var(--acc1) 12%,transparent),transparent),
    radial-gradient(ellipse 60% 40% at 80% 110%,color-mix(in srgb,var(--acc2) 6%,transparent),transparent);
}
body::after{
  content:'';position:fixed;inset:0;pointer-events:none;
  background-image:url(\"data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E\");
  z-index:0;opacity:.6;
}
.card{
  position:relative;z-index:1;
  background:var(--panel);
  border:1px solid var(--brd);
  border-radius:clamp(16px,2vw,24px);
  padding:clamp(22px,5vw,44px);
  width:100%;max-width:520px;
  box-shadow:0 0 0 1px color-mix(in srgb,var(--acc1) 8%,transparent),0 16px 48px rgba(0,0,0,.5);
  animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both;
  margin:0 auto 24px;
}
.card-wide{max-width:640px}
.card-xl{max-width:780px}
@keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:none}}
h1{
  font-family:'Space Mono',monospace;font-weight:700;
  font-size:clamp(1.4rem,5vw,2rem);
  letter-spacing:-.03em;line-height:1.2;text-align:center;
  margin-bottom:clamp(18px,4vw,28px);
  background:linear-gradient(135deg,var(--acc1),var(--acc2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
h2{font-size:clamp(.9rem,2.5vw,1.05rem);font-weight:700;color:var(--acc2);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;font-size:.8rem}
p{color:var(--sub)}
.form-group{margin-bottom:clamp(12px,2.5vw,18px)}
label{display:block;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--sub);margin-bottom:5px}
input,select,textarea{
  width:100%;min-height:48px;padding:11px 15px;
  background:var(--card);border:1.5px solid var(--brd);border-radius:10px;
  color:var(--txt);font-size:1rem;font-family:inherit;
  outline:none;transition:border-color .2s,box-shadow .2s;
  -webkit-appearance:none;appearance:none;
}
textarea{min-height:72px;resize:vertical}
@media(max-width:480px){input,select,textarea{font-size:16px}}
input:focus,select:focus,textarea:focus{border-color:var(--acc1);box-shadow:0 0 0 3px color-mix(in srgb,var(--acc1) 18%,transparent)}
input[type=number]{font-family:'Space Mono',monospace;font-size:1.2rem;text-align:center;font-weight:700}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  width:100%;min-height:48px;padding:12px 20px;
  border:none;border-radius:10px;cursor:pointer;
  font-size:.9rem;font-family:'Syne',inherit;font-weight:700;letter-spacing:.04em;
  text-decoration:none;text-align:center;
  transition:transform .15s,box-shadow .15s,background .15s;
  -webkit-tap-highlight-color:transparent;touch-action:manipulation;user-select:none;white-space:nowrap;
}
.btn:active{transform:scale(.96)}
.btn-primary{background:linear-gradient(135deg,var(--acc1),var(--acc2));color:#fff;box-shadow:0 4px 20px color-mix(in srgb,var(--acc1) 30%,transparent)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px color-mix(in srgb,var(--acc1) 45%,transparent)}
.btn-ghost{background:var(--card);color:var(--txt);border:1.5px solid var(--brd)}
.btn-ghost:hover{border-color:var(--acc1);color:var(--acc2)}
.btn-sm{min-height:36px;padding:6px 14px;font-size:.78rem;width:auto;border-radius:8px}
.btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
.btn-gold{background:linear-gradient(135deg,#ca8a04,#eab308);color:#000;font-weight:800}
.grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:clamp(8px,2vw,14px)}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:clamp(7px,1.5vw,12px)}
@media(max-width:380px){.grid-2,.grid-3{grid-template-columns:1fr}}
.stat-box{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:clamp(12px,2.5vw,20px) 8px;text-align:center;transition:border-color .2s,transform .15s}
.stat-box:hover{border-color:color-mix(in srgb,var(--acc1) 40%,transparent);transform:translateY(-2px)}
.stat-val{font-family:'Space Mono',monospace;font-size:clamp(1.2rem,4vw,1.7rem);font-weight:700;background:linear-gradient(135deg,var(--acc1),var(--acc2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2}
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
.alert{padding:12px 15px;border-radius:10px;font-size:.88rem;margin-top:12px;border-left:3px solid}
.alert-err{background:color-mix(in srgb,#ef4444 12%,transparent);border-color:#ef4444;color:#fca5a5}
.alert-ok{background:color-mix(in srgb,#22c55e 12%,transparent);border-color:#22c55e;color:#86efac}
.progress-wrap{background:var(--brd);border-radius:999px;height:7px;overflow:hidden;margin:6px 0}
.progress-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--acc1),var(--acc2));transition:width .8s cubic-bezier(.22,1,.36,1)}
.warn-box{background:color-mix(in srgb,#f59e0b 10%,transparent);border:1px solid color-mix(in srgb,#f59e0b 40%,transparent);border-radius:10px;padding:13px;color:#fbbf24;font-size:.9rem}
.success-box{background:color-mix(in srgb,#22c55e 10%,transparent);border:1px solid color-mix(in srgb,#22c55e 40%,transparent);border-radius:10px;padding:13px;color:#86efac;font-size:.9rem}
.info-box{background:color-mix(in srgb,var(--acc1) 8%,transparent);border:1px solid color-mix(in srgb,var(--acc1) 30%,transparent);border-radius:10px;padding:13px;color:var(--acc2);font-size:.9rem}
.text-sm{font-size:.875rem}.text-xs{font-size:.73rem}.text-muted{color:var(--sub)}.text-acc{color:var(--acc2)}.text-center{text-align:center}
.text-green{color:#4ade80}.text-red{color:#f87171}.text-gold{color:#fbbf24}
.mono{font-family:'Space Mono',monospace}
.mt-1{margin-top:8px}.mt-2{margin-top:14px}.mt-3{margin-top:22px}
.mb-1{margin-bottom:8px}.mb-2{margin-bottom:14px}.mb-3{margin-bottom:22px}
.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Multiplayer styles */
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
/* Shop styles */
.shop-item{background:var(--card);border:1.5px solid var(--brd);border-radius:13px;padding:14px 16px;transition:border-color .2s,transform .15s}
.shop-item:hover{border-color:color-mix(in srgb,var(--acc1) 40%,transparent);transform:translateY(-2px)}
.shop-item .icon{font-size:2rem;margin-bottom:6px}
.shop-item .item-name{font-weight:700;font-size:.9rem;color:var(--txt)}
.shop-item .item-desc{font-size:.72rem;color:var(--sub);margin:3px 0 8px}
.shop-item .cost{font-family:'Space Mono',monospace;font-weight:700;color:#fbbf24;font-size:.85rem}
/* Room code display */
.room-code{font-family:'Space Mono',monospace;font-size:2.5rem;font-weight:700;letter-spacing:.25em;text-align:center;padding:18px;background:var(--card);border:2px dashed var(--brd);border-radius:14px;color:var(--acc2);margin:14px 0}
/* Landscape / desktop */
@media(max-height:480px) and (orientation:landscape){body{padding-top:10px;padding-bottom:10px}.card{padding:14px 22px}h1{margin-bottom:10px}.stacks{gap:7px}}
@media(min-width:900px){body{padding:48px}.card{padding:48px}}
/* Pulse animation for waiting */
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.pulse{animation:pulse 1.5s ease-in-out infinite}
@keyframes popIn{from{transform:scale(0) rotate(-10deg)}to{transform:scale(1) rotate(0)}}
.pop-in{animation:popIn .5s cubic-bezier(.22,1,.36,1) both}
/* Scrollbar */
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:var(--card)}
::-webkit-scrollbar-thumb{background:var(--brd);border-radius:2px}
</style>
</head>
<body>
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
    $pct=round(($xp-$xpCur)/($xpNext-$xpCur)*100);
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
            if(!isset($usr['hint_xp']))$game->updateUser($u,['hint_xp'=>$usr['experience']]);
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
      <!-- XP bar -->
      <div style="margin-bottom:16px">
        <div class="flex-between text-xs text-muted mb-1"><span><?=$u['experience']?> XP</span><span><?=$xpLeft?> to Lv <?=$lvl+1?></span></div>
        <div class="progress-wrap"><div class="progress-bar" style="width:<?=$pct?>%"></div></div>
      </div>
      <!-- Coin display -->
      <div class="info-box flex-between mb-2" style="padding:10px 15px">
        <span class="text-sm"><span style="font-size:1.1rem">🪙</span> <strong><?=$u['coins']??0?></strong> coins</span>
        <a href="?action=shop" class="btn btn-gold btn-sm" style="min-height:32px;font-size:.75rem">🛒 Shop</a>
      </div>
      <!-- Stats grid -->
      <div class="grid-2 mb-2" style="margin-bottom:14px">
        <div class="stat-box"><div class="stat-val"><?=$u['games_won']?></div><div class="stat-lbl">Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['streak']?><?=$u['streak']>=3?' 🔥':''?></div><div class="stat-lbl">Streak</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']?></div><div class="stat-lbl">Played</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']>0?round($u['games_won']/$u['games_played']*100):0?>%</div><div class="stat-lbl">Win Rate</div></div>
      </div>
      <!-- Daily banner -->
      <?php if(!$dailyDone):?>
      <a href="?action=daily" style="display:block;text-decoration:none;margin-bottom:12px">
        <div class="warn-box" style="cursor:pointer">📅 <strong>Daily Challenge!</strong> — 1–500 in 8 tries · Bonus XP + coins</div>
      </a>
      <?php else:?>
      <div class="success-box mb-2">✅ Daily done! Come back tomorrow.</div>
      <?php endif;?>
      <!-- Navigation -->
      <div class="stacks">
        <a href="?action=select" class="btn btn-primary" style="font-size:1rem">▶&nbsp; Play Solo</a>
        <a href="?action=multiplayer" class="btn btn-ghost" style="border-color:color-mix(in srgb,var(--acc2) 40%,transparent);color:var(--acc2)">⚔️&nbsp; Multiplayer</a>
        <div class="grid-2">
          <a href="?action=rankings" class="btn btn-ghost">🏆 Rankings</a>
          <a href="?action=achievements" class="btn btn-ghost">🎖 Achievements <span class="badge badge-acc"><?=count($earned)?>/<?=count($achs)?></span></a>
        </div>
        <div class="grid-2">
          <a href="?action=stats" class="btn btn-ghost">📊 Stats</a>
          <a href="?action=themes" class="btn btn-ghost">🎨 Themes</a>
        </div>
      </div>
      <!-- Powerup tray -->
      <div class="divider"></div>
      <div class="flex text-xs text-muted" style="justify-content:center;gap:16px;flex-wrap:wrap">
        <span>🔭 ×<?=$u['powerups']['range_narrow']??0?></span>
        <span>🔮 ×<?=$u['powerups']['reveal_digit']??0?></span>
        <span>❤️ ×<?=$u['powerups']['extra_attempt']??0?></span>
        <span>⏸️ ×<?=$u['powerups']['freeze_timer']??0?></span>
        <span>🪙× ×<?=$u['powerups']['double_coins']??0?></span>
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
        if($res['ok']){ $msg='Purchased! ('.$res['coins'].' coins remaining)'; $msgType='ok'; $u=$game->getUser($username); }
        else{ $msg=$res['msg']; $msgType='err'; }
    }
    page_start('Shop',$th,$dm);
    ?>
    <div class="card card-wide">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🛒 Power-Up Shop</h1>
      <div class="info-box flex-between mb-2" style="padding:10px 15px">
        <span>Your balance: <strong class="text-gold mono"><?=$u['coins']??0?> 🪙</strong></span>
        <span class="text-xs text-muted">Earn coins by playing games</span>
      </div>
      <?php if($msg):?><div class="alert alert-<?=$msgType==='ok'?'ok':'err'?> mb-2"><?=htmlspecialchars($msg)?></div><?php endif;?>
      <div class="grid-2" style="gap:12px">
        <?php foreach($shopItems as $key=>$it):
          $owned=$u['powerups'][$key]??0;
          $canBuy=($u['coins']??0)>=$it['cost']&&$owned<$it['max'];
        ?>
        <div class="shop-item">
          <div class="icon"><?=$it['icon']?></div>
          <div class="item-name"><?=$it['name']?></div>
          <div class="item-desc"><?=$it['desc']?></div>
          <div class="flex-between mt-1">
            <span class="cost"><?=$it['cost']?> 🪙</span>
            <span class="text-xs text-muted">Own: <?=$owned?>/<?=$it['max']?></span>
          </div>
          <form method="POST" style="margin-top:8px">
            <input type="hidden" name="item" value="<?=$key?>">
            <button class="btn <?=$canBuy?'btn-gold':'btn-ghost'?> btn-sm" style="width:100%;<?=!$canBuy?'opacity:.4;cursor:not-allowed':''?>" <?=!$canBuy?'disabled':''?>>
              <?=$owned>=$it['max']?'Max Owned':($canBuy?'Buy':'Need '.$it['cost'].'🪙')?>
            </button>
          </form>
        </div>
        <?php endforeach;?>
      </div>
      <div class="divider"></div>
      <p class="text-xs text-muted text-center">Win games to earn coins · Hard mode & streaks give more · Daily challenges give bonus coins</p>
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
    // Handle create room
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['create'])){
        $diff=$_POST['difficulty']??'Medium';
        $maxP=intval($_POST['max_players']??4);
        $code=$game->createRoom($username,$diff,$maxP);
        header("Location: ?action=room&code=$code"); exit;
    }
    // Handle join room
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
      <p class="text-center mb-3">Race your friends to guess the same number!</p>

      <h2>Create a Room</h2>
      <form method="POST" class="mb-3">
        <div class="form-group">
          <label>Difficulty</label>
          <select name="difficulty">
            <option value="Easy">Easy — 1 to 10, 5 attempts</option>
            <option value="Medium" selected>Medium — 1 to 100, 7 attempts</option>
            <option value="Hard">Hard — 1 to 1000, 10 attempts</option>
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
        <input type="text" name="code" placeholder="Room Code (e.g. AB12CD)" maxlength="6" style="text-transform:uppercase;font-family:'Space Mono',monospace;letter-spacing:.15em;font-size:1.1rem;text-align:center">
        <button class="btn btn-ghost" type="submit" name="join" style="width:auto;white-space:nowrap">Join →</button>
      </form>

      <div class="divider"></div>
      <div class="info-box">
        <strong>How it works:</strong><br>
        <span class="text-xs">1. Create or join a room with a 6-char code<br>
        2. Host starts the game when everyone's ready<br>
        3. All players guess the same secret number<br>
        4. Fastest correct guess wins! 🏆</span>
      </div>
    </div>
    <?php page_end();
}

// ═══════════════════════════════════════════════════════════════════════════════
// ROOM (Lobby + Active Game)
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='room'){
    requireAuth();
    [$th,$dm]=userTheme();
    $code=strtoupper($_GET['code']??'');
    $room=$game->getRoom($code);
    if(!$room){ header('Location: ?action=multiplayer'); exit; }
    // Auto-join if not in room
    if(!isset($room['players'][$username])){
        $res=$game->joinRoom($code,$username);
        if(!$res['ok']){ header('Location: ?action=multiplayer'); exit; }
        $room=$game->getRoom($code);
    }
    $isHost=$room['host']===$username;
    $status=$room['status'];
    $myData=$room['players'][$username];
    $u=$game->getUser($username);

    // Handle start
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['start_game'])){
        if($game->startRoom($code,$username)) header("Location: ?action=room&code=$code");
        else header("Location: ?action=room&code=$code&err=nostart");
        exit;
    }
    // Handle chat
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['chat_msg'])){
        $msg=trim($_POST['chat_msg']??'');
        if($msg) $game->addRoomChat($code,$username,$msg);
        header("Location: ?action=room&code=$code"); exit;
    }
    // Handle guess
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['guess'])){
        $guess=intval($_POST['guess']);
        $res=$game->roomGuess($code,$username,$guess);
        if($res['result']==='win') header("Location: ?action=room&code=$code&flash=win");
        elseif($res['result']==='lose') header("Location: ?action=room&code=$code&flash=lose");
        else header("Location: ?action=room&code=$code");
        exit;
    }
    // Handle leave
    if(isset($_GET['leave'])){
        // just redirect away, room persists
        header('Location: ?action=multiplayer'); exit;
    }

    // Score final game
    if($status==='finished'&&!isset($_SESSION['multi_scored_'.$code])){
        $myWon=$myData['won'];
        $game->updateUserStats($username,$myWon,$room['difficulty'],$myData['attempts'],$myData['finish_time']??0,0,false,true);
        if($myWon&&($u['coin_doubler_active']??false)){
            $cfg=$game->getGameConfig($room['difficulty']);
            $game->updateUser($username,['coins'=>($u['coins'])+$cfg['coins_base'],'coin_doubler_active'=>false]);
        }
        $_SESSION['multi_scored_'.$code]=true;
    }

    $room=$game->getRoom($code); // refresh
    $myData=$room['players'][$username];
    $cfg=$game->getGameConfig($room['difficulty']);

    page_start('Room '.$code,$th,$dm);
    $flash=$_GET['flash']??'';
    ?>
    <div class="card card-wide">
      <!-- Header -->
      <div class="flex-between mb-2">
        <div class="flex">
          <span class="badge badge-acc mono"><?=$room['difficulty']?></span>
          <span class="badge" style="background:color-mix(in srgb,<?=$status==='active'?'#22c55e':($status==='finished'?'#ef4444':'#f59e0b')?> 15%,transparent);border-color:color-mix(in srgb,<?=$status==='active'?'#22c55e':($status==='finished'?'#ef4444':'#f59e0b')?> 40%,transparent);color:<?=$status==='active'?'#4ade80':($status==='finished'?'#f87171':'#fbbf24')?>"><?=ucfirst($status)?></span>
        </div>
        <div class="flex">
          <a href="?action=room&code=<?=$code?>&leave=1" class="btn btn-ghost btn-sm">Leave</a>
          <?php if($status!=='finished'):?><meta http-equiv="refresh" content="4"><?php endif;?>
        </div>
      </div>

      <!-- Room code -->
      <?php if($status==='waiting'):?>
      <div class="room-code"><?=$code?></div>
      <p class="text-center text-sm text-muted mb-3">Share this code with friends to invite them</p>
      <?php endif;?>

      <!-- Flash messages -->
      <?php if($flash==='win'):?><div class="success-box mb-2 pop-in">🎉 You guessed it! You win this round!</div><?php endif;?>
      <?php if($flash==='lose'):?><div class="alert alert-err mb-2">💔 Out of attempts! Wait for others to finish.</div><?php endif;?>

      <!-- Players list -->
      <h2>Players (<?=count($room['players'])?>/<?=$room['max_players']?>)</h2>
      <div class="stacks mb-3">
        <?php
        // Sort by: won first (by finish time), then attempts used
        $sorted=$room['players'];
        uasort($sorted,function($a,$b){
            if($a['won']&&!$b['won'])return -1;
            if(!$a['won']&&$b['won'])return 1;
            if($a['won']&&$b['won'])return $a['finish_time']<=>$b['finish_time'];
            return $b['attempts']<=>$a['attempts'];
        });
        $rank=1;
        foreach($sorted as $pname=>$pd):
          $isMe=$pname===$username;
          $isDone=$pd['won']||$pd['attempts']>=$room['max_attempts'];
        ?>
        <div class="player-row <?=$pd['won']?'winner':''?> <?=$isMe?'you':''?>">
          <div style="font-size:1.3rem;width:28px;text-align:center">
            <?php if($status!=='waiting'):?>
              <?=$pd['won']?($rank===1?'🥇':($rank===2?'🥈':'🥉')):'❌'?>
            <?php else:?>
              <?=$pname===$room['host']?'👑':'👤'?>
            <?php endif;?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;color:<?=$isMe?'var(--acc2)':'var(--txt)'?>"><?=htmlspecialchars($pname)?><?=$isMe?' (you)':''?><?=$pname===$room['host']?' 👑':''?></div>
            <?php if($status!=='waiting'):?>
            <div class="text-xs text-muted">
              <?=$pd['attempts']?> attempt<?=$pd['attempts']!==1?'s':''?>
              <?php if($pd['won']):?> · ⏱ <?=$pd['finish_time']?>s<?php endif;?>
              <?php if($status==='finished'&&$isMe&&!$pd['won']):?> · Secret: <strong class="text-acc"><?=$room['secret']?></strong><?php endif;?>
            </div>
            <?php else:?>
            <div class="text-xs text-muted <?=$isDone?'':'pulse'?>">
              <?=$isDone?'Ready ✅':'Waiting...'?>
            </div>
            <?php endif;?>
          </div>
          <?php if($status!=='waiting'&&!$isDone&&!$isMe):?>
          <span class="badge badge-acc pulse">Playing</span>
          <?php endif;?>
          <?php if($rank<=3&&$pd['won']&&$status==='finished') $rank++;?>
        </div>
        <?php endforeach;?>
      </div>

      <!-- Waiting: start button -->
      <?php if($status==='waiting'&&$isHost):?>
      <form method="POST" class="mb-3">
        <button class="btn btn-primary" type="submit" name="start_game" <?=count($room['players'])<2?'disabled':''?>>
          <?=count($room['players'])<2?'Waiting for players…':'▶ Start Game!'?>
        </button>
      </form>
      <?php elseif($status==='waiting'&&!$isHost):?>
      <div class="warn-box mb-3 pulse">⏳ Waiting for host to start the game…</div>
      <?php endif;?>

      <!-- Active game: guess form -->
      <?php if($status==='active'&&!$myData['won']&&$myData['attempts']<$room['max_attempts']):
        $attLeft=$room['max_attempts']-$myData['attempts'];
        $lastGuess=$myData['guesses']?end($myData['guesses']):null;
        $dir=''; 
        if($lastGuess!==null) $dir=$lastGuess<$room['secret']?'↑ Higher':'↓ Lower';
      ?>
      <div class="info-box mb-3">
        <div class="flex-between">
          <span>Attempts left: <strong class="mono"><?=$attLeft?>/<?=$room['max_attempts']?></strong></span>
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
      <?php elseif($status==='active'&&($myData['won']||$myData['attempts']>=$room['max_attempts'])):?>
      <div class="<?=$myData['won']?'success-box':'warn-box'?> mb-3">
        <?=$myData['won']?'🎉 You guessed it! Waiting for others…':'⏳ Out of attempts. Watching others…'?>
      </div>
      <?php endif;?>

      <!-- Finished results -->
      <?php if($status==='finished'):?>
      <div class="divider"></div>
      <p class="text-center text-sm text-muted">The secret number was <strong class="text-acc mono" style="font-size:1.2rem"><?=$room['secret']?></strong></p>
      <div class="grid-2 mt-2">
        <a href="?action=multiplayer" class="btn btn-primary">New Game</a>
        <a href="?action=menu" class="btn btn-ghost">Menu</a>
      </div>
      <?php endif;?>

      <!-- Chat -->
      <div class="divider"></div>
      <h2>Room Chat</h2>
      <div class="chat-wrap">
        <div class="chat-msgs" id="chatBox">
          <?php $chat=array_slice($room['chat']??[],-20); foreach($chat as $cm):?>
          <div class="chat-msg"><span class="chat-name"><?=htmlspecialchars($cm['u'])?>:</span><?=htmlspecialchars($cm['m'])?></div>
          <?php endforeach;?>
          <?php if(empty($chat)):?><div class="text-xs text-muted">No messages yet…</div><?php endif;?>
        </div>
        <div class="chat-input-row">
          <form method="POST" class="flex" style="flex:1;gap:8px;margin:0">
            <input type="text" name="chat_msg" placeholder="Say something…" maxlength="100" style="flex:1">
            <button class="btn btn-ghost btn-sm" type="submit">Send</button>
          </form>
        </div>
      </div>
    </div>
    <script>
    const cb=document.getElementById('chatBox');
    if(cb) cb.scrollTop=cb.scrollHeight;
    </script>
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
            <div class="stat-box" style="text-align:left;padding:17px 20px;cursor:pointer;transition:border-color .2s,transform .15s"
              onmouseover="this.style.borderColor='<?=$col?>';this.style.transform='translateY(-2px)'"
              onmouseout="this.style.borderColor='var(--brd)';this.style.transform='none'">
              <div class="flex-between">
                <strong style="font-size:1.05rem"><?=$name?></strong>
                <div class="flex">
                  <span class="badge badge-acc">×<?=$cfg['xp_mult']?> XP</span>
                  <span class="badge badge-gold"><?=$cfg['coins_base']?> 🪙</span>
                  <span style="color:<?=$col?>;font-size:.85rem">●</span>
                </div>
              </div>
              <div class="text-sm text-muted mt-1">Range <?=$cfg['min']?>–<?=$cfg['max']?> · <?=$cfg['attempts']?> attempts</div>
              <div class="text-xs text-muted mt-1">Your record: <strong class="text-acc"><?=$ds['w']?>/<?=$ds['p']?> (<?=$wr?>%)</strong></div>
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
    $_SESSION['difficulty']=$difficulty; $_SESSION['min']=$config['min'];
    $_SESSION['max_range']=$config['max']; $_SESSION['cur_min']=$config['min'];
    $_SESSION['cur_max']=$config['max']; $_SESSION['attempts']=$config['attempts'];
    $_SESSION['guesses']=[]; $_SESSION['current_attempt']=0;
    $_SESSION['hints_used']=0; $_SESSION['powerup_used']=false;
    $_SESSION['start_time']=time(); $_SESSION['is_daily']=false;
    $_SESSION['extra_attempts_used']=0;
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
    $_SESSION['powerup_used']=false; $_SESSION['start_time']=time();
    $_SESSION['is_daily']=true; $_SESSION['extra_attempts_used']=0;
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
    if($type==='range_narrow'&&$game->usePowerup($username,$type)){
        $range=$curMax-$curMin; $quarter=(int)floor($range/4);
        if($secret-$quarter>$curMin)$_SESSION['cur_min']=$secret-$quarter;
        if($secret+$quarter<$curMax)$_SESSION['cur_max']=$secret+$quarter;
        $_SESSION['powerup_used']=true;
        $_SESSION['powerup_msg']="🔭 Range narrowed to {$_SESSION['cur_min']}–{$_SESSION['cur_max']}!";
    } elseif($type==='reveal_digit'&&$game->usePowerup($username,$type)){
        $digit=$secret%10; $_SESSION['powerup_used']=true;
        $_SESSION['powerup_msg']="🔮 The number ends in: <strong>$digit</strong>";
    } elseif($type==='extra_attempt'&&$game->usePowerup($username,$type)){
        $_SESSION['attempts']++; $_SESSION['extra_attempts_used']=($_SESSION['extra_attempts_used']??0)+1;
        $_SESSION['powerup_used']=true; $_SESSION['powerup_msg']="❤️ +1 Extra attempt granted!";
    } elseif($type==='freeze_timer'&&$game->usePowerup($username,$type)){
        $_SESSION['freeze_end']=time()+30; $_SESSION['powerup_used']=true;
        $_SESSION['powerup_msg']="⏸️ Timer frozen for 30 seconds!";
    } elseif($type==='double_coins'&&$game->usePowerup($username,$type)){
        $game->updateUser($username,['coin_doubler_active'=>true]);
        $_SESSION['powerup_used']=true; $_SESSION['powerup_msg']="🪙 Coin Doubler active! Next win = 2× coins!";
    } elseif($type==='hint_boost'&&$game->usePowerup($username,$type)){
        $u=$game->getUser($username);
        $game->updateUser($username,['hint_xp'=>($u['hint_xp']??0)+100]);
        $_SESSION['powerup_msg']="💡 +100 Hint XP added!";
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
    $freezeEnd=$_SESSION['freeze_end']??0;

    if($_SERVER['REQUEST_METHOD']==='POST'){
        $guess=intval($_POST['guess']??0);
        $_SESSION['guesses'][]=$guess; $_SESSION['current_attempt']++;
        $_SESSION['show_hint']=false; unset($_SESSION['powerup_msg']);
        if($guess==$secret){ header('Location: ?action=results&result=win'); exit; }
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
    $elapsed=time()-($_SESSION['start_time']??time())-($freezeEnd>time()?0:0);
    if($frozenTime>0) $elapsed-=$frozenTime;
    $elapsed=max(0,$elapsed);
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
        </div>
        <span class="text-sm text-muted mono">⏱ <span id="ts"><?=$elapsed?></span>s<?=$frozenTime>0?' ⏸️':'';?></span>
      </div>
      <div class="flex-between" style="margin-bottom:12px">
        <div><div class="text-xs text-muted">Attempts Left</div><div class="mono" style="font-size:1.8rem;font-weight:700;color:<?=$left<=2?'#f87171':'var(--acc2)'?>;line-height:1"><?=$left?>/<?=$attempts?></div></div>
        <div style="text-align:right"><div class="text-xs text-muted">Range</div><div class="mono" style="font-size:1.05rem;font-weight:700;color:var(--acc1)"><?=$curMin?> – <?=$curMax?></div></div>
      </div>
      <div class="progress-wrap mb-2">
        <div class="progress-bar" style="width:<?=round(($current/$attempts)*100)?>%;background:linear-gradient(90deg,<?=$left<=2?'#ef4444':'var(--acc1)'?>,<?=$left<=2?'#dc2626':'var(--acc2)'?>)"></div>
      </div>

      <?php if($warmth): $c=$wc[$warmth['cls']]; ?>
      <div style="background:color-mix(in srgb,<?=$c?> 12%,transparent);border:1px solid color-mix(in srgb,<?=$c?> 35%,transparent);border-radius:10px;padding:14px;margin-bottom:12px;text-align:center">
        <div style="font-size:1.05rem;font-weight:700;color:<?=$c?>"><?=$warmth['msg']?></div>
        <div class="text-sm text-muted mt-1"><?=$dirHint?></div>
      </div>
      <?php endif;?>

      <?php if($hintResult):?><div class="success-box mb-2">💡 Hint: <?=$hintResult?></div><?php endif;?>
      <?php if($powerupMsg):?><div class="success-box mb-2"><?=$powerupMsg?></div><?php endif;?>

      <?php if(!empty($guesses)):?>
      <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:12px;margin-bottom:12px">
        <div class="text-xs text-muted mb-2">Previous Guesses</div>
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
      <!-- Powerup row -->
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between">
        <div class="flex" style="gap:6px;flex-wrap:wrap">
          <?php if(($u['hint_xp']??0)>=50):?>
          <a href="?action=use_hint" class="btn btn-ghost btn-sm">💡 Hint (50 XP)</a>
          <?php else:?>
          <span class="text-xs text-muted">💡 Need 50 XP</span>
          <?php endif;?>
        </div>
        <div class="flex" style="gap:6px;flex-wrap:wrap">
          <?php
          $pu=[
            'range_narrow'=>['icon'=>'🔭','label'=>'Range↓'],
            'reveal_digit'=>['icon'=>'🔮','label'=>'Reveal'],
            'extra_attempt'=>['icon'=>'❤️','label'=>'+Life'],
            'freeze_timer'=>['icon'=>'⏸️','label'=>'Freeze'],
            'double_coins'=>['icon'=>'🪙','label'=>'2×Coins'],
          ];
          foreach($pu as $k=>$p):
            $cnt=$u['powerups'][$k]??0;
            if($cnt>0):
          ?>
          <a href="?action=use_powerup&type=<?=$k?>" class="btn btn-ghost btn-sm" title="<?=$p['label']?>"><?=$p['icon']?> ×<?=$cnt?></a>
          <?php endif; endforeach; ?>
          <a href="?action=shop" class="btn btn-ghost btn-sm">🛒</a>
        </div>
      </div>
    </div>
    <script>
    let frozen=<?=$frozenTime?>,s=<?=$elapsed?>;
    const el=document.getElementById('ts');
    const iv=setInterval(()=>{
      if(frozen>0){frozen--;return;}
      el.textContent=++s;
    },1000);
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
    $timeSecs=time()-($_SESSION['start_time']??time());
    $hintsUsed=$_SESSION['hints_used']??0; $puUsed=$_SESSION['powerup_used']??false;
    $isDaily=$_SESSION['is_daily']??false; $won=$result==='win';

    $uBefore=$game->getUser($username);
    $stats=$game->updateUserStats($username,$won,$difficulty,$attUsed,$timeSecs,$hintsUsed,$puUsed);
    if($isDaily&&$won){
        $game->updateUser($username,['last_daily'=>date('Y-m-d'),'daily_streak'=>($uBefore['daily_streak']??0)+1]);
        $a2=$uBefore['achievements']??[];
        if(!in_array('daily_champion',$a2))$game->updateUser($username,['achievements'=>array_merge($a2,['daily_champion'])]);
    }
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
      <p class="mb-2"><?=$won?"Cracked it in <strong>{$attUsed}</strong> attempt".($attUsed!==1?'s':'')." · {$timeSecs}s":"The number was <strong style='color:var(--acc2);font-family:Space Mono,monospace'>{$secret}</strong>"?></p>

      <div style="background:color-mix(in srgb,<?=$wc?> 8%,transparent);border:1px solid color-mix(in srgb,<?=$wc?> 25%,transparent);border-radius:14px;padding:20px;margin-bottom:18px">
        <div class="flex" style="justify-content:center;gap:20px">
          <div><div class="mono" style="font-size:2rem;font-weight:700;color:<?=$wc?>">+<?=$xpEarned?></div><div class="text-xs text-muted">XP</div></div>
          <div><div class="mono" style="font-size:2rem;font-weight:700;color:#fbbf24">+<?=$coinsEarned?></div><div class="text-xs text-muted">Coins 🪙</div></div>
        </div>
        <div class="text-sm text-muted mt-2"><?=htmlspecialchars($difficulty)?><?=$isDaily?' · Daily':''?><?=$hintsUsed?" · {$hintsUsed} hint(s)":''?></div>
        <?php if($u['streak']>=2):?><div class="text-sm mt-1" style="color:#f97316">🔥 <?=$u['streak']?>-win streak!</div><?php endif;?>
      </div>

      <?php if(!empty($newAch)):?>
      <div style="margin-bottom:16px">
        <div class="text-xs text-muted mb-2">🏅 New Achievements!</div>
        <?php foreach($newAch as $k): $a=$achs[$k]??['name'=>$k,'icon'=>'🏅','desc'=>''];?>
        <div style="background:color-mix(in srgb,var(--acc1) 10%,transparent);border:1px solid color-mix(in srgb,var(--acc1) 25%,transparent);border-radius:10px;padding:12px;margin-top:8px;text-align:left;display:flex;align-items:center;gap:12px">
          <span style="font-size:1.8rem"><?=$a['icon']?></span>
          <div><strong><?=$a['name']?></strong><div class="text-xs text-muted"><?=$a['desc']?></div></div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>

      <div class="text-xs text-muted mb-1">Level <?=$lvl?> Progress</div>
      <div class="progress-wrap mb-2"><div class="progress-bar" style="width:<?=$pct?>%"></div></div>

      <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:13px;margin-bottom:18px;text-align:left">
        <div class="text-xs text-muted mb-2">Your Guesses</div>
        <div style="display:flex;flex-wrap:wrap;gap:7px">
          <?php foreach($guesses as $i=>$g):
            $col=($i===count($guesses)-1&&$won)?'#22c55e':'var(--sub)';
          ?>
          <span class="mono" style="background:var(--brd);color:<?=$col?>;padding:5px 12px;border-radius:999px;font-size:.88rem"><?=$g?></span>
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
            <div style="font-weight:700;color:<?=$isMe?'var(--acc2)':'var(--txt)'?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($data['username'])?><?=$isMe?' (you)':''?></div>
            <div class="text-xs text-muted">Lv <?=$lvl?> · <?=$data['experience']?> XP · <?=$data['games_won']?> wins · <?=$wr?>% WR<?=($data['best_streak']??0)>=3?' · 🔥'.($data['best_streak']).' streak':''?><?=($data['multi_wins']??0)>0?' · ⚔️'.($data['multi_wins']).' multi':''?></div>
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
// ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='achievements'){
    requireAuth();
    [$th,$dm]=userTheme();
    $u=$game->getUser($username); $earned=$u['achievements']??[]; $achs=$game->getAchievements();
    page_start('Achievements',$th,$dm);
    ?>
    <div class="card card-wide">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🎖 Achievements</h1>
      <p class="text-muted text-sm mb-2"><?=count($earned)?> / <?=count($achs)?> unlocked</p>
      <div class="stacks">
        <?php foreach($achs as $key=>$a): $got=in_array($key,$earned);?>
        <div style="background:<?=$got?'color-mix(in srgb,var(--acc1) 8%,transparent)':'var(--card)'?>;border:1.5px solid <?=$got?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:13px 16px;display:flex;align-items:center;gap:12px;opacity:<?=$got?'1':'.38'?>;transition:opacity .2s">
          <div style="font-size:1.7rem;flex-shrink:0"><?=$a['icon']?></div>
          <div style="flex:1"><div style="font-weight:700;color:<?=$got?'var(--acc2)':'var(--txt)'?>"><?=$a['name']?></div><div class="text-xs text-muted"><?=$a['desc']?></div></div>
          <?php if($got):?><div style="color:#4ade80;font-size:1.2rem;flex-shrink:0">✅</div><?php endif;?>
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
      </div>
      <h2>Per Difficulty</h2>
      <div class="stacks mb-2">
        <?php foreach(['Easy'=>'#4ade80','Medium'=>'#fbbf24','Hard'=>'#f87171'] as $d=>$col):
          $ds=$u['diff_stats'][$d]??['w'=>0,'p'=>0];
          $wr=$ds['p']>0?round($ds['w']/$ds['p']*100):0;
        ?>
        <div class="stat-box flex-between" style="text-align:left;padding:12px 16px">
          <span style="font-weight:700;color:<?=$col?>"><?=$d?></span>
          <span class="text-sm text-muted"><?=$ds['w']?>/<?=$ds['p']?> wins · <strong class="text-acc"><?=$wr?>%</strong></span>
        </div>
        <?php endforeach;?>
      </div>
      <h2>Recent Games</h2>
      <?php if(empty($history)):?>
      <p class="text-muted text-sm">No games yet — go play!</p>
      <?php else:?>
      <div class="stacks">
        <?php foreach(array_slice($history,0,10) as $h):?>
        <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:10px">
          <span><?=$h['won']?'✅':'❌'?> <strong><?=$h['diff']?></strong><?=($h['multi']??false)?' ⚔️':''?></span>
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
        if(isset($themes[$t])&&$lvl0>=$themes[$t]['unlock_level'])
            $game->updateUser($username,['theme'=>$t]);
        header('Location: ?action=themes'); exit;
    }
    [$th,$dm]=userTheme();
    $u=$game->getUser($username); [$lvl]=levelProgress($u['experience']);
    page_start('Themes',$th,$dm);
    ?>
    <div class="card">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🎨 Themes</h1>
      <p class="text-muted text-sm mb-2">You are Level <?=$lvl?> — unlock more by levelling up</p>
      <form method="POST">
        <div class="stacks">
          <?php foreach($themes as $key=>$t):
            $unlocked=$lvl>=$t['unlock_level']; $active=($u['theme']??'default')===$key;
          ?>
          <div style="background:<?=$active?'color-mix(in srgb,var(--acc1) 10%,transparent)':'var(--card)'?>;border:1.5px solid <?=$active?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:14px 17px;display:flex;align-items:center;justify-content:space-between;gap:12px;opacity:<?=$unlocked?'1':'.4'?>;transition:border-color .2s,opacity .2s">
            <div class="flex">
              <span style="font-size:1.7rem"><?=$t['icon']?></span>
              <div><div style="font-weight:700"><?=$t['name']?></div><div class="text-xs text-muted">Unlocks at Level <?=$t['unlock_level']?></div></div>
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
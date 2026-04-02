<?php
session_start();

// ═══════════════════════════════════════════════════════════════════════════════
// GAME CLASS
// ═══════════════════════════════════════════════════════════════════════════════
class NumberGuessingGame {
    private $games = [
        'Easy'   => ['min'=>1,'max'=>10,  'attempts'=>5, 'xp_base'=>100,'xp_mult'=>1.0],
        'Medium' => ['min'=>1,'max'=>100, 'attempts'=>7, 'xp_base'=>200,'xp_mult'=>1.5],
        'Hard'   => ['min'=>1,'max'=>1000,'attempts'=>10,'xp_base'=>300,'xp_mult'=>2.5],
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
    ];
    private $themes = [
        'default'=>['name'=>'Neon Noir',     'unlock_level'=>1, 'icon'=>'🌃'],
        'ocean'  =>['name'=>'Deep Ocean',    'unlock_level'=>5, 'icon'=>'🌊'],
        'forest' =>['name'=>'Ancient Forest','unlock_level'=>10,'icon'=>'🌲'],
        'volcano'=>['name'=>'Volcano',       'unlock_level'=>20,'icon'=>'🌋'],
        'galaxy' =>['name'=>'Galaxy',        'unlock_level'=>35,'icon'=>'🌌'],
        'gold'   =>['name'=>'Gold Rush',     'unlock_level'=>50,'icon'=>'✨'],
    ];
    private $usersFile = __DIR__.'/ng_users.json';
    private $dailyFile = __DIR__.'/ng_daily.json';

    public function getDifficulties(){ return $this->games; }
    public function getGameConfig($d){ return $this->games[$d]??null; }
    public function getAchievements(){ return $this->achievements; }
    public function getThemes(){ return $this->themes; }
    public function generateNumber($mn,$mx){ return rand($mn,$mx); }

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
            'powerups'=>['range_narrow'=>2,'reveal_digit'=>1],'game_history'=>[],'no_hint_games'=>0,
            'created_at'=>time(),'last_daily'=>null,'daily_streak'=>0,
            'diff_stats'=>['Easy'=>['w'=>0,'p'=>0],'Medium'=>['w'=>0,'p'=>0],'Hard'=>['w'=>0,'p'=>0]]
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
    public function updateUserStats($username,$won,$difficulty,$attemptsUsed,$timeSecs,$hintsUsed,$usedPowerup){
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
            if($hintsUsed===0)$u['no_hint_games']++;
            else{$u['no_hint_games']=0;$u['hints_used']+=$hintsUsed;}
            if($usedPowerup&&!in_array('power_player',$u['achievements'])){$u['achievements'][]='power_player';$newAchievements[]='power_player';}
            $checks=['first_win'=>$u['games_won']===1,'win_streak_3'=>$u['streak']>=3,'win_streak_5'=>$u['streak']>=5,
                'win_streak_10'=>$u['streak']>=10,'one_shot'=>$attemptsUsed===1,'hard_win'=>$difficulty==='Hard',
                'speed_demon'=>$timeSecs<10,'win_50'=>$u['games_won']>=50,'efficiency'=>$attemptsUsed<=(int)ceil($cfg['attempts']/2)];
            foreach($checks as $key=>$cond)
                if($cond&&!in_array($key,$u['achievements'])){$u['achievements'][]=$key;$newAchievements[]=$key;$xp+=100;}
            $u['experience']+=$xp;
        } else { $u['streak']=0;$u['no_hint_games']=0;$xp=10;$u['experience']+=$xp; }
        $u['level']=min(1000,(int)floor($u['experience']/50)+1);
        foreach(['level_10'=>10,'level_50'=>50] as $key=>$lvl)
            if($u['level']>=$lvl&&!in_array($key,$u['achievements'])){$u['achievements'][]=$key;$newAchievements[]=$key;}
        if($u['games_played']>=100&&!in_array('centurion',$u['achievements'])){$u['achievements'][]='centurion';$newAchievements[]='centurion';}
        if($u['no_hint_games']>=10&&!in_array('hint_hoarder',$u['achievements'])){$u['achievements'][]='hint_hoarder';$newAchievements[]='hint_hoarder';}
        $u['game_history'][]=['diff'=>$difficulty,'won'=>$won,'attempts'=>$attemptsUsed,'time'=>$timeSecs,'date'=>time()];
        if(count($u['game_history'])>50)array_shift($u['game_history']);
        $u['powerups']['range_narrow']=min(5,($u['powerups']['range_narrow']??0)+($won?1:0));
        if($won&&$difficulty==='Hard')$u['powerups']['reveal_digit']=min(3,($u['powerups']['reveal_digit']??0)+1);
        $this->saveUsers($users);
        return['xp'=>$xp??10,'new_achievements'=>$newAchievements];
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
            'light'=>'--bg:#0d0d1a;--panel:#161629;--acc1:#7c6af7;--acc2:#a78bfa;--txt:#e2e8f0;--sub:#94a3b8;--brd:#2d2d50;--card:#1e1e3a',
            'dark' =>'--bg:#0d0d1a;--panel:#161629;--acc1:#7c6af7;--acc2:#a78bfa;--txt:#e2e8f0;--sub:#94a3b8;--brd:#2d2d50;--card:#1e1e3a',
        ],
        'ocean'=>[
            'light'=>'--bg:#e8f4f8;--panel:#ffffff;--acc1:#0077b6;--acc2:#00b4d8;--txt:#03045e;--sub:#023e8a;--brd:#caf0f8;--card:#f0f8ff',
            'dark' =>'--bg:#03045e;--panel:#023e8a;--acc1:#00b4d8;--acc2:#90e0ef;--txt:#caf0f8;--sub:#90e0ef;--brd:#0077b6;--card:#0077b6',
        ],
        'forest'=>[
            'light'=>'--bg:#f0fdf4;--panel:#ffffff;--acc1:#16a34a;--acc2:#4ade80;--txt:#14532d;--sub:#166534;--brd:#bbf7d0;--card:#f0fdf4',
            'dark' =>'--bg:#052e16;--panel:#14532d;--acc1:#4ade80;--acc2:#86efac;--txt:#dcfce7;--sub:#bbf7d0;--brd:#166534;--card:#166534',
        ],
        'volcano'=>[
            'light'=>'--bg:#fff7ed;--panel:#ffffff;--acc1:#ea580c;--acc2:#fb923c;--txt:#431407;--sub:#7c2d12;--brd:#fed7aa;--card:#fff7ed',
            'dark' =>'--bg:#1c0a00;--panel:#431407;--acc1:#fb923c;--acc2:#fdba74;--txt:#ffedd5;--sub:#fed7aa;--brd:#7c2d12;--card:#7c2d12',
        ],
        'galaxy'=>[
            'light'=>'--bg:#f5f3ff;--panel:#ffffff;--acc1:#7c3aed;--acc2:#a78bfa;--txt:#2e1065;--sub:#4c1d95;--brd:#ddd6fe;--card:#f5f3ff',
            'dark' =>'--bg:#0d0617;--panel:#1e0a3c;--acc1:#a78bfa;--acc2:#c4b5fd;--txt:#ede9fe;--sub:#c4b5fd;--brd:#4c1d95;--card:#2e1065',
        ],
        'gold'=>[
            'light'=>'--bg:#fefce8;--panel:#ffffff;--acc1:#ca8a04;--acc2:#eab308;--txt:#422006;--sub:#713f12;--brd:#fef08a;--card:#fefce8',
            'dark' =>'--bg:#1c0f00;--panel:#422006;--acc1:#eab308;--acc2:#facc15;--txt:#fefce8;--sub:#fef08a;--brd:#713f12;--card:#713f12',
        ],
    ];
    $t=$themes[$theme]??$themes['default'];
    return $dark?$t['dark']:$t['light'];
}

// ═══════════════════════════════════════════════════════════════════════════════
// PAGE SHELL — every page uses this for a valid HTML document
// ═══════════════════════════════════════════════════════════════════════════════
function page_start($title='NumGenius',$theme='default',$dark=true){
    $vars=themeVars($theme,$dark);
    $esc=htmlspecialchars($title);
    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,viewport-fit=cover\">
<meta name=\"theme-color\" content=\"#0d0d1a\">
<title>{$esc} — NumGenius</title>
<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
<link href=\"https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;600;700;800&display=swap\" rel=\"stylesheet\">
<style>
:root { {$vars} }
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%;-webkit-text-size-adjust:100%}
body{
  font-family:'DM Sans',system-ui,sans-serif;
  font-size:1rem;line-height:1.6;
  background:var(--bg);color:var(--txt);
  min-height:100vh;min-height:100dvh;
  display:grid;place-items:center;
  padding:clamp(14px,4vw,40px);
  padding-top:max(clamp(14px,4vw,40px),env(safe-area-inset-top));
  padding-bottom:max(clamp(14px,4vw,40px),env(safe-area-inset-bottom));
  position:relative;overflow-x:hidden;
}
body::before{
  content:'';position:fixed;inset:0;pointer-events:none;opacity:.04;z-index:0;
  background-image:url(\"data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\");
}
.card{
  position:relative;z-index:1;
  background:var(--panel);border:1px solid var(--brd);
  border-radius:clamp(14px,2vw,22px);
  padding:clamp(22px,5vw,48px);
  width:100%;max-width:500px;
  box-shadow:0 8px 16px rgba(0,0,0,.12),0 32px 64px rgba(0,0,0,.35);
  animation:slideUp .4s cubic-bezier(.22,1,.36,1) both;
}
.card-wide{max-width:620px}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
h1{
  font-family:'DM Mono',monospace;font-weight:500;
  font-size:clamp(1.5rem,5vw,2.2rem);
  letter-spacing:-.04em;line-height:1.15;text-align:center;
  margin-bottom:clamp(18px,4vw,30px);
  background:linear-gradient(135deg,var(--acc1),var(--acc2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
h2{font-size:clamp(.95rem,3vw,1.15rem);font-weight:700;color:var(--acc2);margin-bottom:12px}
p{color:var(--sub)}
.form-group{margin-bottom:clamp(13px,3vw,20px)}
label{display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--sub);margin-bottom:6px}
input,select{
  width:100%;min-height:48px;padding:11px 15px;
  background:var(--card);border:1.5px solid var(--brd);border-radius:10px;
  color:var(--txt);font-size:1rem;font-family:inherit;
  outline:none;transition:border-color .2s,box-shadow .2s;
  -webkit-appearance:none;appearance:none;
}
@media(max-width:480px){input,select{font-size:16px}}
input:focus,select:focus{border-color:var(--acc1);box-shadow:0 0 0 3px color-mix(in srgb,var(--acc1) 18%,transparent)}
input[type=number]{font-family:'DM Mono',monospace;font-size:1.25rem;text-align:center;font-weight:500}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  width:100%;min-height:48px;padding:12px 20px;
  border:none;border-radius:10px;cursor:pointer;
  font-size:.95rem;font-family:inherit;font-weight:700;letter-spacing:.02em;
  text-decoration:none;text-align:center;
  transition:transform .15s,box-shadow .15s;
  -webkit-tap-highlight-color:transparent;touch-action:manipulation;user-select:none;white-space:nowrap;
}
.btn:active{transform:scale(.96)}
.btn-primary{background:linear-gradient(135deg,var(--acc1),var(--acc2));color:#fff;box-shadow:0 4px 14px color-mix(in srgb,var(--acc1) 35%,transparent)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px color-mix(in srgb,var(--acc1) 45%,transparent)}
.btn-ghost{background:var(--card);color:var(--txt);border:1.5px solid var(--brd)}
.btn-ghost:hover{border-color:var(--acc1);color:var(--acc1)}
.btn-sm{min-height:38px;padding:7px 15px;font-size:.82rem;width:auto}
.btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
.grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:clamp(8px,2vw,14px)}
@media(max-width:340px){.grid-2{grid-template-columns:1fr}}
.stat-box{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:clamp(11px,2.5vw,20px) 8px;text-align:center}
.stat-val{font-family:'DM Mono',monospace;font-size:clamp(1.3rem,4vw,1.9rem);font-weight:500;background:linear-gradient(135deg,var(--acc1),var(--acc2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2}
.stat-lbl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--sub);margin-top:4px}
.flex{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.flex-between{display:flex;align-items:center;justify-content:space-between;gap:8px}
.stacks{display:flex;flex-direction:column;gap:clamp(9px,2vw,13px)}
.divider{height:1px;background:var(--brd);margin:clamp(14px,3vw,22px) 0}
.link{color:var(--acc2);text-decoration:none;font-weight:600}.link:hover{text-decoration:underline}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:700;border:1px solid;white-space:nowrap}
.badge-acc{background:color-mix(in srgb,var(--acc1) 15%,transparent);border-color:color-mix(in srgb,var(--acc1) 40%,transparent);color:var(--acc2)}
.alert{padding:12px 15px;border-radius:10px;font-size:.88rem;margin-top:13px;border-left:3px solid}
.alert-err{background:color-mix(in srgb,#ef4444 12%,transparent);border-color:#ef4444;color:#fca5a5}
.alert-ok{background:color-mix(in srgb,#22c55e 12%,transparent);border-color:#22c55e;color:#86efac}
.progress-wrap{background:var(--brd);border-radius:999px;height:8px;overflow:hidden;margin:8px 0}
.progress-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--acc1),var(--acc2));transition:width .7s cubic-bezier(.22,1,.36,1)}
.warn-box{background:color-mix(in srgb,#f59e0b 10%,transparent);border:1px solid color-mix(in srgb,#f59e0b 40%,transparent);border-radius:10px;padding:13px;color:#fbbf24;font-size:.9rem}
.success-box{background:color-mix(in srgb,#22c55e 10%,transparent);border:1px solid color-mix(in srgb,#22c55e 40%,transparent);border-radius:10px;padding:13px;color:#86efac;font-size:.9rem}
.text-sm{font-size:.875rem}.text-xs{font-size:.74rem}.text-muted{color:var(--sub)}.text-acc{color:var(--acc2)}.text-center{text-align:center}
.mono{font-family:'DM Mono',monospace}
.mt-1{margin-top:8px}.mt-2{margin-top:14px}.mt-3{margin-top:22px}
.mb-1{margin-bottom:8px}.mb-2{margin-bottom:14px}
.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Landscape phone */
@media(max-height:480px) and (orientation:landscape){
  body{padding-top:10px;padding-bottom:10px}
  .card{padding:14px 22px}
  h1{margin-bottom:10px}
  .stacks{gap:7px}
}
/* Desktop breathing room */
@media(min-width:900px){ body{padding:48px} .card{padding:52px} }
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
            header('Location: ?action=menu'); exit;
        } else $err='Invalid credentials. Try again.';
    }
    page_start('Login');
    ?>
    <div class="card">
      <h1>🎯 NumGenius</h1>
      <p class="text-center mb-2">The number guessing game that rewards skill</p>
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
          <span class="text-sm" style="font-weight:600"><?=htmlspecialchars($username)?></span>
        </div>
        <div class="flex">
          <a href="?action=toggle_dark" class="btn btn-ghost btn-sm"><?=$dm?'☀️ Light':'🌙 Dark'?></a>
          <a href="?action=logout" class="btn btn-ghost btn-sm">Logout</a>
        </div>
      </div>
      <div style="margin-bottom:18px">
        <div class="flex-between text-xs text-muted mb-1">
          <span><?=$u['experience']?> XP</span><span><?=$xpLeft?> XP to Lv <?=$lvl+1?></span>
        </div>
        <div class="progress-wrap"><div class="progress-bar" style="width:<?=$pct?>%"></div></div>
      </div>
      <div class="grid-2 mb-2">
        <div class="stat-box"><div class="stat-val"><?=$u['games_won']?></div><div class="stat-lbl">Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['streak']?><?=$u['streak']>=3?' 🔥':''?></div><div class="stat-lbl">Streak</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']?></div><div class="stat-lbl">Played</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']>0?round($u['games_won']/$u['games_played']*100):0?>%</div><div class="stat-lbl">Win Rate</div></div>
      </div>
      <?php if(!$dailyDone):?>
      <a href="?action=daily" style="display:block;text-decoration:none;margin-bottom:12px">
        <div class="warn-box" style="cursor:pointer">📅 <strong>Daily Challenge available!</strong> — Guess 1–500 in 8 tries<br><span class="text-xs" style="opacity:.8">Bonus XP + achievement</span></div>
      </a>
      <?php else:?>
      <div class="success-box mb-2">✅ Daily challenge done! Come back tomorrow.</div>
      <?php endif;?>
      <div class="stacks">
        <a href="?action=select" class="btn btn-primary" style="font-size:1rem">▶&nbsp;&nbsp;Play Game</a>
        <div class="grid-2">
          <a href="?action=rankings" class="btn btn-ghost">🏆 Rankings</a>
          <a href="?action=achievements" class="btn btn-ghost">🎖 Achievements&nbsp;<span class="badge badge-acc"><?=count($earned)?>/<?=count($achs)?></span></a>
        </div>
        <div class="grid-2">
          <a href="?action=stats" class="btn btn-ghost">📊 My Stats</a>
          <a href="?action=themes" class="btn btn-ghost">🎨 Themes</a>
        </div>
      </div>
      <div class="divider"></div>
      <div class="flex text-xs text-muted" style="justify-content:center;gap:18px;flex-wrap:wrap">
        <span>💰 Hint XP: <strong class="text-acc"><?=$u['hint_xp']??0?></strong></span>
        <span>🔭 Range↓: <strong class="text-acc"><?=$u['powerups']['range_narrow']??0?></strong></span>
        <span>🔮 Reveal: <strong class="text-acc"><?=$u['powerups']['reveal_digit']??0?></strong></span>
      </div>
    </div>
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
            $cols=['Easy'=>'#22c55e','Medium'=>'#f59e0b','Hard'=>'#ef4444'];
            $col=$cols[$name]??'var(--acc1)';
          ?>
          <label style="cursor:pointer;display:block">
            <input type="radio" name="difficulty" value="<?=$name?>" required style="display:none" onchange="this.form.submit()">
            <div class="stat-box" style="text-align:left;padding:17px 20px;cursor:pointer;transition:border-color .2s,transform .15s"
              onmouseover="this.style.borderColor='<?=$col?>';this.style.transform='translateY(-2px)'"
              onmouseout="this.style.borderColor='var(--brd)';this.style.transform='none'">
              <div class="flex-between">
                <strong style="font-size:1.05rem"><?=$name?></strong>
                <div class="flex"><span class="badge badge-acc">×<?=$cfg['xp_mult']?> XP</span><span style="color:<?=$col?>;font-size:.85rem">●</span></div>
              </div>
              <div class="text-sm text-muted mt-1">Range <?=$cfg['min']?>–<?=$cfg['max']?> &nbsp;·&nbsp; <?=$cfg['attempts']?> attempts &nbsp;·&nbsp; <?=$cfg['xp_base']?> base XP</div>
              <div class="text-xs text-muted mt-1">Your record: <strong class="text-acc"><?=$ds['w']?>/<?=$ds['p']?> wins (<?=$wr?>%)</strong></div>
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
    $_SESSION['is_daily']=true;
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
    $elapsed=time()-($_SESSION['start_time']??time()); $left=$attempts-$current;
    $wc=['exact'=>'#22c55e','blazing'=>'#ef4444','hot'=>'#f97316','warm'=>'#f59e0b','lukewarm'=>'#eab308','tepid'=>'#84cc16','cold'=>'#38bdf8','freezing'=>'#818cf8'];

    page_start('Game',$th,$dm);
    ?>
    <div class="card">
      <div class="flex-between mb-2">
        <div class="flex">
          <span class="badge badge-acc"><?=htmlspecialchars($difficulty)?></span>
          <?php if($isDaily):?><span class="badge" style="background:color-mix(in srgb,#f59e0b 15%,transparent);border-color:color-mix(in srgb,#f59e0b 40%,transparent);color:#fbbf24">📅 Daily</span><?php endif;?>
        </div>
        <span class="text-sm text-muted mono">⏱ <span id="ts"><?=$elapsed?></span>s</span>
      </div>
      <div class="flex-between" style="margin-bottom:14px">
        <div>
          <div class="text-xs text-muted">Attempts Left</div>
          <div class="mono" style="font-size:1.8rem;font-weight:500;color:<?=$left<=2?'#ef4444':'var(--acc2)'?>;line-height:1"><?=$left?>/<?=$attempts?></div>
        </div>
        <div style="text-align:right">
          <div class="text-xs text-muted">Range</div>
          <div class="mono" style="font-size:1.05rem;font-weight:500;color:var(--acc1)"><?=$curMin?> – <?=$curMax?></div>
        </div>
      </div>
      <div class="progress-wrap mb-2">
        <div class="progress-bar" style="width:<?=round(($current/$attempts)*100)?>%;background:linear-gradient(90deg,<?=$left<=2?'#ef4444':'var(--acc1)'?>,<?=$left<=2?'#dc2626':'var(--acc2)'?>)"></div>
      </div>

      <?php if($warmth): $c=$wc[$warmth['cls']]; ?>
      <div style="background:color-mix(in srgb,<?=$c?> 12%,transparent);border:1px solid color-mix(in srgb,<?=$c?> 35%,transparent);border-radius:10px;padding:14px;margin-bottom:13px;text-align:center">
        <div style="font-size:1.1rem;font-weight:700;color:<?=$c?>"><?=$warmth['msg']?></div>
        <div class="text-sm text-muted mt-1"><?=$dirHint?></div>
      </div>
      <?php endif;?>

      <?php if($hintResult):?><div class="success-box mb-2">💡 Hint: <?=$hintResult?></div><?php endif;?>
      <?php if($powerupMsg):?><div class="success-box mb-2"><?=$powerupMsg?></div><?php endif;?>

      <?php if(!empty($guesses)):?>
      <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:13px;margin-bottom:14px">
        <div class="text-xs text-muted mb-2">Previous Guesses</div>
        <div style="display:flex;flex-wrap:wrap;gap:7px">
          <?php foreach($guesses as $g): $w=$game->getWarmthHint($g,$secret,$maxR-$minR); $c=$wc[$w['cls']]; ?>
          <span class="mono" style="background:color-mix(in srgb,<?=$c?> 14%,transparent);border:1px solid color-mix(in srgb,<?=$c?> 35%,transparent);color:<?=$c?>;padding:5px 13px;border-radius:999px;font-size:.88rem"><?=$g?></span>
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
      <div class="flex-between" style="flex-wrap:wrap;gap:8px">
        <div>
          <?php if(($u['hint_xp']??0)>=50):?>
          <a href="?action=use_hint" class="btn btn-ghost btn-sm">💡 Hint (50 XP)</a>
          <?php else:?>
          <span class="text-xs text-muted">💡 Need 50 XP for hint</span>
          <?php endif;?>
        </div>
        <div class="flex">
          <?php if(($u['powerups']['range_narrow']??0)>0):?>
          <a href="?action=use_powerup&type=range_narrow" class="btn btn-ghost btn-sm">🔭 ×<?=$u['powerups']['range_narrow']?></a>
          <?php endif;?>
          <?php if(($u['powerups']['reveal_digit']??0)>0):?>
          <a href="?action=use_powerup&type=reveal_digit" class="btn btn-ghost btn-sm">🔮 ×<?=$u['powerups']['reveal_digit']?></a>
          <?php endif;?>
        </div>
      </div>
    </div>
    <script>
    let s=<?=$elapsed?>;const el=document.getElementById('ts');
    setInterval(()=>{el.textContent=++s;},1000);
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

    $u=$game->getUser($username); $xpEarned=$stats['xp']??0;
    $newAch=$stats['new_achievements']??[]; $achs=$game->getAchievements();
    [$lvl,$pct]=levelProgress($u['experience']);
    $wc=$won?'#22c55e':'#ef4444';

    page_start('Results',$th,$dm);
    ?>
    <div class="card" style="text-align:center">
      <div style="font-size:3.5rem;margin-bottom:12px;animation:pop .5s cubic-bezier(.22,1,.36,1)"><?=$won?'🎉':'💔'?></div>
      <h1 style="margin-bottom:8px"><?=$won?'You Won!':'Game Over'?></h1>
      <p class="mb-2"><?=$won?"Cracked it in <strong>{$attUsed}</strong> attempt".($attUsed!==1?'s':'')." — {$timeSecs}s":"The number was <strong style='color:var(--acc2)'>{$secret}</strong>"?></p>
      <div style="background:color-mix(in srgb,<?=$wc?> 8%,transparent);border:1px solid color-mix(in srgb,<?=$wc?> 25%,transparent);border-radius:14px;padding:20px;margin-bottom:18px">
        <div class="mono" style="font-size:2.2rem;font-weight:500;color:<?=$wc?>">+<?=$xpEarned?> XP</div>
        <div class="text-sm text-muted mt-1"><?=htmlspecialchars($difficulty)?><?=$isDaily?' · Daily':''?><?=$hintsUsed?" · {$hintsUsed} hint(s)":''?></div>
        <?php if($u['streak']>=2):?><div class="text-sm mt-1" style="color:#f97316">🔥 <?=$u['streak']?>-win streak!</div><?php endif;?>
      </div>
      <?php if(!empty($newAch)):?>
      <div style="margin-bottom:16px">
        <div class="text-xs text-muted mb-2">🏅 New Achievements Unlocked!</div>
        <?php foreach($newAch as $k): $a=$achs[$k]??['name'=>$k,'icon'=>'🏅'];?>
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
          <span class="mono" style="background:var(--brd);color:<?=$col?>;padding:5px 13px;border-radius:999px;font-size:.88rem"><?=$g?></span>
          <?php endforeach;?>
        </div>
      </div>
      <div class="stacks">
        <a href="?action=select" class="btn btn-primary">▶ Play Again</a>
        <a href="?action=menu" class="btn btn-ghost">← Menu</a>
      </div>
    </div>
    <style>@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}</style>
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
        <div style="background:<?=$isMe?'color-mix(in srgb,var(--acc1) 10%,transparent)':'var(--card)'?>;border:1.5px solid <?=$isMe?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:15px 18px;display:flex;align-items:center;gap:13px">
          <div style="font-size:1.4rem;width:34px;text-align:center;flex-shrink:0"><?=$medal?></div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;color:<?=$isMe?'var(--acc2)':'var(--txt)'?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($data['username'])?><?=$isMe?' (you)':''?></div>
            <div class="text-xs text-muted">Lv <?=$lvl?> · <?=$data['experience']?> XP · <?=$data['games_won']?> wins · <?=$wr?>% WR<?=$data['best_streak']>=3?' · 🔥 '.$data['best_streak'].' streak':''?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div class="text-xs text-muted">Level</div>
            <div class="mono" style="font-size:1.4rem;color:var(--acc2);font-weight:500"><?=$lvl?></div>
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
        <div style="background:<?=$got?'color-mix(in srgb,var(--acc1) 8%,transparent)':'var(--card)'?>;border:1.5px solid <?=$got?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:14px 17px;display:flex;align-items:center;gap:13px;opacity:<?=$got?'1':'.42'?>">
          <div style="font-size:1.8rem;flex-shrink:0"><?=$a['icon']?></div>
          <div style="flex:1"><div style="font-weight:700;color:<?=$got?'var(--acc2)':'var(--txt)'?>"><?=$a['name']?></div><div class="text-xs text-muted"><?=$a['desc']?></div></div>
          <?php if($got):?><div style="color:#22c55e;font-size:1.3rem;flex-shrink:0">✅</div><?php endif;?>
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
        <div class="stat-box"><div class="stat-val"><?=$u['games_won']?></div><div class="stat-lbl">Wins</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['games_played']?></div><div class="stat-lbl">Played</div></div>
        <div class="stat-box"><div class="stat-val"><?=$u['best_streak']?><?=$u['best_streak']>=3?' 🔥':''?></div><div class="stat-lbl">Best Streak</div></div>
        <div class="stat-box"><div class="stat-val"><?=$avgTime?>s</div><div class="stat-lbl">Avg Win Time</div></div>
      </div>
      <h2>Per Difficulty</h2>
      <div class="stacks mb-2">
        <?php foreach(['Easy','Medium','Hard'] as $d):
          $ds=$u['diff_stats'][$d]??['w'=>0,'p'=>0];
          $wr=$ds['p']>0?round($ds['w']/$ds['p']*100):0;
          $cols=['Easy'=>'#22c55e','Medium'=>'#f59e0b','Hard'=>'#ef4444'];
        ?>
        <div class="stat-box flex-between" style="text-align:left;padding:13px 17px">
          <span style="font-weight:700;color:<?=$cols[$d]?>"><?=$d?></span>
          <span class="text-sm text-muted"><?=$ds['w']?>/<?=$ds['p']?> wins &nbsp;·&nbsp; <strong class="text-acc"><?=$wr?>%</strong></span>
        </div>
        <?php endforeach;?>
      </div>
      <h2>Recent Games</h2>
      <?php if(empty($history)):?>
      <p class="text-muted text-sm">No games yet — go play!</p>
      <?php else:?>
      <div class="stacks">
        <?php foreach(array_slice($history,0,10) as $h):?>
        <div style="background:var(--card);border:1px solid var(--brd);border-radius:10px;padding:11px 15px;display:flex;justify-content:space-between;align-items:center;gap:10px">
          <span><?=$h['won']?'✅':'❌'?> <strong><?=$h['diff']?></strong></span>
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
// THEMES  — POST is handled FIRST so the new theme is active when we render
// ═══════════════════════════════════════════════════════════════════════════════
elseif($action==='themes'){
    requireAuth();
    $themes=$game->getThemes();

    // Handle selection BEFORE reading theme for rendering
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $u0=$game->getUser($username);
        [$lvl0]=levelProgress($u0['experience']);
        $t=$_POST['theme']??'default';
        if(isset($themes[$t])&&$lvl0>=$themes[$t]['unlock_level']){
            $game->updateUser($username,['theme'=>$t]);
        }
        header('Location: ?action=themes'); exit;
    }

    // Read fresh user data so active theme is current
    [$th,$dm]=userTheme();
    $u=$game->getUser($username);
    [$lvl]=levelProgress($u['experience']);
    page_start('Themes',$th,$dm);
    ?>
    <div class="card">
      <a href="?action=menu" class="link text-sm">← Back</a>
      <h1 class="mt-2">🎨 Themes</h1>
      <p class="text-muted text-sm mb-2">You are Level <?=$lvl?> — unlock more by levelling up</p>
      <form method="POST">
        <div class="stacks">
          <?php foreach($themes as $key=>$t):
            $unlocked=$lvl>=$t['unlock_level'];
            $active=($u['theme']??'default')===$key;
          ?>
          <div style="background:<?=$active?'color-mix(in srgb,var(--acc1) 10%,transparent)':'var(--card)'?>;border:1.5px solid <?=$active?'var(--acc1)':'var(--brd)'?>;border-radius:12px;padding:15px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;opacity:<?=$unlocked?'1':'.4'?>;transition:opacity .2s">
            <div class="flex">
              <span style="font-size:1.7rem"><?=$t['icon']?></span>
              <div>
                <div style="font-weight:700"><?=$t['name']?></div>
                <div class="text-xs text-muted">Unlocks at Level <?=$t['unlock_level']?></div>
              </div>
            </div>
            <?php if($active):?>
            <span class="badge badge-acc">Active</span>
            <?php elseif($unlocked):?>
            <button type="submit" name="theme" value="<?=$key?>" class="btn btn-primary btn-sm">Select</button>
            <?php else:?>
            <span class="text-xs text-muted">🔒 Lv <?=$t['unlock_level']?></span>
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
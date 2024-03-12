<?php
namespace noahasu\casinoCoin;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class CasinoCoin extends PluginBase implements Listener {
    
    private static CasinoCoin $instance;
    
    const DEFAULT_CC = 100; // 初期カジノコイン
    const CC_UNIT = "CC";

    private Config $config;
    
    
    public static function getInstance(): CasinoCoin {
        return self::$instance;
    }

    protected function onLoad() : void {
        self::$instance = $this;
    }

    protected function onEnable() : void {
        $this -> getServer() -> getPluginManager() -> registerEvents($this, $this);
        if (!file_exists($this->getDataFolder())) {
            @mkdir($this->getDataFolder(), 0744, true);
        }
        $this -> config = new Config($this -> getDataFolder()."CasinoCoin.yml", Config::YAML);
    }

    public function onJoin(PlayerJoinEvent $ev) : void {
        $player = $ev -> getPlayer();
        $pname = $player -> getName();
        if(!$this -> isExistPlayerData($pname)) $this -> createPlayerData($pname);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!$sender instanceof Player || $label != 'cc') return false;
        $pname = $sender -> getName();
        
        // OPでない または 第一引数が入力されていない
        if(!$this -> getServer() -> isOp($pname) || !isset($args[0])) {
            $sender -> sendMessage(
                '§b>>§r 所持'.self::CC_UNIT.': '.$this -> getCasinoCoin($pname)
            );
            return true;
        }

        // 第二引数が入力されていない または configに存在しないプレイヤー名
        if(!isset($args[1]) || !$this -> isExistPlayerData($args[1])) {
            $sender -> sendMessage('§cプレイヤー名が入力されていないか、登録されていないプレイヤー名が入力されています。');
            return true;
        }

        $target = $args[1];

        // 第三引数が入力されていない または 数値でない または 0未満の数値
        if(!isset($args[2]) || !is_numeric($args[2]) || (int)$args[2] < 0) {
            $sender -> sendMessage('§c値が正しく入力されていません');
            return true;
        }

        $cc = (int)$args[2];

        switch($args[0]) {
            case 'set':
                $this -> setCasinoCoin($target, $cc);
                $sender -> sendMessage($target.'の所持'.self::CC_UNIT.'を'.$cc.'にセットしました。');
                break;
            case 'add':
                $this -> addCasinoCoin($target, $cc);
                $sender -> sendMessage($target.'の所持'.self::CC_UNIT.'を'.$cc.'増やしました。');
                break;
            case 'reduce':
                $this -> reduceCasinoCoin($target, $cc);
                $sender -> sendMessage($target.'の所持'.self::CC_UNIT.'を'.$cc.'減らしました。');
                break;
        }

        return true;
    }

    /**
     * データが存在するか
     * @param string $pname Playername
     * @return bool
     */
    public function isExistPlayerData(string $pname) : bool {
        return $this -> config -> get("$pname") != null;
    }

    /**
     * プレイヤーデータを作成
     * @param string $pname Playername
     */
    public function createPlayerData(string $pname) : void {
        $this -> config -> set("$pname");
        $this -> config -> save();
        $this -> config -> set("$pname", self::DEFAULT_CC);
        $this -> config -> save();
    }

    /**
     * 所持CCを取得
     * @param string $pname Playername
     * @return int 所持CC
     */
    public function getCasinoCoin(string $pname) : int {
        return $this -> config -> get("$pname");
    }

    /**
     * 所持CCを指定CCに変更
     * @param string $pname Playername
     * @param int $cc セットするCC (負の値は0になります。)
     */
    public function setCasinoCoin(string $pname, int $cc = 0) : void {
        if($cc < 0) $cc = 0;
        $this -> config -> set("$pname", $cc);
        $this -> config -> save();
    }
    
    /**
     * 所持CCを増やす
     * @param string $pname Playername
     * @param int $cc 増加値(非負)
     */
    public function addCasinoCoin(string $pname, int $cc = 0) : void {
        if($cc < 0) return;
        $this -> setCasinoCoin($pname, $this -> getCasinoCoin($pname) + $cc);
    }

    /**
     * 所持CCを減らす
     * @param string $pname PLayername
     * @param int $cc 減少値(非負)
     */
    public function reduceCasinoCoin(string $pname, int $cc = 0) : void {
        if($cc < 0) return;
        $this -> setCasinoCoin($pname, $this -> getCasinoCoin($pname) - $cc);
    }
}
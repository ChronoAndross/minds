<?php
if (PHP_SAPI !== 'cli') {
        echo "You must use the command line to run this script.";
        exit;
}

/**
 * Start the Elgg engine
 */
require_once(dirname(dirname(__FILE__)) . "/vendor/autoload.php");
define('__MINDS_INSTALLING__', true);

echo "Instaling Minds \n";

$minds = new Minds\Core\Minds();
$minds->loadLegacy();

$engine_dir = dirname(dirname(__FILE__)) . '/engine';

/**
 * Get passed options
 */
$args = array(
    'cassandra_server' => '127.0.0.1',
    'cassandra_keyspace' => 'minds',
    'path' => dirname(dirname(__FILE__)) . '/',
    'dataroot' => '/data/',
    'default_site' => 1,
    'site_secret' => md5(rand() . microtime()),
    'site_name' => 'Minds',
    'site_url' => 'http://localhost/',
    'site_email' => 'dev@minds.io',

    'username' => 'minds',
    'password' => 'password',
    'email' => 'dev@minds.io'
);
array_shift($argv);
foreach($argv as $arg){
    $arg = substr($arg, 1);
    $part = split('=',$arg);
    $args[$part[0]] = $part[1];
}

/**
 * Setup config file
 */
$template  = file_get_contents("$engine_dir/settings.example.php");
$params = array();
foreach($args as $k => $v){
    $template = str_replace("{{" . $k . "}}", $v, $template);
}
file_put_contents("$engine_dir/settings.php", $template);

/**
 * Setup cassandra
 */
echo "Setting up cassandra \n";
try{
    $db = new Minds\Core\Data\Call(null, $args['cassandra_keyspace'], array($args['cassandra_server']));
    if($db->keyspaceExists()){
        echo "Keyspace already installed. Done. \n";
        exit;
    }
    $attrs = array(   "strategy_options" => array("replication_factor" => "3"));
    $db->createKeyspace($attrs);
    $db->installSchema();
} catch (Exception $e){
    echo "Failed.. $e->why \n";
}
$minds->loadConfigs();
/**
 * Setup sites
 */
echo "Setting up site \n";
$site = new ElggSite();
$site->name = $args['site_name'];
$site->url = $args['site_url'];
$site->access_id = ACCESS_PUBLIC;
$site->email = $args['site_email'];
$guid = $site->save();
/**
 * Setup default user
 */
echo "Setting up a default user \n";
$guid = register_user(
                    $args['username'],
                    $args['password'],
                    $args['username'],
                    $args['email']
                    );
$user = new Minds\entities\user($guid);
$user->validated = true;
$user->validated_method = 'admin_user';
$user->save();

/**
 * Configure plugins
 */
$db = new Minds\Core\Data\Call('plugin', $args['cassandra_keyspace'], array($args['cassandra_server']));
$plugins = array('channel', 'thumbs', 'payments', 'blog', 'guard', 'notifications', 'groups', 'gatherings', 'archive');
foreach($plugins as $plugin){
  $db->insert($plugin, array('type'=>'plugin', 'active'=>1, 'access_id'=>2));
}

/**
 * A test post
 */
 $activity = new Minds\entities\activity();
 $activity->owner_guid = $guid;
 $activity->setMessage("Hello Minds!");
 $guid = $activity->save();

echo "minds is complete \n";

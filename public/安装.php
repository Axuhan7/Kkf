<?php
mysqli_report(MYSQLI_REPORT_OFF);
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
define('INSTALL_LOCK', dirname(__DIR__) . '/install/install.lock');
define('SQL_FILE', dirname(__DIR__) . '/install/data.sql');
define('DB_CONFIG_FILE', dirname(__DIR__) . '/config/database.php');

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$installInfo = isset($_SESSION['install_result']) ? $_SESSION['install_result'] : null;

// 已安装且不是刚安装完成的step2跳转时，显示已安装页面
if (file_exists(INSTALL_LOCK) && !($step == 2 && $installInfo)) {
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="/favicon.ico" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <title>系统已安装</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 450px; padding: 40px; text-align: center; }
        .icon { width: 70px; height: 70px; background: #52c41a; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 36px; color: #fff; }
        h1 { font-size: 24px; color: #333; margin-bottom: 12px; }
        p { font-size: 14px; color: #666; line-height: 1.8; margin-bottom: 20px; }
        .warning-box { background: #fff7e6; border: 1px solid #ffd591; border-radius: 10px; padding: 16px; text-align: left; margin-bottom: 20px; }
        .warning-box h3 { font-size: 14px; color: #d46b08; margin-bottom: 10px; }
        .warning-box ul { font-size: 13px; color: #666; list-style: none; }
        .warning-box li { padding: 6px 0; padding-left: 20px; position: relative; }
        .warning-box li::before { content: '•'; position: absolute; left: 0; color: #ff9a9e; }
        .btn { display: inline-block; padding: 12px 36px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 600; transition: opacity 0.3s; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✓</div>
        <h1>系统已安装</h1>
        <p>客服系统已经成功安装，请勿重复安装。</p>
        <div class="warning-box">
            <h3>安全提示</h3>
            <ul>
                <li>建议删除安装文件：<code>public/<?php echo basename(__FILE__); ?></code></li>
                <li>如需重新安装，请先删除锁文件：<code>install/install.lock</code></li>
                <li>重新安装将清空所有数据，请谨慎操作</li>
            </ul>
        </div>
        <a href="./index.php" class="btn">返回首页</a>
    </div>
</body>
</html>
<?php
    exit;
}

$error = '';
$success = '';

function testDbConnection($host, $port, $user, $pass, $dbname) {
    $port = empty($port) ? 3306 : $port;
    $conn = @mysqli_connect($host, $user, $pass, '', $port);
    if (!$conn) {
        return ['success' => false, 'msg' => '数据库连接失败: ' . mysqli_connect_error()];
    }
    if (!mysqli_select_db($conn, $dbname)) {
        $createResult = @mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        if (!$createResult) {
            mysqli_close($conn);
            return ['success' => false, 'msg' => '数据库不存在且无法创建: ' . mysqli_error($conn)];
        }
    }
    mysqli_close($conn);
    return ['success' => true, 'msg' => '数据库连接成功'];
}

function executeSqlFile($host, $port, $user, $pass, $dbname, $sqlFile) {
    $port = empty($port) ? 3306 : $port;
    $conn = @mysqli_connect($host, $user, $pass, $dbname, $port);
    if (!$conn) {
        return ['success' => false, 'msg' => '数据库连接失败: ' . mysqli_connect_error()];
    }
    @mysqli_query($conn, "SET NAMES utf8mb4");
    @mysqli_query($conn, "SET SESSION innodb_strict_mode = 0");
    
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        mysqli_close($conn);
        return ['success' => false, 'msg' => '无法读取SQL文件'];
    }
    
    @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    // 先删除已有表，避免残留结构冲突
    preg_match_all('/DROP TABLE IF EXISTS `([^`]+)`/', $sql, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $table) {
            @mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
        }
    }
    
    // 逐条执行语句，便于精确定位错误
    $statements = splitSqlStatements($sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '' || strpos($statement, '--') === 0) {
            continue;
        }
        if (!@mysqli_query($conn, $statement)) {
            $err = mysqli_error($conn);
            // 表已存在等幂等错误可忽略
            if (strpos($err, 'already exists') === false) {
                @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
                mysqli_close($conn);
                return ['success' => false, 'msg' => 'SQL执行失败: ' . $err];
            }
        }
    }
    
    @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    
    mysqli_close($conn);
    return ['success' => true, 'msg' => 'SQL导入成功'];
}

/**
 * 将SQL文本按语句拆分（处理注释与分号）
 */
function splitSqlStatements($sql) {
    // 去掉 -- 单行注释
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    // 去掉 /*!40101 ... */ 这类条件注释语句
    $sql = preg_replace('/\/\*!\d+[^*]*\*\//', '', $sql);
    $statements = [];
    $current = '';
    $inString = false;
    $quote = '';
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        if ($inString) {
            $current .= $char;
            // 反斜杠转义：原样吞掉下一个字符（\' \\ \" 等）
            if ($char === '\\') {
                if ($i + 1 < $len) {
                    $i++;
                    $current .= $sql[$i];
                }
                continue;
            }
            if ($char === $quote) {
                // 双写引号转义（'' 或 ""）视为字符串内容
                if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                    $i++;
                    $current .= $sql[$i];
                    continue;
                }
                $inString = false;
            }
            continue;
        }
        if ($char === "'" || $char === '"') {
            $inString = true;
            $quote = $char;
            $current .= $char;
            continue;
        }
        if ($char === ';') {
            if (trim($current) !== '') {
                $statements[] = $current;
            }
            $current = '';
            continue;
        }
        $current .= $char;
    }
    if (trim($current) !== '') {
        $statements[] = $current;
    }
    return $statements;
}

function createAdminAccounts($host, $port, $user, $pass, $dbname, $adminUser, $adminPass, $platformUser, $platformPass) {
    $port = empty($port) ? 3306 : $port;
    $conn = @mysqli_connect($host, $user, $pass, $dbname, $port);
    if (!$conn) {
        return ['success' => false, 'msg' => '数据库连接失败: ' . mysqli_connect_error()];
    }
    @mysqli_query($conn, "SET NAMES utf8mb4");
    
    // 先用原始值计算密码哈希，再转义用于SQL
    $platformHash = md5(md5($platformPass) . $platformUser);
    $adminHash = md5($adminUser . "hjkj" . $adminPass);
    
    $platformUser = mysqli_real_escape_string($conn, $platformUser);
    $adminUser = mysqli_real_escape_string($conn, $adminUser);
    
    $platformSql = "INSERT INTO `wolive_admin` (`username`, `password`, `addtime`, `is_delete`, `app_max_count`, `permission`, `remark`, `expire_time`, `mobile`) VALUES ('$platformUser', '$platformHash', " . time() . ", 0, 999, '', '平台管理员', 0, '')";
    if (!@mysqli_query($conn, $platformSql)) {
        mysqli_close($conn);
        return ['success' => false, 'msg' => '创建平台管理员失败: ' . mysqli_error($conn)];
    }
    $adminId = mysqli_insert_id($conn);
    
    $businessSql = "INSERT INTO `wolive_business` (`business_name`, `logo`, `copyright`, `admin_id`, `video_state`, `voice_state`, `audio_state`, `template_state`, `distribution_rule`, `voice_address`, `domain_entry`, `domain_landing`, `remark`, `expire_time`, `max_count`, `push_url`, `state`, `is_recycle`, `is_delete`) VALUES ('默认商户', '', '', $adminId, 'close', 'open', 'close', 'close', 'auto', '/upload/voice/default.mp3', '', '', '', 0, 999, '', 'open', 0, 0)";
    if (!@mysqli_query($conn, $businessSql)) {
        mysqli_close($conn);
        return ['success' => false, 'msg' => '创建商户失败: ' . mysqli_error($conn)];
    }
    $businessId = mysqli_insert_id($conn);
    
    $adminSql = "INSERT INTO `wolive_service` (`user_name`, `nick_name`, `password`, `groupid`, `groupid2`, `phone`, `open_id`, `email`, `business_id`, `avatar`, `level`, `parent_id`, `offline_first`, `state`) VALUES ('$adminUser', '管理员', '$adminHash', '0', '0', '', '', '', $businessId, '/assets/images/admin/avatar-admin2.png', 'super_manager', 0, 0, 'offline')";
    if (!@mysqli_query($conn, $adminSql)) {
        mysqli_close($conn);
        return ['success' => false, 'msg' => '创建后台管理员失败: ' . mysqli_error($conn)];
    }
    
    mysqli_close($conn);
    return ['success' => true, 'msg' => '管理员账号创建成功'];
}

/**
 * 写入项目根目录 .env（加固版通过环境变量读库与域名，不再改写 database.php）
 */
function writeEnvConfig($host, $port, $user, $pass, $dbname, $domain) {
    $port = empty($port) ? '3306' : $port;
    $domain = rtrim($domain, '/');
    $hostName = parse_url($domain, PHP_URL_HOST);
    if (!$hostName) {
        $hostName = 'your-domain.com';
    }
    $wss = preg_replace('#^https?://#i', 'wss://', $domain);
    $quote = function ($v) {
        $v = str_replace(['\\', '"'], ['\\\\', '\\"'], (string)$v);
        return '"' . $v . '"';
    };
    $rand = function ($len = 32) {
        try {
            return bin2hex(random_bytes((int)($len / 2)));
        } catch (Exception $e) {
            return substr(md5(uniqid((string)mt_rand(), true)), 0, $len);
        }
    };
    $cert = '/www/server/panel/vhost/cert/' . $hostName . '/fullchain.pem';
    $pk = '/www/server/panel/vhost/cert/' . $hostName . '/privkey.pem';
    // 证书不存在时留空（方案 B / 尚未开 SSL），避免 pusher 启动即退出
    if (!is_file($cert) || !is_file($pk)) {
        $cert = '';
        $pk = '';
    }
    $lines = [
        '; Generated by web installer. Values with : must be quoted.',
        '',
        '; ---- Database ----',
        'DB_HOST = ' . $quote($host),
        'DB_PORT = ' . $quote($port),
        'DB_DATABASE = ' . $quote($dbname),
        'DB_USERNAME = ' . $quote($user),
        'DB_PASSWORD = ' . $quote($pass),
        '',
        '; ---- Site (no trailing slash) ----',
        'APP_DOMAIN = ' . $quote($domain),
        '',
        '; ---- Pusher ----',
        'PUSHER_APP_KEY = ' . $quote($rand(32)),
        'PUSHER_APP_SECRET = ' . $quote($rand(32)),
        'PUSHER_APP_ID = "232"',
        'PUSHER_WHOST = ' . $quote($wss),
        'PUSHER_WPORT = "2081"',
        'PUSHER_AHOST = "http://127.0.0.1"',
        'PUSHER_APORT = "2082"',
        'PUSHER_SSL_CERT = ' . $quote($cert),
        'PUSHER_SSL_PK = ' . $quote($pk),
        '',
        '; ---- Security ----',
        'YMWL_SALT = ' . $quote($rand(32)),
        'REGIST_TOKEN = ' . $quote($rand(16)),
        'API_ACCESS_TOKEN = ' . $quote($rand(32)),
        '',
    ];
    $envFile = dirname(__DIR__) . '/.env';
    if (@file_put_contents($envFile, implode("\n", $lines)) === false) {
        return ['success' => false, 'msg' => '写入 .env 失败，请检查项目根目录权限'];
    }
    @chmod($envFile, 0600);
    return ['success' => true, 'msg' => '已写入 .env'];
}

/** @deprecated 保留函数名兼容；实际写 .env */
function writeDbConfig($host, $port, $user, $pass, $dbname, $domain = '') {
    return writeEnvConfig($host, $port, $user, $pass, $dbname, $domain);
}

function createInstallLock() {
    if (file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'))) {
        return ['success' => true, 'msg' => '安装锁创建成功'];
    }
    return ['success' => false, 'msg' => '安装锁创建失败，请检查目录权限'];
}

function replaceDomainInFiles($domain) {
    $domain = rtrim($domain, '/');
    $host = parse_url($domain, PHP_URL_HOST);
    $errors = [];

    // 1. ymwl_pusher/config.php: 替换 $domain 值（保留末尾斜杠，用于拼接 /admin/event）
    $configPhp = dirname(__DIR__) . '/ymwl_pusher/config.php';
    if (file_exists($configPhp)) {
        $content = file_get_contents($configPhp);
        $newContent = preg_replace_callback(
            '/(\$domain\s*=\s*[\'"])https?:\/\/[^\'"]*([\'"])/',
            function ($m) use ($domain) {
                return $m[1] . $domain . '/' . $m[2];
            },
            $content
        );
        if ($newContent === null || file_put_contents($configPhp, $newContent) === false) {
            $errors[] = '写入 ymwl_pusher/config.php 失败';
        }
    }

    // 2. ymwl_pusher/start.php: 替换SSL证书路径中的域名目录（证书目录名一般为域名）
    $startPhp = dirname(__DIR__) . '/ymwl_pusher/start.php';
    if (file_exists($startPhp)) {
        $content = file_get_contents($startPhp);
        // 将 $ws_ssl_cert / $ws_ssl_pk 路径中"文件名前的那一段目录"替换为新域名
        $newContent = preg_replace_callback(
            "/(\\\$ws_ssl_(?:cert|pk)\s*=\s*')([^']+)(')/",
            function ($m) use ($host) {
                $path = $m[2];
                $parts = explode('/', rtrim($path, '/'));
                if (count($parts) >= 2) {
                    $parts[count($parts) - 2] = $host;
                }
                return $m[1] . implode('/', $parts) . $m[3];
            },
            $content
        );
        if ($newContent === null || file_put_contents($startPhp, $newContent) === false) {
            $errors[] = '写入 ymwl_pusher/start.php 失败';
        }
    }

    // 3. public/index.php: 替换 whost(wss)、ahost(http)、domain(https) 三个常量
    $indexPhp = dirname(__DIR__) . '/public/index.php';
    if (file_exists($indexPhp)) {
        $content = file_get_contents($indexPhp);
        $wssDomain = str_replace(['http://', 'https://'], 'wss://', $domain);
        $httpDomain = str_replace(['https://', 'wss://'], 'http://', $domain);
        $newContent = preg_replace_callback(
            "/(define\(\s*'whost'\s*,\s*')[^']*('\s*\))/",
            function ($m) use ($wssDomain) { return $m[1] . $wssDomain . $m[2]; },
            $content
        );
        $newContent = preg_replace_callback(
            "/(define\(\s*'ahost'\s*,\s*')[^']*('\s*\))/",
            function ($m) use ($httpDomain) { return $m[1] . $httpDomain . $m[2]; },
            $newContent
        );
        $newContent = preg_replace_callback(
            "/(define\(\s*'domain'\s*,\s*')[^']*('\s*\))/",
            function ($m) use ($domain) { return $m[1] . $domain . $m[2]; },
            $newContent
        );
        if ($newContent === null || file_put_contents($indexPhp, $newContent) === false) {
            $errors[] = '写入 public/index.php 失败';
        }
    }

    if (empty($errors)) {
        return ['success' => true, 'msg' => '域名配置成功'];
    }
    return ['success' => false, 'msg' => implode('; ', $errors)];
}

/**
 * 自动启动 ymwl_pusher WebSocket 服务（仅Linux环境，Workerman daemon模式）
 */
function startPusherService() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return ['success' => false, 'msg' => 'Windows环境不支持自动启动，请在服务器上手动执行: cd ymwl_pusher && php start.php start -d'];
    }
    $pusherDir = dirname(__DIR__) . '/ymwl_pusher';
    if (!is_dir($pusherDir)) {
        return ['success' => false, 'msg' => 'ymwl_pusher 目录不存在'];
    }
    $phpBin = '/www/server/php/74/bin/php';
    if (!is_file($phpBin)) {
        $phpBin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    }
    $phpBinEsc = escapeshellarg($phpBin);
    $logFile = '/tmp/ymwl_pusher_install_' . time() . '.log';
    // 使用 restart 保证新域名配置生效（服务未运行时restart等同于start），后台执行避免阻塞
    $cmd = "cd " . escapeshellarg($pusherDir) . " && $phpBinEsc start.php restart -d > " . escapeshellarg($logFile) . " 2>&1 &";
    $output = [];
    $returnVar = 0;
    @exec($cmd, $output, $returnVar);

    // 读取websocket端口用于验证
    $port = 2081;
    $configFile = $pusherDir . '/config.php';
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        if (preg_match('/\$websocket_port\s*=\s*(\d+)/', $configContent, $m)) {
            $port = intval($m[1]);
        }
    }
    // 等待进程启动后再读取日志并检测端口
    sleep(2);
    $outputStr = @file_exists($logFile) ? @file_get_contents($logFile) : '';
    $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 3);
    if ($conn) {
        fclose($conn);
        return ['success' => true, 'msg' => 'Pusher服务启动成功（端口 ' . $port . '）'];
    }
    return ['success' => false, 'msg' => 'Pusher服务可能未启动，请手动执行: cd ymwl_pusher && php start.php start -d。输出信息: ' . mb_substr($outputStr, 0, 300)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['domain', 'db_host', 'db_port', 'db_user', 'db_pass', 'db_name', 'admin_user', 'admin_pass', 'platform_user', 'platform_pass'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            $_POST[$field] = '';
        }
    }
    $domain = trim($_POST['domain']);
    $host = trim($_POST['db_host']);
    $port = trim($_POST['db_port']);
    $user = trim($_POST['db_user']);
    $pass = trim($_POST['db_pass']);
    $dbname = trim($_POST['db_name']);
    $adminUser = trim($_POST['admin_user']);
    $adminPass = trim($_POST['admin_pass']);
    $platformUser = trim($_POST['platform_user']);
    $platformPass = trim($_POST['platform_pass']);

    if (empty($domain) || empty($host) || empty($user) || empty($dbname) || empty($adminUser) || empty($adminPass) || empty($platformUser) || empty($platformPass)) {
        $response = ['success' => false, 'msg' => '请填写所有必填项'];
    } else {
        if (!preg_match('/^https?:\/\/[^\/]+$/', $domain)) {
            $response = ['success' => false, 'msg' => '域名格式不正确，请输入完整URL（如：https://example.com）'];
        } else {
            $result = testDbConnection($host, $port, $user, $pass, $dbname);
            if (!$result['success']) {
                $response = ['success' => false, 'msg' => $result['msg']];
            } else {
                $result = executeSqlFile($host, $port, $user, $pass, $dbname, SQL_FILE);
                if (!$result['success']) {
                    $response = ['success' => false, 'msg' => $result['msg']];
                } else {
                    $result = createAdminAccounts($host, $port, $user, $pass, $dbname, $adminUser, $adminPass, $platformUser, $platformPass);
                    if (!$result['success']) {
                        $response = ['success' => false, 'msg' => $result['msg']];
                    } else {
                        $result = writeEnvConfig($host, $port, $user, $pass, $dbname, $domain);
                        if (!$result['success']) {
                            $response = ['success' => false, 'msg' => $result['msg']];
                        } else {
                            // 加固版域名/密钥均走 .env，无需改写 index.php / pusher 硬编码
                            $pusherResult = startPusherService();

                            $result = createInstallLock();
                            if (!$result['success']) {
                                $response = ['success' => false, 'msg' => $result['msg']];
                            } else {
                                $_SESSION['install_result'] = [
                                    'domain' => rtrim($domain, '/'),
                                    'platform_user' => $platformUser,
                                    'platform_pass' => $platformPass,
                                    'admin_user' => $adminUser,
                                    'admin_pass' => $adminPass,
                                    'pusher_msg' => $pusherResult['msg'],
                                    'pusher_success' => $pusherResult['success'],
                                ];
                                $response = ['success' => true, 'msg' => '安装完成！', 'redirect' => './' . rawurlencode(basename(__FILE__)) . '?step=2'];
                            }
                        }
                    }
                }
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($step == 1) {
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="/favicon.ico" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <meta content="telephone=no,email=no" name="format-detection" />
    <title>云宫客服系统安装</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .layadmin-user-login {
            position: relative;
            min-height: 100vh;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .bg-nantianmen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url(/assets/images/admin/nantianmen-bg.jpg) center center / cover no-repeat;
            z-index: 1;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .bg-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center 40%, rgba(255, 200, 100, 0.15) 0%, transparent 50%);
            z-index: 2;
            pointer-events: none;
        }

        .cloud-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            pointer-events: none;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .cloud {
            position: absolute;
            opacity: 0.9;
            animation: cloudFloat 6s ease-in-out infinite;
        }

        .cloud img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 6px 16px rgba(0,0,0,0.3)) drop-shadow(0 2px 4px rgba(139,105,20,0.2));
        }

        .cloud:nth-child(1) { top: 2%; left: 2%; width: 200px; animation-delay: 0s; }
        .cloud:nth-child(2) { top: 12%; right: 3%; width: 240px; animation-delay: 1s; }
        .cloud:nth-child(3) { bottom: 8%; left: 5%; width: 220px; animation-delay: 2s; }
        .cloud:nth-child(4) { bottom: 15%; right: 2%; width: 180px; animation-delay: 0.5s; }
        .cloud:nth-child(5) { top: 38%; left: 1%; width: 160px; animation-delay: 3s; }
        .cloud:nth-child(6) { top: 48%; right: 1%; width: 170px; animation-delay: 1.5s; }
        .cloud:nth-child(7) { top: 68%; left: 12%; width: 140px; animation-delay: 2.5s; }
        .cloud:nth-child(8) { top: 22%; left: 18%; width: 120px; animation-delay: 4s; }
        .cloud:nth-child(9) { bottom: 25%; left: 30%; width: 150px; animation-delay: 1.8s; }

        @keyframes cloudFloat {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-12px) translateX(8px); }
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            padding: 0;
        }

        .layadmin-user-login-main {
            background: rgba(254, 254, 254, 0.95) !important;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(255, 215, 0, 0.2);
            padding: 45px 40px 35px !important;
            width: 520px !important;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.3);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .layadmin-user-login-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, #FFD700, #FFA500, #FFD700, transparent);
            border-radius: 20px 20px 0 0;
        }

        .layadmin-user-login-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-logo {
            width: 72px;
            height: 72px;
            margin: 0 auto 14px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
            border: 2px solid rgba(255, 215, 0, 0.6);
            transition: transform 0.4s ease;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
            font-weight: bold;
        }

        .login-logo:hover {
            transform: scale(1.06);
        }

        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .layadmin-user-login-header h2 {
            font-size: 28px;
            color: #8B4513;
            margin: 0;
            font-weight: 700;
            letter-spacing: 6px;
            text-shadow: 1px 1px 2px rgba(255, 215, 0, 0.3);
        }

        .layadmin-user-login-header .tagline {
            font-size: 13px;
            color: #B8860B;
            margin-top: 8px;
            letter-spacing: 3px;
            font-weight: 500;
        }

        .journey-quote {
            text-align: center;
            margin-bottom: 28px;
            padding: 12px 20px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.08));
            border-radius: 12px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            position: relative;
        }

        .journey-quote::before,
        .journey-quote::after {
            content: '☁';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            opacity: 0.5;
            color: #DAA520;
        }

        .journey-quote::before { left: 8px; }
        .journey-quote::after { right: 8px; }

        .journey-quote p {
            font-size: 14px;
            color: #8B4513;
            letter-spacing: 2px;
            line-height: 1.8;
            font-weight: 500;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #8B4513;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 16px;
            background: linear-gradient(180deg, #FFD700, #FFA500);
            border-radius: 2px;
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group label .required {
            color: #FF6B6B;
            margin-left: 4px;
        }

        .form-group input {
            width: 100%;
            height: 44px;
            border: 1.5px solid rgba(255, 215, 0, 0.3) !important;
            border-radius: 12px !important;
            padding: 0 20px !important;
            font-size: 14px !important;
            background: rgba(255, 255, 255, 0.9) !important;
            transition: all 0.3s ease;
            color: #333 !important;
            outline: none;
        }

        .form-group input:focus {
            border-color: #FFD700 !important;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
            background: #fff !important;
        }

        .form-group input::placeholder {
            color: #bbb;
        }

        .form-group .hint {
            color: #999;
            font-size: 12px;
            margin-top: 6px;
        }

        .error-box {
            background: #fff2f0;
            border: 1px solid #ffccc7;
            border-radius: 10px;
            padding: 14px 16px;
            color: #ff4d4f;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }

        .error-box::before {
            content: '✕';
            width: 20px;
            height: 20px;
            background: #ff4d4f;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            letter-spacing: 4px;
            background: linear-gradient(135deg, #DAA520, #FFD700, #FFA500);
            border: none;
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.5);
            transition: all 0.3s ease;
            color: #fff;
            cursor: pointer;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(218, 165, 32, 0.7);
            background: linear-gradient(135deg, #FFD700, #FFA500, #DAA520);
        }

        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .layadmin-user-login-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            z-index: 10;
            letter-spacing: 1px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        @media screen and (max-width: 767px) {
            .layadmin-user-login {
                align-items: flex-start !important;
                padding-top: 10px;
                min-height: 100vh;
                overflow-y: auto;
            }

            .layadmin-user-login-main {
                width: 92% !important;
                padding: 30px 20px 24px !important;
                max-height: calc(100vh - 20px);
            }

            .login-wrapper {
                padding: 10px 0;
                width: 100%;
            }

            .layadmin-user-login-header {
                margin-bottom: 20px;
            }

            .login-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 10px;
            }

            .layadmin-user-login-header h2 {
                font-size: 22px;
                letter-spacing: 4px;
            }

            .layadmin-user-login-header .tagline {
                font-size: 12px;
                margin-top: 4px;
            }

            .journey-quote {
                margin-bottom: 20px;
                padding: 10px 16px;
            }

            .journey-quote p {
                font-size: 12px;
                line-height: 1.6;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .form-group input {
                height: 42px !important;
                font-size: 14px !important;
            }

            .btn-submit {
                height: 44px;
                font-size: 16px;
            }

            .cloud { display: none; }

            .layadmin-user-login-footer {
                position: static;
                padding: 16px 0;
            }
        }

        @media screen and (max-width: 360px) {
            .layadmin-user-login-main {
                padding: 24px 16px 20px !important;
            }

            .layadmin-user-login-header h2 {
                font-size: 20px;
            }

            .login-logo {
                width: 52px;
                height: 52px;
            }

            .journey-quote p {
                font-size: 11px;
            }

            .form-group input {
                height: 40px !important;
            }

            .btn-submit {
                height: 42px;
                font-size: 15px;
            }
        }

        .progress-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .progress-content {
            background: #fff;
            border-radius: 12px;
            width: 320px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .progress-header {
            background: linear-gradient(135deg, #DAA520, #FFD700);
            padding: 15px 20px;
            text-align: center;
        }

        .progress-header h3 {
            color: #fff;
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }

        .progress-body {
            padding: 30px 20px;
            text-align: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            position: relative;
            margin: 0 auto 15px;
        }
        .spinner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 4px solid #f0f0f0;
            border-top-color: #DAA520;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .progress-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .progress-bar-wrap {
            width: 100%;
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #DAA520, #FFD700);
            width: 0%;
            transition: width 0.2s ease;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="layadmin-user-login layadmin-user-display-show" id="LAY-user-login">
        <div class="bg-nantianmen"></div>
        <div class="bg-glow"></div>
        <div class="cloud-layer" id="cloudLayer">
            <div class="cloud"><img src="/assets/images/admin/yun1.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun2.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun3.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun4.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun5.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun6.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun7.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun8.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun9.png" alt=""></div>
        </div>

        <div class="login-wrapper">
            <div class="layadmin-user-login-main">
                <div class="layadmin-user-login-header">
                    <div class="login-logo">
                        <img src="/upload/images/xitongkefulogo.jpg" alt="云宫" onerror="this.style.display='none'; this.parentNode.textContent='云';">
                    </div>
                    <h2>云 宫</h2>
                    <div class="tagline">客服系统安装向导</div>
                </div>

                <div class="journey-quote">
                    <p>「踏平坎坷成大道，斗罢艰险又出发」</p>
                </div>

                <?php if ($error): ?>
                <div class="error-box"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="installForm">
                    <div class="section-title">基本配置</div>
                    <div class="form-group">
                        <label>系统域名 <span class="required">*</span></label>
                        <input type="text" name="domain" placeholder="例如: https://www.yanshi.fun" value="<?php echo isset($_POST['domain']) ? htmlspecialchars($_POST['domain']) : ''; ?>">
                        <div class="hint">请输入完整URL，以http://或https://开头，不带末尾斜杠</div>
                    </div>

                    <div class="section-title">数据库配置</div>
                    <div class="form-group">
                        <label>数据库地址 <span class="required">*</span></label>
                        <input type="text" name="db_host" placeholder="例如: 127.0.0.1" value="<?php echo isset($_POST['db_host']) ? htmlspecialchars($_POST['db_host']) : '127.0.0.1'; ?>">
                    </div>
                    <div class="form-group">
                        <label>数据库端口</label>
                        <input type="text" name="db_port" placeholder="默认: 3306" value="<?php echo isset($_POST['db_port']) ? htmlspecialchars($_POST['db_port']) : '3306'; ?>">
                    </div>
                    <div class="form-group">
                        <label>数据库用户名 <span class="required">*</span></label>
                        <input type="text" name="db_user" placeholder="例如: root" value="<?php echo isset($_POST['db_user']) ? htmlspecialchars($_POST['db_user']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>数据库密码 <span class="required">*</span></label>
                        <input type="password" name="db_pass" placeholder="输入数据库密码" value="<?php echo isset($_POST['db_pass']) ? htmlspecialchars($_POST['db_pass']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>数据库名称 <span class="required">*</span></label>
                        <input type="text" name="db_name" placeholder="例如: kf3_db" value="<?php echo isset($_POST['db_name']) ? htmlspecialchars($_POST['db_name']) : ''; ?>">
                        <div class="hint">如果数据库不存在，系统将自动创建</div>
                    </div>

                    <div class="section-title">后台管理员账号（/admin）</div>
                    <div class="form-group">
                        <label>用户名 <span class="required">*</span></label>
                        <input type="text" name="admin_user" placeholder="例如: admin" value="<?php echo isset($_POST['admin_user']) ? htmlspecialchars($_POST['admin_user']) : 'admin'; ?>">
                    </div>
                    <div class="form-group">
                        <label>密码 <span class="required">*</span></label>
                        <input type="password" name="admin_pass" placeholder="输入管理员密码" value="<?php echo isset($_POST['admin_pass']) ? htmlspecialchars($_POST['admin_pass']) : ''; ?>">
                    </div>

                    <div class="section-title">平台管理员账号（/platform）</div>
                    <div class="form-group">
                        <label>用户名 <span class="required">*</span></label>
                        <input type="text" name="platform_user" placeholder="例如: platform" value="<?php echo isset($_POST['platform_user']) ? htmlspecialchars($_POST['platform_user']) : 'platform'; ?>">
                    </div>
                    <div class="form-group">
                        <label>密码 <span class="required">*</span></label>
                        <input type="password" name="platform_pass" placeholder="输入平台管理员密码" value="<?php echo isset($_POST['platform_pass']) ? htmlspecialchars($_POST['platform_pass']) : ''; ?>">
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span id="btnText">开始安装</span>
                        <span id="btnLoading" class="loading" style="display:none"></span>
                    </button>
                </form>
            </div>
        </div>

        <div class="layui-trans layadmin-user-login-footer">
            <p>© 2026 云宫客服系统</p>
        </div>
    </div>

    <script>
        (function() {
            var cloudLayer = document.getElementById('cloudLayer');
            var bgNantianmen = document.querySelector('.bg-nantianmen');
            if (!cloudLayer) return;

            var mouseX = 0, mouseY = 0;
            var currentX = 0, currentY = 0;

            document.addEventListener('mousemove', function(e) {
                mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
                mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
            });

            function animate() {
                currentX += (mouseX - currentX) * 0.05;
                currentY += (mouseY - currentY) * 0.05;

                var cloudMoveX = currentX * 30;
                var cloudMoveY = currentY * 20;
                cloudLayer.style.transform = 'translate(' + cloudMoveX + 'px, ' + cloudMoveY + 'px)';

                if (bgNantianmen) {
                    var bgMoveX = currentX * 8;
                    var bgMoveY = currentY * 5;
                    bgNantianmen.style.transform = 'translate(' + bgMoveX + 'px, ' + bgMoveY + 'px)';
                }

                requestAnimationFrame(animate);
            }

            animate();
        })();

        var progressModal = document.createElement('div');
        progressModal.className = 'progress-modal';
        progressModal.innerHTML = `
            <div class="progress-content">
                <div class="progress-header">
                    <h3>正在安装...</h3>
                </div>
                <div class="progress-body">
                    <div class="spinner"></div>
                    <div class="progress-text">正在处理，请稍候...</div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(progressModal);

        var progressBar = document.getElementById('progressBar');
        var progressInterval;

        function startProgressAnimation() {
            var width = 0;
            progressInterval = setInterval(function() {
                width += Math.random() * 5;
                if (width > 90) width = 90;
                progressBar.style.width = width + '%';
            }, 200);
        }

        function stopProgressAnimation() {
            clearInterval(progressInterval);
            progressBar.style.width = '100%';
        }

        document.getElementById('installForm').addEventListener('submit', function(e) {
            e.preventDefault();
            progressModal.style.display = 'flex';
            progressBar.style.width = '0%';
            startProgressAnimation();

            var formData = new FormData(this);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', this.action, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    stopProgressAnimation();
                    if (xhr.status == 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 500);
                        } else {
                            setTimeout(function() {
                                progressModal.style.display = 'none';
                                alert(response.msg);
                                document.getElementById('btnText').style.display = 'inline-block';
                                document.getElementById('btnLoading').style.display = 'none';
                                document.getElementById('submitBtn').disabled = false;
                            }, 500);
                        }
                    } else {
                        setTimeout(function() {
                            progressModal.style.display = 'none';
                            alert('安装请求失败，请重试');
                        }, 500);
                    }
                }
            };
            xhr.send(formData);
        });
    </script>
</body>
</html>
<?php
} elseif ($step == 2) {
    $info = $installInfo;
    // 没有安装信息时（直接访问或刷新页面），跳回安装首页
    if (!$info) {
        header('Location: ./' . rawurlencode(basename(__FILE__)));
        exit;
    }
    // 显示完成后清除session，防止信息泄露
    unset($_SESSION['install_result']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="/favicon.ico" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <meta content="telephone=no,email=no" name="format-detection" />
    <title>安装完成</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .layadmin-user-login {
            position: relative;
            min-height: 100vh;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .bg-nantianmen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url(/assets/images/admin/nantianmen-bg.jpg) center center / cover no-repeat;
            z-index: 1;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .bg-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center 40%, rgba(255, 200, 100, 0.15) 0%, transparent 50%);
            z-index: 2;
            pointer-events: none;
        }

        .cloud-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            pointer-events: none;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .cloud {
            position: absolute;
            opacity: 0.9;
            animation: cloudFloat 6s ease-in-out infinite;
        }

        .cloud img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 6px 16px rgba(0,0,0,0.3)) drop-shadow(0 2px 4px rgba(139,105,20,0.2));
        }

        .cloud:nth-child(1) { top: 2%; left: 2%; width: 200px; animation-delay: 0s; }
        .cloud:nth-child(2) { top: 12%; right: 3%; width: 240px; animation-delay: 1s; }
        .cloud:nth-child(3) { bottom: 8%; left: 5%; width: 220px; animation-delay: 2s; }
        .cloud:nth-child(4) { bottom: 15%; right: 2%; width: 180px; animation-delay: 0.5s; }
        .cloud:nth-child(5) { top: 38%; left: 1%; width: 160px; animation-delay: 3s; }
        .cloud:nth-child(6) { top: 48%; right: 1%; width: 170px; animation-delay: 1.5s; }
        .cloud:nth-child(7) { top: 68%; left: 12%; width: 140px; animation-delay: 2.5s; }
        .cloud:nth-child(8) { top: 22%; left: 18%; width: 120px; animation-delay: 4s; }
        .cloud:nth-child(9) { bottom: 25%; left: 30%; width: 150px; animation-delay: 1.8s; }

        @keyframes cloudFloat {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-12px) translateX(8px); }
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            padding: 0;
        }

        .layadmin-user-login-main {
            background: rgba(254, 254, 254, 0.95) !important;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(255, 215, 0, 0.2);
            padding: 45px 40px 35px !important;
            width: 520px !important;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.3);
            text-align: center;
        }

        .layadmin-user-login-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, #52c41a, #73d13d, #52c41a, transparent);
            border-radius: 20px 20px 0 0;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #52c41a, #73d13d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: #fff;
            box-shadow: 0 8px 24px rgba(82, 196, 26, 0.5);
        }

        .layadmin-user-login-header h2 {
            font-size: 28px;
            color: #8B4513;
            margin: 0;
            font-weight: 700;
            letter-spacing: 6px;
            text-shadow: 1px 1px 2px rgba(255, 215, 0, 0.3);
        }

        .layadmin-user-login-header p {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }

        .info-box {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.08), rgba(255, 165, 0, 0.06));
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            text-align: left;
        }

        .info-title {
            font-size: 14px;
            font-weight: 600;
            color: #8B4513;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .info-title::before {
            content: '';
            width: 4px;
            height: 14px;
            background: linear-gradient(180deg, #FFD700, #FFA500);
            border-radius: 2px;
            margin-right: 8px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            font-size: 13px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            height: 44px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #DAA520, #FFD700, #FFA500);
            color: #fff;
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.5);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(218, 165, 32, 0.7);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #f0f0f0;
            border-color: #d0d0d0;
        }

        .layadmin-user-login-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            z-index: 10;
            letter-spacing: 1px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        @media screen and (max-width: 767px) {
            .layadmin-user-login-main {
                width: 92% !important;
                padding: 30px 20px 24px !important;
            }

            .success-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }

            .layadmin-user-login-header h2 {
                font-size: 22px;
                letter-spacing: 4px;
            }

            .cloud { display: none; }

            .layadmin-user-login-footer {
                position: static;
                padding: 16px 0;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="layadmin-user-login layadmin-user-display-show" id="LAY-user-login">
        <div class="bg-nantianmen"></div>
        <div class="bg-glow"></div>
        <div class="cloud-layer" id="cloudLayer">
            <div class="cloud"><img src="/assets/images/admin/yun1.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun2.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun3.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun4.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun5.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun6.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun7.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun8.png" alt=""></div>
            <div class="cloud"><img src="/assets/images/admin/yun9.png" alt=""></div>
        </div>

        <div class="login-wrapper">
            <div class="layadmin-user-login-main">
                <div class="layadmin-user-login-header">
                    <div class="success-icon">✓</div>
                    <h2>安装完成</h2>
                    <p>客服系统已成功安装，请使用以下账号登录</p>
                </div>

                <div class="content">
                    <div class="info-box">
                        <div class="info-title">平台管理员</div>
                        <div class="info-item"><span class="info-label">登录地址</span><span class="info-value" style="word-break: break-all;"><a href="<?php echo htmlspecialchars($info['domain']); ?>/platform" target="_blank" style="color: #333; text-decoration: underline;"><?php echo htmlspecialchars($info['domain']); ?>/platform</a></span></div>
                        <div class="info-item"><span class="info-label">用户名</span><span class="info-value"><?php echo htmlspecialchars($info['platform_user']); ?></span></div>
                        <div class="info-item"><span class="info-label">密码</span><span class="info-value"><?php echo htmlspecialchars($info['platform_pass']); ?></span></div>
                    </div>
                    <div class="info-box">
                        <div class="info-title">后台管理员</div>
                        <div class="info-item"><span class="info-label">登录地址</span><span class="info-value" style="word-break: break-all;"><a href="<?php echo htmlspecialchars($info['domain']); ?>/admin" target="_blank" style="color: #333; text-decoration: underline;"><?php echo htmlspecialchars($info['domain']); ?>/admin</a></span></div>
                        <div class="info-item"><span class="info-label">用户名</span><span class="info-value"><?php echo htmlspecialchars($info['admin_user']); ?></span></div>
                        <div class="info-item"><span class="info-label">密码</span><span class="info-value"><?php echo htmlspecialchars($info['admin_pass']); ?></span></div>
                    </div>
                    <div class="info-box">
                        <div class="info-title">系统链接</div>
                        <div class="info-item"><span class="info-label">聊天通用链接</span><span class="info-value" style="word-break: break-all;"><a href="<?php echo htmlspecialchars($info['domain']); ?>/index/index/home?business_id=1&theme=6a59ff" target="_blank" style="color: #333; text-decoration: underline;"><?php echo htmlspecialchars($info['domain']); ?>/index/index/home?business_id=1&theme=6a59ff</a></span></div>
                        <div class="info-item"><span class="info-label">IP限制管理</span><span class="info-value" style="word-break: break-all;"><a href="<?php echo htmlspecialchars($info['domain']); ?>/platform/adminIp/index" target="_blank" style="color: #333; text-decoration: underline;"><?php echo htmlspecialchars($info['domain']); ?>/platform/adminIp/index</a></span></div>
                    </div>
                    <div class="info-box" style="background: <?php echo $info['pusher_success'] ? '#f6ffed' : '#fff7e6'; ?>; border-color: <?php echo $info['pusher_success'] ? '#b7eb8f' : '#ffd591'; ?>;">
                        <div class="info-title" style="color: <?php echo $info['pusher_success'] ? '#389e0d' : '#d46b08'; ?>;">Pusher服务状态</div>
                        <p style="font-size: 13px; color: #666; line-height: 1.8;">
                            <?php if ($info['pusher_success']): ?>
                                ✓ <?php echo htmlspecialchars($info['pusher_msg']); ?><br><br>
                                如需重启服务：<code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">cd ymwl_pusher && php start.php restart -d</code><br>
                                如需停止服务：<code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">cd ymwl_pusher && php start.php stop</code>
                            <?php else: ?>
                                ⚠ <?php echo htmlspecialchars($info['pusher_msg']); ?><br><br>
                                手动启动步骤：<br>
                                1. 打开终端，进入 <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">ymwl_pusher</code> 目录<br>
                                2. 执行命令：<code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">php start.php start -d</code><br>
                                3. 出现 <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px;">ok</code> 表示启动成功
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="./index.php" class="btn btn-secondary">返回首页</a>
                        <a href="<?php echo htmlspecialchars($info['domain']); ?>/admin/login/index" class="btn btn-primary">后台登录</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-trans layadmin-user-login-footer">
            <p>© 2026 云宫客服系统</p>
        </div>
    </div>

    <script>
        (function() {
            var cloudLayer = document.getElementById('cloudLayer');
            var bgNantianmen = document.querySelector('.bg-nantianmen');
            if (!cloudLayer) return;

            var mouseX = 0, mouseY = 0;
            var currentX = 0, currentY = 0;

            document.addEventListener('mousemove', function(e) {
                mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
                mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
            });

            function animate() {
                currentX += (mouseX - currentX) * 0.05;
                currentY += (mouseY - currentY) * 0.05;

                var cloudMoveX = currentX * 30;
                var cloudMoveY = currentY * 20;
                cloudLayer.style.transform = 'translate(' + cloudMoveX + 'px, ' + cloudMoveY + 'px)';

                if (bgNantianmen) {
                    var bgMoveX = currentX * 8;
                    var bgMoveY = currentY * 5;
                    bgNantianmen.style.transform = 'translate(' + bgMoveX + 'px, ' + bgMoveY + 'px)';
                }

                requestAnimationFrame(animate);
            }

            animate();
        })();
    </script>
</body>
</html>
<?php
}
?>